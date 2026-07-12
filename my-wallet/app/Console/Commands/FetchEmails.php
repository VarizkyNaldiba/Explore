<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;
use App\Models\Transaction;
use Carbon\Carbon;

class FetchEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and parse transaction notifications from personal email via IMAP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting email fetch process...');

        $host = Setting::getValue('imap_host');
        $port = Setting::getValue('imap_port');
        $encryption = Setting::getValue('imap_encryption');
        $email = Setting::getValue('imap_email');
        $password = Setting::getValue('imap_password');

        if (!$host || !$port || !$email || !$password) {
            $this->error('Konfigurasi IMAP belum lengkap. Silakan atur di web dashboard.');
            return Command::FAILURE;
        }

        if (!function_exists('imap_open')) {
            $this->error('Ekstensi PHP-IMAP tidak aktif di server ini. Tidak dapat memproses email secara otomatis.');
            return Command::FAILURE;
        }

        $sslOption = $encryption === 'ssl' ? '/ssl' : ($encryption === 'tls' ? '/tls' : '');
        $mailbox = "{" . $host . ":" . $port . "/imap" . $sslOption . "/novalidate-cert}INBOX";

        $this->info("Menghubungkan ke {$email} di {$host}...");
        
        // Open connection
        $mbox = @imap_open($mailbox, $email, $password);
        if (!$mbox) {
            $this->error('Koneksi IMAP gagal: ' . (imap_last_error() ?: 'Kredensial salah.'));
            return Command::FAILURE;
        }

        // Search for UNREAD emails
        $emails = imap_search($mbox, 'UNSEEN');
        
        if (!$emails) {
            $this->info('Tidak ada email baru (belum dibaca) untuk diproses.');
            imap_close($mbox);
            return Command::SUCCESS;
        }

        $this->info('Ditemukan ' . count($emails) . ' email baru. Memulai parsing...');
        $processedCount = 0;

        foreach ($emails as $emailNumber) {
            $overview = imap_fetch_overview($mbox, $emailNumber, 0);
            $overview = $overview[0] ?? null;
            if (!$overview) continue;

            $subject = $overview->subject ?? '';
            
            // Get email body (try HTML and plain text)
            $body = imap_fetchbody($mbox, $emailNumber, 1.1); // text/html
            if (empty($body)) {
                $body = imap_fetchbody($mbox, $emailNumber, 1); // text/plain
            }

            // Decode based on encoding type
            $structure = imap_fetchstructure($mbox, $emailNumber);
            $encoding = $structure->encoding ?? 0;
            if ($encoding == 3) { // Base64
                $body = base64_decode($body);
            } elseif ($encoding == 4) { // Quoted-Printable
                $body = quoted_printable_decode($body);
            }

            // Clean up text for parsing (strip HTML tags)
            $cleanText = strip_tags($body);
            $cleanText = html_entity_decode($cleanText);

            $this->info("Memproses email: \"{$subject}\"");

            // Execute parsing
            $parsed = $this->parseTransaction($subject, $cleanText, $overview->date ?? null, $email);
            if ($parsed) {
                $processedCount++;
                // Mark email as read/seen
                imap_setflag_full($mbox, $emailNumber, "\\Seen");
            }
        }

        imap_close($mbox);
        $this->info("Selesai! Berhasil memproses {$processedCount} transaksi dari email.");
        return Command::SUCCESS;
    }

    /**
     * Parse financial transaction details from subject and body.
     */
    private function parseTransaction($subject, $body, $dateStr, $emailAddress)
    {
        $combinedText = $subject . "\n" . $body;

        // Keywords indicating financial/wallet transactions
        $financeKeywords = [
            'penarikan', 'withdraw', 'transfer', 'transaksi', 'debet', 'debit', 
            'kredit', 'credit', 'uang', 'dana', 'saldo', 'pembayaran', 'bayar',
            'topup', 'top-up', 'masuk', 'keluar', 'diterima', 'dikirim'
        ];

        $isFinance = false;
        foreach ($financeKeywords as $keyword) {
            if (stripos($combinedText, $keyword) !== false) {
                $isFinance = true;
                break;
            }
        }

        if (!$isFinance) {
            return false;
        }

        // 1. Try to extract amount
        // Pattern 1: Rp. 50.000 or Rp 50.000 or Rp.50.000 or Rp50.000
        // Pattern 2: IDR 50.000 or IDR. 50.000 etc.
        $amount = 0;
        if (preg_match('/(?:Rp|IDR)\.?\s*([0-9]{1,3}(?:\.[0-9]{3})+)/i', $combinedText, $matches)) {
            $amount = (double) str_replace('.', '', $matches[1]);
        } elseif (preg_match('/(?:Rp|IDR)\.?\s*([0-9]+)/i', $combinedText, $matches)) {
            $amount = (double) $matches[1];
        }

        if ($amount <= 0) {
            // Check numbers followed by rupiah / IDR
            if (preg_match('/([0-9]{1,3}(?:\.[0-9]{3})+)\s*(?:rupiah)/i', $combinedText, $matches)) {
                $amount = (double) str_replace('.', '', $matches[1]);
            }
        }

        if ($amount <= 0) {
            return false; // Can't parse amount
        }

        // 2. Determine type (masuk / keluar)
        $type = 'keluar'; // Default to outgoing as user highlighted "penarikan dana"

        $outgoingKeywords = ['penarikan', 'withdraw', 'tarik', 'debet', 'debit', 'keluar', 'pembayaran', 'bayar', 'transfer ke', 'sent to'];
        $incomingKeywords = ['masuk', 'penerimaan', 'kredit', 'credit', 'diterima', 'deposit', 'top up', 'topup', 'transfer dari', 'received from'];

        $outPoints = 0;
        $inPoints = 0;

        foreach ($outgoingKeywords as $word) {
            if (stripos($combinedText, $word) !== false) $outPoints++;
        }
        foreach ($incomingKeywords as $word) {
            if (stripos($combinedText, $word) !== false) $inPoints++;
        }

        if ($inPoints > $outPoints) {
            $type = 'masuk';
        }

        // 3. Extract transaction date
        $transactionDate = now();
        if ($dateStr) {
            try {
                $transactionDate = Carbon::parse($dateStr);
            } catch (\Exception $e) {
                // Keep now()
            }
        }

        // 4. Create Description
        $description = trim($subject);
        if (empty($description)) {
            $description = "Transaksi Otomatis Email";
        }

        // 5. Detect bank type
        $bankType = null;
        $banks = ['BCA', 'BNI', 'BRI', 'Mandiri', 'OVO', 'GoPay', 'Go-Pay', 'LinkAja', 'ShopeePay', 'Dana'];
        foreach ($banks as $bank) {
            if (stripos($combinedText, $bank) !== false) {
                $bankType = ($bank === 'Go-Pay') ? 'GoPay' : $bank;
                break;
            }
        }

        // Save to DB
        Transaction::create([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'email' => $emailAddress,
            'bank_type' => $bankType,
            'source' => 'email',
            'transaction_date' => $transactionDate,
        ]);

        $this->info("Berhasil mencatat transaksi: [{$type}] Nominal: Rp " . number_format($amount, 0, ',', '.') . " - Keterangan: {$description}");
        return true;
    }
}

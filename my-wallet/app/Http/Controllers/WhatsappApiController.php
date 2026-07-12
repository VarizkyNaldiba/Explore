<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class WhatsappApiController extends Controller
{
    /**
     * Update WhatsApp connection status.
     * POST /api/whatsapp/status
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => 'required|string',
            'user' => 'nullable|string',
        ]);

        Setting::setValue('whatsapp_status', $request->status);
        
        if ($request->has('user')) {
            Setting::setValue('whatsapp_user', $request->user);
        } else if ($request->status === 'disconnected') {
            Setting::setValue('whatsapp_user', null);
            Setting::setValue('whatsapp_qr', null);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status updated successfully',
        ]);
    }

    /**
     * Update WhatsApp QR code.
     * POST /api/whatsapp/qr
     */
    public function updateQr(Request $request)
    {
        $request->validate([
            'qr' => 'required|string',
        ]);

        Setting::setValue('whatsapp_status', 'scanning');
        Setting::setValue('whatsapp_qr', $request->qr);

        return response()->json([
            'success' => true,
            'message' => 'QR Code updated successfully',
        ]);
    }

    /**
     * Process message from WhatsApp bot.
     * POST /api/whatsapp/message
     */
    public function handleMessage(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'sender' => 'required|string',
        ]);

        $message = trim($request->message);
        $sender = $request->sender;

        // If the message doesn't start with command prefix, ignore or return empty
        if (!str_starts_with($message, '!')) {
            return response()->json(['reply' => null]);
        }

        // Split message by space to get command and arguments
        $parts = preg_split('/\s+/', $message, 3);
        $command = strtolower($parts[0] ?? '');

        switch ($command) {
            case '!saldo':
                return response()->json(['reply' => $this->getSaldoReply()]);

            case '!masuk':
                if (count($parts) < 3) {
                    return response()->json([
                        'reply' => "❌ Format salah. Contoh: !masuk 50000 Gaji Project"
                    ]);
                }
                return response()->json(['reply' => $this->recordTransaction('masuk', $parts[1], $parts[2])]);

            case '!keluar':
                if (count($parts) < 3) {
                    return response()->json([
                        'reply' => "❌ Format salah. Contoh: !keluar 25000 Makan Siang"
                    ]);
                }
                return response()->json(['reply' => $this->recordTransaction('keluar', $parts[1], $parts[2])]);

            case '!transaksi':
                return response()->json(['reply' => $this->getTransaksiReply()]);

            case '!help':
                return response()->json(['reply' => $this->getHelpReply()]);

            default:
                return response()->json([
                    'reply' => "❓ Perintah tidak dikenal.\nKetik *!help* untuk melihat daftar perintah yang tersedia."
                ]);
        }
    }

    /**
     * Request disconnect to the WhatsApp bot control server.
     * POST /whatsapp/disconnect
     */
    public function disconnect()
    {
        $botPort = env('BOT_PORT', 8001);
        $botUrl = "http://127.0.0.1:{$botPort}/disconnect";

        try {
            $client = new \GuzzleHttp\Client(['timeout' => 5]);
            $response = $client->post($botUrl);
            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['success']) && $body['success']) {
                // Reset state in database
                Setting::setValue('whatsapp_status', 'disconnected');
                Setting::setValue('whatsapp_user', null);
                Setting::setValue('whatsapp_qr', null);

                return back()->with('success', 'Berhasil memutus koneksi WhatsApp.');
            }
        } catch (\Exception $e) {
            Log::error("[Whatsapp] Failed to contact bot control server: " . $e->getMessage());
            
            // Fallback: if bot is not running, just reset settings in database
            Setting::setValue('whatsapp_status', 'disconnected');
            Setting::setValue('whatsapp_user', null);
            Setting::setValue('whatsapp_qr', null);

            return back()->with('success', 'Bot tidak aktif. Status koneksi WhatsApp telah di-reset ke terputus.');
        }

        return back()->with('error', 'Gagal memutus koneksi WhatsApp.');
    }

    private function formatRupiah($amount)
    {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    private function getSaldoReply()
    {
        $totalMasuk = Transaction::where('type', 'masuk')->sum('amount');
        $totalKeluar = Transaction::where('type', 'keluar')->sum('amount');
        $saldo = $totalMasuk - $totalKeluar;

        return "📊 *INFO SALDO WALLET*\n\n" .
               "📥 Dana Masuk: " . $this->formatRupiah($totalMasuk) . "\n" .
               "📤 Dana Keluar: " . $this->formatRupiah($totalKeluar) . "\n" .
               "-------------------------------------\n" .
               "💰 *Saldo Saat Ini: " . $this->formatRupiah($saldo) . "*";
    }

    private function recordTransaction($type, $amountStr, $description)
    {
        // Clean numeric amount from dots, commas, or currency symbols
        $amount = preg_replace('/[^0-9]/', '', $amountStr);
        $amount = (double) $amount;

        if ($amount <= 0) {
            return "❌ Jumlah nominal tidak valid. Masukkan angka positif saja.";
        }

        $transaction = Transaction::create([
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'source' => 'whatsapp',
        ]);

        $totalMasuk = Transaction::where('type', 'masuk')->sum('amount');
        $totalKeluar = Transaction::where('type', 'keluar')->sum('amount');
        $saldo = $totalMasuk - $totalKeluar;

        $typeText = $type === 'masuk' ? 'DANA MASUK' : 'DANA KELUAR';
        $emoji = $type === 'masuk' ? '📥' : '📤';

        return "✅ *CATATAN BERHASIL!*\n\n" .
               "Status: {$emoji} {$typeText}\n" .
               "Nominal: " . $this->formatRupiah($amount) . "\n" .
               "Keterangan: {$description}\n" .
               "-------------------------------------\n" .
               "💰 *Saldo Saat Ini: " . $this->formatRupiah($saldo) . "*";
    }

    private function getTransaksiReply()
    {
        $transactions = Transaction::latest('id')->limit(5)->get();

        if ($transactions->isEmpty()) {
            return "📭 Belum ada transaksi yang dicatat.";
        }

        $reply = "📅 *5 TRANSAKSI TERAKHIR*\n\n";
        foreach ($transactions as $index => $t) {
            $emoji = $t->type === 'masuk' ? '📥 [Masuk]' : '📤 [Keluar]';
            $date = $t->transaction_date->format('d/m H:i');
            $sourceStr = ucfirst($t->source);
            $reply .= ($index + 1) . ". {$emoji} " . $this->formatRupiah($t->amount) . " - {$t->description} ({$sourceStr} | {$date})\n";
        }

        return $reply;
    }

    private function getHelpReply()
    {
        return "🤖 *BANTUAN WALLET BOT*\n\n" .
               "Gunakan perintah berikut untuk mencatat keuangan Anda:\n\n" .
               "📌 *Cek Saldo*\n" .
               "Ketik: `!saldo`\n\n" .
               "📥 *Catat Dana Masuk*\n" .
               "Ketik: `!masuk <nominal> <keterangan>`\n" .
               "Contoh: `!masuk 50000 Gaji Project`\n\n" .
               "📤 *Catat Dana Keluar*\n" .
               "Ketik: `!keluar <nominal> <keterangan>`\n" .
               "Contoh: `!keluar 25000 Makan Bakso`\n\n" .
               "📅 *Lihat Riwayat*\n" .
               "Ketik: `!transaksi`\n\n" .
               "💡 _Catatan: Nominal hanya berupa angka tanpa tanda titik/koma (misal: 100000)._";
    }
}

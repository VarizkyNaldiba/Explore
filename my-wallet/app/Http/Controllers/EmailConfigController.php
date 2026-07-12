<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;

class EmailConfigController extends Controller
{
    /**
     * Save IMAP configuration settings.
     * POST /api/email/config
     */
    public function saveConfig(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'encryption' => 'required|string|in:ssl,tls,none',
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        Setting::setValue('imap_host', $request->host);
        Setting::setValue('imap_port', $request->port);
        Setting::setValue('imap_encryption', $request->encryption);
        Setting::setValue('imap_email', $request->email);
        Setting::setValue('imap_password', $request->password);

        return back()->with('success', 'Konfigurasi email IMAP berhasil disimpan.');
    }

    /**
     * Test the IMAP connection parameters.
     * POST /api/email/test
     */
    public function testConnection(Request $request)
    {
        $request->validate([
            'host' => 'required|string',
            'port' => 'required|numeric',
            'encryption' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $host = $request->host;
        $port = $request->port;
        $encryption = strtolower($request->encryption);
        $email = $request->email;
        $password = $request->password;

        // Check if PHP IMAP extension is loaded
        if (function_exists('imap_open')) {
            // Structure: {imap.example.com:993/imap/ssl/novalidate-cert}INBOX
            $sslOption = $encryption === 'ssl' ? '/ssl' : ($encryption === 'tls' ? '/tls' : '');
            $mailbox = "{" . $host . ":" . $port . "/imap" . $sslOption . "/novalidate-cert}INBOX";

            // Set timeout to 5 seconds
            imap_timeout(IMAP_OPENTIMEOUT, 5);
            
            $mbox = @imap_open($mailbox, $email, $password);
            if ($mbox) {
                imap_close($mbox);
                return response()->json([
                    'success' => true,
                    'message' => 'Koneksi IMAP Berhasil! Akun email Anda terhubung dengan benar.'
                ]);
            } else {
                $error = imap_last_error() ?: 'Kredensial salah atau server menolak koneksi.';
                return response()->json([
                    'success' => false,
                    'message' => 'Koneksi Gagal: ' . $error
                ]);
            }
        } else {
            // Fallback: Test network connection to port
            $prefix = $encryption === 'ssl' ? 'ssl://' : '';
            $timeout = 5;
            
            $fp = @fsockopen($prefix . $host, $port, $errno, $errstr, $timeout);
            if ($fp) {
                fclose($fp);
                return response()->json([
                    'success' => true,
                    'message' => 'Server email terdeteksi aktif di ' . $host . ':' . $port . '. Namun, ekstensi PHP-IMAP tidak aktif pada PHP server Anda. Penarikan email akan dialihkan ke socket-level fallback parser saat script fetch dijalankan.'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Koneksi Gagal: Tidak dapat menjangkau server {$host}:{$port}. Error: {$errstr} ({$errno})"
                ]);
            }
        }
    }
}

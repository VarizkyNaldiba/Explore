<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Setting;

class DashboardController extends Controller
{
    /**
     * Show the wallet dashboard.
     */
    public function index()
    {
        // 1. Calculate general stats
        $totalMasuk = Transaction::where('type', 'masuk')->sum('amount');
        $totalKeluar = Transaction::where('type', 'keluar')->sum('amount');
        $saldo = $totalMasuk - $totalKeluar;

        // 2. Fetch recent transactions
        $transactions = Transaction::latest('id')->paginate(10);

        // 3. WhatsApp Integration Status
        $waStatus = Setting::getValue('whatsapp_status', 'disconnected');
        $waUser = Setting::getValue('whatsapp_user', null);
        $waQr = Setting::getValue('whatsapp_qr', null);

        // 4. Email IMAP Settings
        $imapConfig = [
            'host' => Setting::getValue('imap_host', ''),
            'port' => Setting::getValue('imap_port', '993'),
            'encryption' => Setting::getValue('imap_encryption', 'ssl'),
            'email' => Setting::getValue('imap_email', ''),
            'password' => Setting::getValue('imap_password', ''),
        ];

        // 5. Chart data for the last 7 days
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $carbonDate = now()->subDays($i);
            $dateStr = $carbonDate->format('Y-m-d');
            $dateLabel = $carbonDate->format('d M');

            $masuk = Transaction::where('type', 'masuk')
                ->whereDate('transaction_date', $dateStr)
                ->sum('amount');

            $keluar = Transaction::where('type', 'keluar')
                ->whereDate('transaction_date', $dateStr)
                ->sum('amount');

            $chartData[] = [
                'label' => $dateLabel,
                'masuk' => (double)$masuk,
                'keluar' => (double)$keluar,
            ];
        }

        return view('dashboard', compact(
            'totalMasuk',
            'totalKeluar',
            'saldo',
            'transactions',
            'waStatus',
            'waUser',
            'waQr',
            'imapConfig',
            'chartData'
        ));
    }

    /**
     * Store a manually entered transaction.
     */
    public function storeManual(Request $request)
    {
        $request->validate([
            'type' => 'required|in:masuk,keluar',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'bank_type' => 'nullable|string|max:50',
        ]);

        Transaction::create([
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'email' => $request->email,
            'bank_type' => $request->bank_type,
            'source' => 'manual',
            'transaction_date' => now(),
        ]);

        return back()->with('success', 'Transaksi manual berhasil dicatat.');
    }

    /**
     * Update user profile settings (Name, Email, Password).
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return back()->with('success', 'Profil admin berhasil diperbarui.');
    }

    /**
     * Update an existing transaction.
     */
    public function updateTransaction(Request $request, $id)
    {
        $transaction = Transaction::findOrFail($id);

        $request->validate([
            'type' => 'required|in:masuk,keluar',
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'bank_type' => 'nullable|string|max:50',
            'transaction_date' => 'required|date',
        ]);

        $transaction->update([
            'type' => $request->type,
            'amount' => $request->amount,
            'description' => $request->description,
            'email' => $request->email,
            'bank_type' => $request->bank_type,
            'transaction_date' => $request->transaction_date,
        ]);

        return back()->with('success', 'Transaksi berhasil diperbarui.');
    }

    /**
     * Delete an existing transaction.
     */
    public function deleteTransaction($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return back()->with('success', 'Transaksi berhasil dihapus.');
    }
}

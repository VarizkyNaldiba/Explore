<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wallet - WhatsApp Controlled Dashboard</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <!-- Chart.js from CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<div class="dashboard-wrapper">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div>
            <div class="sidebar-logo">
                <h2>My Wallet</h2>
                <span>Integrator Bot</span>
            </div>
            
            <nav class="sidebar-menu">
                <a href="#dashboard" class="menu-item active" data-tab="dashboard">
                    <span class="icon">📊</span> Dashboard
                </a>
                <a href="#transaksi" class="menu-item" data-tab="transaksi">
                    <span class="icon">💸</span> Transaksi
                </a>
                <a href="#integrasi" class="menu-item" data-tab="integrasi">
                    <span class="icon">⚙️</span> Integrasi & Profil
                </a>
            </nav>
        </div>

        <div class="sidebar-footer">
            <div class="sidebar-user">
                <div class="avatar">👤</div>
                <div class="user-info">
                    <span class="name">{{ auth()->user()->name }}</span>
                    <span class="role">Administrator</span>
                </div>
            </div>
            <form action="{{ route('logout') }}" method="POST" style="margin: 0; width: 100%;">
                @csrf
                <button type="submit" class="btn-sidebar-logout">Keluar ➔</button>
            </form>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="content-area">
        <!-- Top Bar -->
        <div class="top-bar">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <button class="menu-toggle" id="menuToggle">☰</button>
                <h1 id="page-title">Dashboard</h1>
            </div>
            <div class="server-time" id="serverTime">
                {{ now()->format('d M Y - H:i:s') }}
            </div>
        </div>

        @if(session('success'))
            <div class="alert-success-box" style="margin-bottom: 2rem;">
                {{ session('success') }}
            </div>
        @endif

        <!-- Tab 1: Dashboard -->
        <div id="tab-dashboard" class="tab-content active">
            <!-- Stats Row -->
            <div class="stats-grid">
                <div class="stat-card masuk">
                    <div class="stat-title">Total Dana Masuk</div>
                    <div class="stat-value">Rp {{ number_format($totalMasuk, 0, ',', '.') }}</div>
                    <div class="stat-desc">Akumulasi seluruh transaksi masuk</div>
                </div>
                
                <div class="stat-card keluar">
                    <div class="stat-title">Total Dana Keluar</div>
                    <div class="stat-value">Rp {{ number_format($totalKeluar, 0, ',', '.') }}</div>
                    <div class="stat-desc">Akumulasi seluruh transaksi keluar</div>
                </div>

                <div class="stat-card saldo">
                    <div class="stat-title">Saldo Saat Ini</div>
                    <div class="stat-value">Rp {{ number_format($saldo, 0, ',', '.') }}</div>
                    <div class="stat-desc">Sisa saldo keuangan aktif</div>
                </div>
            </div>

            <!-- Chart Card -->
            <div class="panel-card">
                <div class="card-title-bar">
                    <h2>📈 Grafik Arus Kas (7 Hari Terakhir)</h2>
                </div>
                <div class="chart-container">
                    <canvas id="cashflowChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Tab 2: Transaksi -->
        <div id="tab-transaksi" class="tab-content">
            <div class="panel-card">
                <div class="card-title-bar">
                    <h2>📅 Riwayat Transaksi</h2>
                    <div style="display: flex; gap: 0.5rem;">
                        <button onclick="openModal('modal-create-transaction')" class="btn btn-primary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto;">+ Tambah Manual</button>
                        <form action="{{ route('email.fetch') }}" method="POST" style="margin: 0;">
                            @csrf
                            <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.8rem; font-size: 0.8rem; width: auto;">🔄 Tarik Email</button>
                        </form>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Tipe</th>
                                <th>Nominal</th>
                                <th>Keterangan</th>
                                <th>Email</th>
                                <th>Bank</th>
                                <th>Sumber</th>
                                <th>Waktu</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $tx)
                                <tr>
                                    <td>
                                        <span class="badge {{ $tx->type }}">
                                            {{ $tx->type === 'masuk' ? 'Masuk' : 'Keluar' }}
                                        </span>
                                    </td>
                                    <td style="font-weight: 600; color: {{ $tx->type === 'masuk' ? 'var(--green-neon)' : 'var(--red-neon)' }}">
                                        Rp {{ number_format($tx->amount, 0, ',', '.') }}
                                    </td>
                                    <td>{{ $tx->description }}</td>
                                    <td style="font-size: 0.85rem; color: var(--text-muted);">{{ $tx->email ?: '-' }}</td>
                                    <td>
                                        @if($tx->bank_type)
                                            <span class="badge" style="background-color: rgba(255, 255, 255, 0.05); color: var(--text-main); border: 1px solid var(--card-border);">
                                                {{ $tx->bank_type }}
                                            </span>
                                        @else
                                            <span style="color: var(--text-muted);">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge source-{{ $tx->source }}">
                                            {{ $tx->source }}
                                        </span>
                                    </td>
                                    <td style="color: var(--text-muted); font-size: 0.85rem;">
                                        {{ $tx->transaction_date->format('d M Y, H:i') }}
                                    </td>
                                    <td>
                                        <div style="display: flex; gap: 0.25rem;">
                                            <button class="btn btn-secondary btn-edit-tx" 
                                                    data-id="{{ $tx->id }}"
                                                    data-type="{{ $tx->type }}"
                                                    data-amount="{{ $tx->amount }}"
                                                    data-description="{{ $tx->description }}"
                                                    data-email="{{ $tx->email }}"
                                                    data-bank_type="{{ $tx->bank_type }}"
                                                    data-date="{{ $tx->transaction_date->toIso8601String() }}"
                                                    style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 6px; width: auto;">✏️ Edit</button>
                                            <button class="btn btn-secondary btn-delete-tx"
                                                    data-id="{{ $tx->id }}"
                                                    data-type="{{ $tx->type }}"
                                                    data-amount="{{ $tx->amount }}"
                                                    data-description="{{ $tx->description }}"
                                                    style="padding: 0.25rem 0.5rem; font-size: 0.75rem; border-radius: 6px; width: auto; color: var(--red-neon); border-color: rgba(255, 23, 68, 0.2);">🗑️ Hapus</button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" style="text-align: center; color: var(--text-muted); padding: 2rem;">
                                        Belum ada transaksi tercatat.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="pagination-wrapper">
                    {{ $transactions->links() }}
                </div>
            </div>
        </div>

        <!-- Tab 3: Integrasi & Profil -->
        <div id="tab-integrasi" class="tab-content">
            <div class="dashboard-body" style="grid-template-columns: 1fr 1.2fr;">
                
                <!-- Left Side (WhatsApp Bot control) -->
                <div>
                    <div class="panel-card">
                        <div class="card-title-bar">
                            <h2>🤖 WhatsApp Bot Control</h2>
                        </div>
                        
                        <div class="whatsapp-box">
                            <!-- Status Badge -->
                            <div id="waStatusBadge" class="status-badge {{ $waStatus }}">
                                @if($waStatus === 'connected')
                                    Terhubung
                                @elseif($waStatus === 'scanning')
                                    Siap Scan
                                @else
                                    Terputus
                                @endif
                            </div>

                            <!-- QR Code Display Area -->
                            <div class="qr-code-wrapper" id="qrWrapper">
                                <div class="qr-loading" id="qrLoading" style="display: {{ $waStatus === 'scanning' ? 'none' : 'flex' }}">
                                    @if($waStatus === 'connected')
                                        <div style="color: var(--green-neon); font-size: 2rem;">✓</div>
                                        <span style="font-weight: 600;">Bot Aktif</span>
                                    @else
                                        <div class="spinner"></div>
                                        <span id="qrStatusText">Menunggu Bot Aktif...</span>
                                    @endif
                                </div>
                                <img id="qrImage" class="qr-code-image" src="{{ $waQr ?: '' }}" alt="WhatsApp QR Code" style="display: {{ $waStatus === 'scanning' && $waQr ? 'block' : 'none' }}">
                            </div>

                            <!-- Connection Info / Instructions -->
                            <div class="wa-instructions" id="waInfoText">
                                @if($waStatus === 'connected')
                                    Connected user: <strong>{{ $waUser ?: 'Unknown' }}</strong><br>
                                    Kirim pesan WhatsApp ke nomor bot untuk memasukkan data.
                                @elseif($waStatus === 'scanning')
                                    Scan QR Code di atas dengan aplikasi WhatsApp HP Anda (Tautkan Perangkat).
                                @else
                                    Jalankan command di folder bot:<br><code>npm run dev</code> atau <code>node bot.js</code> untuk mengaktifkan bot.
                                @endif
                            </div>

                            <!-- Disconnect Button Wrapper -->
                            <div id="waDisconnectWrapper" style="margin-top: 1rem; width: 100%; display: {{ $waStatus === 'connected' || $waStatus === 'scanning' ? 'block' : 'none' }}">
                                <form action="{{ route('whatsapp.disconnect') }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin memutuskan koneksi WhatsApp?')">
                                    @csrf
                                    <button type="submit" class="btn btn-logout" style="width: 100%; padding: 0.6rem; font-size: 0.85rem;">🔌 Putuskan Koneksi</button>
                                </form>
                            </div>
                        </div>

                        <div class="alert-info-box" style="margin-bottom: 0;">
                            <strong>Format Perintah WhatsApp:</strong><br>
                            • <code>!saldo</code> (Cek Saldo)<br>
                            • <code>!masuk [angka] [ket]</code> (Catat Uang Masuk)<br>
                            • <code>!keluar [angka] [ket]</code> (Catat Uang Keluar)<br>
                            • <code>!transaksi</code> (Riwayat 5 Transaksi)
                        </div>
                    </div>
                </div>

                <!-- Right Side (Email Settings and Admin Profile) -->
                <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                    <!-- Email IMAP Parsing Settings -->
                    <div class="panel-card">
                        <div class="card-title-bar">
                            <h2>📧 Email IMAP (Tarik Penarikan)</h2>
                        </div>

                        <form id="imapForm" action="{{ route('email.config.save') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="host">IMAP Host Server</label>
                                <input type="text" name="host" id="host" value="{{ $imapConfig['host'] }}" placeholder="imap.gmail.com" required>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="port">Port</label>
                                    <input type="number" name="port" id="port" value="{{ $imapConfig['port'] }}" placeholder="993" required>
                                </div>
                                <div class="form-group">
                                    <label for="encryption">Enkripsi</label>
                                    <select name="encryption" id="encryption" required>
                                        <option value="ssl" {{ $imapConfig['encryption'] === 'ssl' ? 'selected' : '' }}>SSL</option>
                                        <option value="tls" {{ $imapConfig['encryption'] === 'tls' ? 'selected' : '' }}>TLS</option>
                                        <option value="none" {{ $imapConfig['encryption'] === 'none' ? 'selected' : '' }}>None</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="email">Alamat Email</label>
                                <input type="email" name="email" id="email" value="{{ $imapConfig['email'] }}" placeholder="user@gmail.com" required>
                            </div>

                            <div class="form-group">
                                <label for="password">Password / App Password</label>
                                <input type="password" name="password" id="password" value="{{ $imapConfig['password'] }}" placeholder="••••••••••••••••" required>
                            </div>

                            <div style="display: flex; gap: 0.75rem;">
                                <button type="button" id="btnTestImap" class="btn btn-secondary" style="flex: 1;">Tes Koneksi</button>
                                <button type="submit" class="btn btn-primary" style="flex: 1.5;">Simpan Pengaturan</button>
                            </div>
                        </form>

                        <div id="testResult" style="margin-top: 1rem; font-size: 0.85rem; display: none;"></div>
                    </div>

                    <!-- Edit Admin Profile Card -->
                    <div class="panel-card">
                        <div class="card-title-bar">
                            <h2>👤 Edit Profil Admin</h2>
                        </div>

                        <form action="{{ route('profile.update') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <label for="profile_name">Nama Lengkap</label>
                                <input type="text" name="name" id="profile_name" value="{{ auth()->user()->name }}" required>
                            </div>

                            <div class="form-group">
                                <label for="profile_email">Alamat Email</label>
                                <input type="email" name="email" id="profile_email" value="{{ auth()->user()->email }}" required>
                            </div>

                            <div class="form-group">
                                <label for="profile_password">Password Baru (Kosongkan jika tidak diganti)</label>
                                <input type="password" name="password" id="profile_password" placeholder="Minimal 8 karakter">
                            </div>

                            <div class="form-group">
                                <label for="profile_password_confirmation">Konfirmasi Password Baru</label>
                                <input type="password" name="password_confirmation" id="profile_password_confirmation" placeholder="Ulangi password baru">
                            </div>

                            <button type="submit" class="btn btn-primary">Perbarui Profil</button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </main>
</div>

<!-- ================= MODALS SYSTEM ================= -->

<!-- Create Transaction Modal -->
<div id="modal-create-transaction" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3>📝 Tambah Transaksi Manual</h3>
            <button class="modal-close" onclick="closeModal('modal-create-transaction')">&times;</button>
        </div>
        <form action="{{ route('transaction.manual.save') }}" method="POST">
            @csrf
            <div class="modal-body">
                <div class="form-group">
                    <label for="manual_type">Jenis Transaksi</label>
                    <select name="type" id="manual_type" required>
                        <option value="masuk">Dana Masuk (Pemasukan)</option>
                        <option value="keluar">Dana Keluar (Pengeluaran)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="manual_amount">Nominal (Rupiah)</label>
                    <input type="number" name="amount" id="manual_amount" placeholder="Contoh: 50000" min="1" required>
                </div>

                <div class="form-group">
                    <label for="manual_desc">Keterangan</label>
                    <input type="text" name="description" id="manual_desc" placeholder="Contoh: Makan Siang" required>
                </div>

                <div class="form-group">
                    <label for="manual_email">Alamat Email Terkait (Opsional)</label>
                    <input type="email" name="email" id="manual_email" placeholder="Contoh: user@domain.com">
                </div>

                <div class="form-group">
                    <label for="manual_bank">Jenis Bank / Dompet (Opsional)</label>
                    <select name="bank_type" id="manual_bank">
                        <option value="">-- Pilih Bank / Dompet --</option>
                        <option value="BCA">BCA</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="OVO">OVO</option>
                        <option value="GoPay">GoPay</option>
                        <option value="Dana">Dana</option>
                        <option value="LinkAja">LinkAja</option>
                        <option value="ShopeePay">ShopeePay</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-create-transaction')" style="width: auto;">Batal</button>
                <button type="submit" class="btn btn-primary" style="width: auto;">Catat Transaksi</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="modal-edit-transaction" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3>✏️ Edit Transaksi</h3>
            <button class="modal-close" onclick="closeModal('modal-edit-transaction')">&times;</button>
        </div>
        <form id="edit-transaction-form" method="POST">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_type">Jenis Transaksi</label>
                    <select name="type" id="edit_type" required>
                        <option value="masuk">Dana Masuk (Pemasukan)</option>
                        <option value="keluar">Dana Keluar (Pengeluaran)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_amount">Nominal (Rupiah)</label>
                    <input type="number" name="amount" id="edit_amount" min="1" required>
                </div>

                <div class="form-group">
                    <label for="edit_desc">Keterangan</label>
                    <input type="text" name="description" id="edit_desc" required>
                </div>

                <div class="form-group">
                    <label for="edit_email">Alamat Email Terkait (Opsional)</label>
                    <input type="email" name="email" id="edit_email">
                </div>

                <div class="form-group">
                    <label for="edit_bank">Jenis Bank / Dompet (Opsional)</label>
                    <select name="bank_type" id="edit_bank">
                        <option value="">-- Pilih Bank / Dompet --</option>
                        <option value="BCA">BCA</option>
                        <option value="Mandiri">Mandiri</option>
                        <option value="BNI">BNI</option>
                        <option value="BRI">BRI</option>
                        <option value="OVO">OVO</option>
                        <option value="GoPay">GoPay</option>
                        <option value="Dana">Dana</option>
                        <option value="LinkAja">LinkAja</option>
                        <option value="ShopeePay">ShopeePay</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="edit_date">Tanggal Transaksi</label>
                    <input type="datetime-local" name="transaction_date" id="edit_date" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit-transaction')" style="width: auto;">Batal</button>
                <button type="submit" class="btn btn-primary" style="width: auto;">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Transaction Modal -->
<div id="modal-delete-transaction" class="modal-overlay">
    <div class="modal-container">
        <div class="modal-header">
            <h3>⚠️ Hapus Transaksi</h3>
            <button class="modal-close" onclick="closeModal('modal-delete-transaction')">&times;</button>
        </div>
        <form id="delete-transaction-form" method="POST">
            @csrf
            @method('DELETE')
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus transaksi ini? Tindakan ini tidak dapat dibatalkan.</p>
                <div class="transaction-detail-box" id="delete-tx-details">
                    <!-- filled dynamically in JS -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('modal-delete-transaction')" style="width: auto;">Batal</button>
                <button type="submit" class="btn btn-primary" style="width: auto; background: var(--red-neon); box-shadow: 0 4px 15px var(--red-neon-glow);">Hapus Permanen</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Live Server Time ticking
    setInterval(() => {
        const timeBox = document.getElementById('serverTime');
        const now = new Date();
        timeBox.textContent = now.toLocaleDateString('id-ID', {
            day: '2-digit', month: 'short', year: 'numeric'
        }) + ' - ' + now.toLocaleTimeString('id-ID', { hour12: false });
    }, 1000);

    // Tab switching logic based on hash routing
    function switchTab(tabId) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
        document.querySelectorAll('.menu-item').forEach(el => el.classList.remove('active'));
        
        const targetTab = document.getElementById('tab-' + tabId);
        if (targetTab) {
            targetTab.classList.add('active');
        }
        
        const menuItem = document.querySelector(`.menu-item[data-tab="${tabId}"]`);
        if (menuItem) {
            menuItem.classList.add('active');
        }
        
        const titles = {
            'dashboard': 'Dashboard Keuangan',
            'transaksi': 'Riwayat Transaksi (CRUD)',
            'integrasi': 'Integrasi & Pengaturan'
        };
        document.getElementById('page-title').textContent = titles[tabId] || 'Dashboard';
    }
    
    function handleRouting() {
        const hash = window.location.hash || '#dashboard';
        const tabId = hash.replace('#', '');
        switchTab(tabId);
    }
    
    window.addEventListener('hashchange', handleRouting);
    
    // Inits
    window.addEventListener('DOMContentLoaded', () => {
        handleRouting();
        
        // Handle mobile sidebar toggle
        const menuToggle = document.getElementById('menuToggle');
        const sidebar = document.querySelector('.sidebar');
        
        if (menuToggle && sidebar) {
            menuToggle.addEventListener('click', (e) => {
                sidebar.classList.toggle('open');
                e.stopPropagation();
            });
            
            document.addEventListener('click', (e) => {
                if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && e.target !== menuToggle) {
                    sidebar.classList.remove('open');
                }
            });
        }
    });

    // Modal Control Functions
    function openModal(id) {
        document.getElementById(id).classList.add('active');
    }

    // Reset create modal fields when closing
    function closeModal(id) {
        document.getElementById(id).classList.remove('active');
        if (id === 'modal-create-transaction') {
            document.querySelector('#modal-create-transaction form').reset();
        }
    }

    // Close modal when clicking outside the container
    document.querySelectorAll('.modal-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });

    // Populate Edit and Delete Modals
    function openEditModal(tx) {
        const form = document.getElementById('edit-transaction-form');
        form.action = `/transaction/${tx.id}`;
        
        document.getElementById('edit_type').value = tx.type;
        document.getElementById('edit_amount').value = tx.amount;
        document.getElementById('edit_desc').value = tx.description;
        document.getElementById('edit_email').value = tx.email || '';
        document.getElementById('edit_bank').value = tx.bank_type || '';
        
        if (tx.transaction_date) {
            const d = new Date(tx.transaction_date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            const hours = String(d.getHours()).padStart(2, '0');
            const minutes = String(d.getMinutes()).padStart(2, '0');
            document.getElementById('edit_date').value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        openModal('modal-edit-transaction');
    }

    function openDeleteModal(tx) {
        const form = document.getElementById('delete-transaction-form');
        form.action = `/transaction/${tx.id}`;
        
        const details = document.getElementById('delete-tx-details');
        const amountFormatted = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(tx.amount);
        const typeLabel = tx.type === 'masuk' ? 'Dana Masuk' : 'Dana Keluar';
        details.innerHTML = `
            <strong>Keterangan:</strong> ${tx.description}<br>
            <strong>Nominal:</strong> ${amountFormatted}<br>
            <strong>Jenis:</strong> ${typeLabel}
        `;
        
        openModal('modal-delete-transaction');
    }

    // Event delegation for edit and delete buttons in the table
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-edit-tx') || e.target.closest('.btn-edit-tx')) {
            const btn = e.target.classList.contains('btn-edit-tx') ? e.target : e.target.closest('.btn-edit-tx');
            const tx = {
                id: btn.getAttribute('data-id'),
                type: btn.getAttribute('data-type'),
                amount: btn.getAttribute('data-amount'),
                description: btn.getAttribute('data-description'),
                email: btn.getAttribute('data-email'),
                bank_type: btn.getAttribute('data-bank_type'),
                transaction_date: btn.getAttribute('data-date')
            };
            openEditModal(tx);
        }
        
        if (e.target.classList.contains('btn-delete-tx') || e.target.closest('.btn-delete-tx')) {
            const btn = e.target.classList.contains('btn-delete-tx') ? e.target : e.target.closest('.btn-delete-tx');
            const tx = {
                id: btn.getAttribute('data-id'),
                type: btn.getAttribute('data-type'),
                amount: btn.getAttribute('data-amount'),
                description: btn.getAttribute('data-description')
            };
            openDeleteModal(tx);
        }
    });

    // Render Cashflow Chart
    const ctx = document.getElementById('cashflowChart').getContext('2d');
    const rawChartData = @json($chartData);
    
    const labels = rawChartData.map(item => item.label);
    const dataMasuk = rawChartData.map(item => item.masuk);
    const dataKeluar = rawChartData.map(item => item.keluar);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Dana Masuk',
                    data: dataMasuk,
                    backgroundColor: 'rgba(0, 230, 118, 0.4)',
                    borderColor: '#00e676',
                    borderWidth: 2,
                    borderRadius: 5,
                },
                {
                    label: 'Dana Keluar',
                    data: dataKeluar,
                    backgroundColor: 'rgba(255, 23, 68, 0.4)',
                    borderColor: '#ff1744',
                    borderWidth: 2,
                    borderRadius: 5,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: '#f5f6fa',
                        font: { family: 'Outfit', size: 12 }
                    }
                }
            },
            scales: {
                x: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { color: '#8b8f9e', font: { family: 'Outfit' } }
                },
                y: {
                    grid: { color: 'rgba(255, 255, 255, 0.05)' },
                    ticks: { 
                        color: '#8b8f9e', 
                        font: { family: 'Outfit' },
                        callback: function(value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // Test IMAP Connection via AJAX
    document.getElementById('btnTestImap').addEventListener('click', async function() {
        const form = document.getElementById('imapForm');
        const formData = new FormData(form);
        const resultDiv = document.getElementById('testResult');
        const btn = this;
        
        btn.textContent = 'Menghubungkan...';
        btn.disabled = true;
        
        resultDiv.style.display = 'block';
        resultDiv.style.color = '#8b8f9e';
        resultDiv.textContent = 'Mengirim data koneksi ke server...';

        try {
            const response = await fetch('{{ route("email.config.test") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    host: formData.get('host'),
                    port: formData.get('port'),
                    encryption: formData.get('encryption'),
                    email: formData.get('email'),
                    password: formData.get('password'),
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                resultDiv.style.color = 'var(--green-neon)';
                resultDiv.textContent = result.message;
            } else {
                resultDiv.style.color = 'var(--red-neon)';
                resultDiv.textContent = result.message;
            }
        } catch (err) {
            resultDiv.style.color = 'var(--red-neon)';
            resultDiv.textContent = 'Terjadi kesalahan jaringan atau server error.';
        } finally {
            btn.textContent = 'Tes Koneksi';
            btn.disabled = false;
        }
    });

    // Polling WhatsApp Bot Status & QR code updates every 3 seconds
    let lastStatus = '{{ $waStatus }}';
    
    async function pollWhatsappStatus() {
        try {
            const response = await fetch('{{ route("whatsapp.status.check") }}');
            const data = await response.json();
            
            const badge = document.getElementById('waStatusBadge');
            const qrWrapper = document.getElementById('qrWrapper');
            const qrLoading = document.getElementById('qrLoading');
            const qrImage = document.getElementById('qrImage');
            const infoText = document.getElementById('waInfoText');
            const disconnectWrapper = document.getElementById('waDisconnectWrapper');
            
            // 1. Update status badge classes and text
            badge.className = 'status-badge ' + data.status;
            if (data.status === 'connected') {
                badge.textContent = 'Terhubung';
            } else if (data.status === 'scanning') {
                badge.textContent = 'Siap Scan';
            } else {
                badge.textContent = 'Terputus';
            }
            
            // 2. Update QR view and loading states
            if (data.status === 'scanning' && data.qr) {
                qrLoading.style.display = 'none';
                qrImage.style.display = 'block';
                qrImage.src = data.qr;
            } else {
                qrImage.style.display = 'none';
                qrLoading.style.display = 'flex';
                
                if (data.status === 'connected') {
                    qrLoading.innerHTML = '<div style="color: var(--green-neon); font-size: 2rem;">✓</div><span style="font-weight: 600;">Bot Aktif</span>';
                } else {
                    qrLoading.innerHTML = '<div class="spinner"></div><span id="qrStatusText">Menunggu Bot Aktif...</span>';
                }
            }
            
            // 3. Update info instructions text
            if (data.status === 'connected') {
                infoText.innerHTML = `Connected user: <strong>${data.user || 'Unknown'}</strong><br>Kirim pesan WhatsApp ke nomor bot untuk memasukkan data.`;
            } else if (data.status === 'scanning') {
                infoText.innerHTML = 'Scan QR Code di atas dengan aplikasi WhatsApp HP Anda (Tautkan Perangkat).';
            } else {
                infoText.innerHTML = 'Jalankan command di folder bot:<br><code>npm run dev</code> atau <code>node bot.js</code> untuk mengaktifkan bot.';
            }

            // 4. Update disconnect button visibility
            if (data.status === 'connected' || data.status === 'scanning') {
                disconnectWrapper.style.display = 'block';
            } else {
                disconnectWrapper.style.display = 'none';
            }
            
            // If bot just connected, reload page to refresh stats
            if (lastStatus !== 'connected' && data.status === 'connected') {
                setTimeout(() => window.location.reload(), 1500);
            }
            lastStatus = data.status;
            
        } catch (e) {
            console.error('Failed to poll whatsapp status', e);
        }
    }

    // Start polling every 3 seconds
    setInterval(pollWhatsappStatus, 3000);
</script>
</body>
</html>

<?php
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'siswa') {
    header("Location: index.php");
    exit;
}

$nis = $_SESSION['nis'];

// Proses Input Aspirasi
if (isset($_POST['kirim'])) {
    $id_kategori = $_POST['id_kategori'];
    $lokasi = $_POST['lokasi'];
    $pesan = $_POST['pesan'];
    
    $nama_foto = NULL; 
    $upload_sukses = true; 

    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        $ukuran = $_FILES['foto']['size'];
        $ext = pathinfo($foto, PATHINFO_EXTENSION);
        $nama_foto = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $folder = "uploads/";

        if (!is_dir($folder)) mkdir($folder, 0777, true);
        $path = $folder . $nama_foto;
        
        if ($ukuran > 2 * 1024 * 1024) {
            $upload_sukses = false;
            $error = "Ukuran file terlalu besar! Maksimal 2MB.";
        } else {
            if (!move_uploaded_file($tmp, $path)) {
                $upload_sukses = false;
                $error = "Gagal mengunggah foto ke server!";
            }
        }
    }
    
    if ($upload_sukses) {
        $stmt = $conn->prepare("INSERT INTO input_aspirasi (nis, id_kategori, lokasi, pesan, foto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $nis, $id_kategori, $lokasi, $pesan, $nama_foto);

        if ($stmt->execute()) {
            $id_pelaporan = $conn->insert_id;
            $stmt_track = $conn->prepare("INSERT INTO aspirasi (id_pelaporan, id_kategori, status) VALUES (?, ?, 'Menunggu')");
            $stmt_track->bind_param("ii", $id_pelaporan, $id_kategori);
            $stmt_track->execute();
            $msg = "Aspirasi berhasil dikirim!";
        } else {
            $error = "Database Error: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Siswa | Sistem Pengaduan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .glass-panel {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#0b1120] text-slate-200 min-h-screen font-sans pb-12">

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-10">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight">Halo, <?= $_SESSION['nama']; ?>!</h2>
            <p class="text-slate-400 mt-1">Selamat datang di panel pengaduan dan aspirasi siswa.</p>
        </div>
        <a href="logout.php" class="inline-flex items-center px-5 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/50 rounded-xl font-semibold transition-all">
            Logout
        </a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-500 p-4 rounded-2xl mb-8 flex justify-between items-center">
            <span class="text-sm font-medium"><?= $msg ?></span>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-white">&times;</button>
        </div>
    <?php endif; ?>
    <?php if(isset($error)): ?>
        <div class="bg-red-500/10 border border-red-500/50 text-red-500 p-4 rounded-2xl mb-8 flex justify-between items-center">
            <span class="text-sm font-medium"><?= $error ?></span>
            <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-white">&times;</button>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <div class="lg:col-span-4">
            <div class="glass-panel p-6 rounded-3xl shadow-xl">
                <h3 class="text-lg font-bold text-white mb-6 flex items-center gap-2">
                    <span class="w-2 h-6 bg-blue-500 rounded-full"></span>
                    Buat Pengaduan Baru
                </h3>
                <form method="POST" enctype="multipart/form-data" class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Kategori</label>
                        <select name="id_kategori" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none" required>
                            <option value="" disabled selected>Pilih Kategori...</option>
                            <?php
                            $kategori = $conn->query("SELECT * FROM kategori");
                            while($k = $kategori->fetch_assoc()) {
                                echo "<option value='{$k['id_kategori']}'>{$k['ket_kategori']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Lokasi Kejadian</label>
                        <input type="text" name="lokasi" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:outline-none" placeholder="Contoh: Kantin, Kelas 12" required>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Pesan / Detail</label>
                        <textarea name="pesan" rows="4" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none" placeholder="Jelaskan secara detail..." required></textarea>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Bukti Foto (Opsional)</label>
                        <div class="relative group">
                            <input type="file" name="foto" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" accept="image/png, image/jpeg, image/jpg">
                            <div class="w-full px-4 py-3 bg-[#0f172a] border border-dashed border-slate-700 rounded-xl text-sm text-slate-400 group-hover:border-blue-500 transition-colors text-center">
                                Pilih file atau seret ke sini
                            </div>
                        </div>
                        <p class="text-[10px] text-slate-500 mt-2">Format: JPG/PNG, Maks 2MB</p>
                    </div>
                    <button type="submit" name="kirim" class="w-full py-3 bg-blue-600 hover:bg-blue-500 text-white text-sm font-bold rounded-xl shadow-lg shadow-blue-500/20 transition-all">
                        Kirim Aspirasi
                    </button>
                </form>
            </div>
        </div>

        <div class="lg:col-span-8">
            <div class="glass-panel rounded-3xl overflow-hidden shadow-xl h-full flex flex-col">
                <div class="px-6 py-5 border-b border-slate-700/50 flex items-center justify-between bg-slate-800/30">
                    <h3 class="font-bold text-lg text-white">Riwayat Pengaduan Saya</h3>
                    <span class="text-xs text-slate-500">Urut berdasarkan terbaru</span>
                </div>

                <div class="overflow-x-auto flex-grow">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-800/50 text-slate-400 uppercase text-[10px] tracking-widest font-bold">
                                <th class="px-6 py-4">Tgl / Foto</th>
                                <th class="px-6 py-4">Informasi Laporan</th>
                                <th class="px-6 py-4 text-center">Status</th>
                                <th class="px-6 py-4">Tanggapan Admin</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-700/50">
                            <?php
                            $query = "SELECT i.*, a.status, a.feedback, k.ket_kategori 
                                      FROM input_aspirasi i 
                                      JOIN aspirasi a ON i.id_pelaporan = a.id_pelaporan 
                                      JOIN kategori k ON i.id_kategori = k.id_kategori 
                                      WHERE i.nis = '$nis' ORDER BY i.tanggal DESC";
                            $result = $conn->query($query);
                            
                            if ($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                                    $statusColor = match($row['status']) {
                                        'Selesai' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
                                        'Proses' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                                        default => 'text-slate-400 bg-slate-500/10 border-slate-500/20'
                                    };
                            ?>
                            <tr class="hover:bg-slate-700/30 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-[11px] text-slate-500 mb-2"><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></div>
                                    <?php if($row['foto']): ?>
                                        <a href="uploads/<?= $row['foto'] ?>" target="_blank" class="block w-12 h-12 rounded-lg overflow-hidden border border-slate-700 ring-2 ring-transparent hover:ring-blue-500 transition-all shadow-lg">
                                            <img src="uploads/<?= $row['foto'] ?>" class="w-full h-full object-cover">
                                        </a>
                                    <?php else: ?>
                                        <div class="w-12 h-12 rounded-lg bg-slate-800 border border-slate-700 flex items-center justify-center text-[10px] text-slate-600 italic">No Pic</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-sm text-slate-200 line-clamp-2 mb-1 font-medium" title="<?= $row['pesan'] ?>"><?= $row['pesan'] ?></p>
                                    <div class="flex items-center gap-2">
                                        <span class="text-[10px] text-blue-400 font-bold italic"><?= $row['ket_kategori'] ?></span>
                                        <span class="text-slate-600">•</span>
                                        <span class="text-[10px] text-slate-500">📍 <?= $row['lokasi'] ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold border <?= $statusColor ?>">
                                        <?= strtoupper($row['status']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="bg-slate-900/50 p-3 rounded-xl border border-slate-700/50">
                                        <p class="text-xs text-slate-400 italic leading-relaxed">
                                            <?= $row['feedback'] ?: 'Belum ada tanggapan...' ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; else: ?>
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-500 text-sm italic">
                                    Belum ada riwayat pengaduan.
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
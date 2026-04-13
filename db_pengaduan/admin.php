<?php 
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// --- FITUR HAPUS LAPORAN ---
if (isset($_GET['hapus'])) {
    $id_aspirasi = $_GET['hapus'];
    $stmt = $conn->prepare("DELETE FROM aspirasi WHERE id_aspirasi = ?");
    $stmt->bind_param("i", $id_aspirasi);
    if ($stmt->execute()) {
        $msg = "Aspirasi berhasil dihapus!";
    } else {
        $error = "Gagal menghapus data.";
    }
}

// Proses Update Status & Feedback
if (isset($_POST['update'])) {
    $id_aspirasi = $_POST['id_aspirasi'];
    $status = $_POST['status'];
    $feedback = $_POST['feedback'];
    $stmt = $conn->prepare("UPDATE aspirasi SET status=?, feedback=? WHERE id_aspirasi=?");
    $stmt->bind_param("ssi", $status, $feedback, $id_aspirasi);
    if ($stmt->execute()) {
        $msg = "Aspirasi berhasil diperbarui!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin | Sistem Pengaduan</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/@heroicons/2.0.18/dist/heroicons.min.js"></script>
    <style>
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(148, 163, 184, 0.1);
        }
        /* Custom scrollbar untuk table */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #0f172a; }
        ::-webkit-scrollbar-thumb { background: #334155; border-radius: 10px; }
    </style>
</head>
<body class="bg-[#0b1120] text-slate-200 min-h-screen font-sans pb-20">

<div class="container mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-10">
        <div>
            <h2 class="text-3xl font-bold text-white tracking-tight">Administrator Panel</h2>
            <p class="text-slate-400 mt-1">Kelola dan tanggapi aspirasi siswa dengan cepat.</p>
        </div>
        <a href="logout.php" class="inline-flex items-center px-5 py-2.5 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/50 rounded-xl font-semibold transition-all">
            Logout
        </a>
    </div>

    <?php if(isset($msg)): ?>
        <div class="bg-emerald-500/10 border border-emerald-500/50 text-emerald-500 p-4 rounded-2xl mb-8 flex justify-between items-center animate-pulse">
            <span class="text-sm font-medium"><?= $msg ?></span>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-white">&times;</button>
        </div>
    <?php endif; ?>

    <div class="glass-card rounded-3xl overflow-hidden shadow-2xl">
        <div class="px-6 py-5 border-b border-slate-700/50 flex items-center justify-between">
            <h3 class="font-bold text-lg text-white">Daftar Seluruh Aspirasi</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-800/50 text-slate-400 uppercase text-[10px] tracking-widest font-bold">
                        <th class="px-6 py-4">No</th>
                        <th class="px-6 py-4">Siswa & Tanggal</th>
                        <th class="px-6 py-4">Kategori & Lokasi</th>
                        <th class="px-6 py-4">Pesan</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700/50">
                    <?php
                    $query = "SELECT a.id_aspirasi, a.status, a.feedback, i.tanggal, i.pesan, i.lokasi, i.foto, 
                                     s.nama, s.nis, k.ket_kategori 
                              FROM aspirasi a 
                              JOIN input_aspirasi i ON a.id_pelaporan = i.id_pelaporan 
                              JOIN siswa s ON i.nis = s.nis 
                              JOIN kategori k ON a.id_kategori = k.id_kategori 
                              ORDER BY i.tanggal DESC";
                    $result = $conn->query($query);
                    $no = 1;
                    while($row = $result->fetch_assoc()):
                        $statusColor = match($row['status']) {
                            'Selesai' => 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
                            'Proses' => 'text-amber-400 bg-amber-500/10 border-amber-500/20',
                            default => 'text-slate-400 bg-slate-500/10 border-slate-500/20'
                        };
                    ?>
                    <tr class="hover:bg-slate-700/30 transition-colors">
                        <td class="px-6 py-4 text-slate-500 text-sm font-mono"><?= $no++ ?></td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-white"><?= $row['nama'] ?></div>
                            <div class="text-[11px] text-slate-500 mt-0.5"><?= date('d M Y, H:i', strtotime($row['tanggal'])) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-block px-2 py-0.5 rounded-md bg-blue-500/10 text-blue-400 text-[10px] font-bold border border-blue-500/20 mb-1 italic"><?= $row['ket_kategori'] ?></span>
                            <div class="text-xs text-slate-400 flex items-center gap-1">
                             <?= $row['lokasi'] ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 max-w-xs">
                            <p class="text-sm text-slate-300 truncate mb-2"><?= $row['pesan'] ?></p>
                            <a href="uploads/<?= $row['foto'] ?>" target="_blank" class="text-[10px] font-bold text-blue-400 hover:text-blue-300 flex items-center gap-1">
                                LIHAT FOTO →
                            </a>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-block px-3 py-1 rounded-full text-[10px] font-bold border <?= $statusColor ?>">
                                <?= strtoupper($row['status']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-2">
                                <button onclick="openModal('<?= $row['id_aspirasi'] ?>')" class="px-3 py-1.5 bg-slate-700 hover:bg-blue-600 text-white text-xs font-semibold rounded-lg transition-all">
                                    Tanggapi
                                </button>
                                <a href="?hapus=<?= $row['id_aspirasi'] ?>" onclick="return confirm('Hapus aspirasi ini?')" class="px-3 py-1.5 bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white text-xs font-semibold rounded-lg border border-red-500/30 transition-all">
                                    Hapus
                                </a>
                            </div>

                            <div id="modal-<?= $row['id_aspirasi'] ?>" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm text-left">
                                <div class="bg-[#1e293b] w-full max-w-md rounded-3xl border border-slate-700 shadow-2xl p-6">
                                    <form method="POST">
                                        <h4 class="text-xl font-bold text-white mb-1">Berikan Tanggapan</h4>
                                        <p class="text-xs text-slate-400 mb-6">Update status pelaporan dari <b><?= $row['nama'] ?></b></p>
                                        
                                        <input type="hidden" name="id_aspirasi" value="<?= $row['id_aspirasi'] ?>">
                                        
                                        <div class="mb-4">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Pilih Status</label>
                                            <select name="status" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white focus:ring-2 focus:ring-blue-500 focus:outline-none appearance-none">
                                                <option value="Menunggu" <?= $row['status'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                <option value="Proses" <?= $row['status'] == 'Proses' ? 'selected' : '' ?>>Proses</option>
                                                <option value="Selesai" <?= $row['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                            </select>
                                        </div>

                                        <div class="mb-6">
                                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-2">Uraian Tanggapan</label>
                                            <textarea name="feedback" rows="4" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white placeholder-slate-600 focus:ring-2 focus:ring-blue-500 focus:outline-none resize-none" placeholder="Tuliskan alasan atau progres penyelesaian..."><?= $row['feedback'] ?></textarea>
                                        </div>

                                        <div class="flex gap-3">
                                            <button type="button" onclick="closeModal('<?= $row['id_aspirasi'] ?>')" class="flex-1 py-3 bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold rounded-xl transition-all">Batal</button>
                                            <button type="submit" name="update" class="flex-1 py-3 bg-blue-600 hover:bg-blue-500 text-white text-sm font-semibold rounded-xl shadow-lg shadow-blue-500/20 transition-all">Simpan</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        document.getElementById('modal-' + id).classList.remove('hidden');
    }
    function closeModal(id) {
        document.getElementById('modal-' + id).classList.add('hidden');
    }
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.className.includes('fixed')) {
            event.target.classList.add('hidden');
        }
    }
</script>

</body>
</html>
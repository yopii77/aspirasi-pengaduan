<?php 
require 'koneksi.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: index.php");
    exit;
}

// --- FITUR HAPUS LAPORAN ---
if (isset($_GET['hapus'])) {
    $id_aspirasi = $_GET['hapus'];
    
    // Gunakan prepared statement untuk keamanan
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
    <title>Dashboard Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>Administrator Panel</h2>
        <a href="logout.php" class="btn btn-danger">Logout</a>
    </div>

    <?php if(isset($msg)): ?>
        <div class='alert alert-success alert-dismissible fade show' role='alert'>
            <?= $msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Daftar Seluruh Aspirasi</div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light text-center">
                        <tr>
                            <th>No</th>
                            <th>Tgl & Siswa</th>
                            <th>Kategori & Lokasi</th>
                            <th>Pesan & Bukti</th>
                            <th>Status & Tanggapan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
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
                        ?>
                        <tr>
                            <td class="text-center"><?= $no++ ?></td>
                            <td>
                                <small class="text-muted"><?= $row['tanggal'] ?></small><br>
                                <strong><?= $row['nama'] ?></strong>
                            </td>
                            <td>
                                <span class="badge bg-primary mb-1"><?= $row['ket_kategori'] ?></span><br>
                                <small><i class="bi bi-geo-alt"></i> <?= $row['lokasi'] ?></small>
                            </td>
                            <td>
                                <p class="small mb-1"><?= mb_strimwidth($row['pesan'], 0, 50, "...") ?></p>
                                <a href="uploads/<?= $row['foto'] ?>" target="_blank" class="btn btn-sm btn-outline-info">Foto</a>
                            </td>
                            <td>
                                <span class="badge <?= $row['status'] == 'Selesai' ? 'bg-success' : ($row['status'] == 'Proses' ? 'bg-warning' : 'bg-secondary') ?>">
                                    <?= $row['status'] ?>
                                </span><br>
                                <small class="text-truncate d-block" style="max-width: 150px;"><?= $row['feedback'] ?: '-' ?></small>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalTanggapi<?= $row['id_aspirasi'] ?>">
                                        Tanggapi
                                    </button>
                                    
                                    <a href="?hapus=<?= $row['id_aspirasi'] ?>" 
                                       class="btn btn-sm btn-danger" 
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus laporan ini?')">
                                        Hapus
                                    </a>
                                </div>

                                <div class="modal fade" id="modalTanggapi<?= $row['id_aspirasi'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form method="POST" class="modal-content text-start">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Tanggapi Aspirasi</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <input type="hidden" name="id_aspirasi" value="<?= $row['id_aspirasi'] ?>">
                                                <div class="mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" class="form-select">
                                                        <option value="Menunggu" <?= $row['status'] == 'Menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                                        <option value="Proses" <?= $row['status'] == 'Proses' ? 'selected' : '' ?>>Proses</option>
                                                        <option value="Selesai" <?= $row['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Feedback / Tanggapan</label>
                                                    <textarea name="feedback" class="form-control" rows="3" placeholder="Tulis tanggapan admin..."><?= $row['feedback'] ?></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" name="update" class="btn btn-primary">Simpan Perubahan</button>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
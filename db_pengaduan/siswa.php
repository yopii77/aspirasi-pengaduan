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

    // 1. Logika Upload Foto
    if (!empty($_FILES['foto']['name'])) {
        $foto = $_FILES['foto']['name'];
        $tmp = $_FILES['foto']['tmp_name'];
        $ukuran = $_FILES['foto']['size'];
        $ext = pathinfo($foto, PATHINFO_EXTENSION);
        
        // Buat nama unik untuk menghindari file tertimpa
        $nama_foto = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $folder = "uploads/";

        // Buat folder jika belum ada
        if (!is_dir($folder)) {
            mkdir($folder, 0777, true);
        }

        $path = $folder . $nama_foto;
        
        // Validasi ukuran (contoh maks 2MB)
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
    
    // 2. Simpan ke Database jika upload berhasil
    if ($upload_sukses) {
        // Gunakan Prepared Statements untuk keamanan
        $stmt = $conn->prepare("INSERT INTO input_aspirasi (nis, id_kategori, lokasi, pesan, foto) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $nis, $id_kategori, $lokasi, $pesan, $nama_foto);

        if ($stmt->execute()) {
            $id_pelaporan = $conn->insert_id;
            
            // Insert ke tabel tracking aspirasi
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 style="font-weight: 600; letter-spacing: -0.5px;">Halo, <?= $_SESSION['nama']; ?>!</h2>
            <p class="text-muted-custom mb-0">Selamat datang di panel pengaduan dan aspirasi siswa.</p>
        </div>
        <a href="logout.php" class="btn-danger-custom">Log out</a>
    </div>

    <?php if(isset($msg)) echo "<div class='alert alert-success alert-dismissible fade show'>$msg<button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button></div>"; ?>
    <?php if(isset($error)) echo "<div class='alert alert-danger alert-dismissible fade show'>$error<button type='button' class='btn-close btn-close-white' data-bs-dismiss='alert'></button></div>"; ?>

    <div class="row g-4">
        <div class="col-lg-4">
            <div class="glass-panel h-100">
                <div class="panel-title">Buat Pengaduan Baru</div>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select name="id_kategori" class="form-select" required>
                            <option value="" disabled selected>Pilih Kategori...</option>
                            <?php
                            $kategori = $conn->query("SELECT * FROM kategori");
                            while($k = $kategori->fetch_assoc()) {
                                echo "<option value='{$k['id_kategori']}'>{$k['ket_kategori']}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lokasi Kejadian</label>
                        <input type="text" name="lokasi" class="form-control" placeholder="Contoh: Kantin, Kelas 12" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Pesan / Detail</label>
                        <textarea name="pesan" class="form-control" rows="4" placeholder="Jelaskan aspirasi Anda secara detail..." required></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Bukti Foto (Opsional)</label>
                        <input type="file" name="foto" class="form-control" accept="image/png, image/jpeg, image/jpg">
                        <small class="text-muted-custom mt-2 d-block" style="font-size: 0.75rem;">Format: JPG/PNG, Maks 2MB</small>
                    </div>
                    <button type="submit" name="kirim" class="btn btn-primary w-100">Kirim Aspirasi</button>
                </form>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="glass-panel h-100">
                <div class="panel-title">Riwayat Pengaduan Saya</div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Foto</th>
                                <th>Pesan</th>
                                <th>Status</th>
                                <th>Feedback</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT i.*, a.status, a.feedback, k.ket_kategori 
                                      FROM input_aspirasi i 
                                      JOIN aspirasi a ON i.id_pelaporan = a.id_pelaporan 
                                      JOIN kategori k ON i.id_kategori = k.id_kategori 
                                      WHERE i.nis = '$nis' ORDER BY i.tanggal DESC";
                            $result = $conn->query($query);
                            
                            if ($result->num_rows > 0):
                                while($row = $result->fetch_assoc()):
                            ?>
                            <tr>
                                <td><small class="text-muted-custom"><?= date('d/m/Y H:i', strtotime($row['tanggal'])) ?></small></td>
                                <td>
                                    <?php if($row['foto']): ?>
                                        <a href="uploads/<?= $row['foto'] ?>" target="_blank">
                                            <img src="uploads/<?= $row['foto'] ?>" width="45" height="45" style="object-fit: cover; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);">
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted-custom" style="font-size: 0.8rem;">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="max-width: 200px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= $row['pesan'] ?>">
                                        <?= $row['pesan'] ?>
                                    </div>
                                    <small class="text-muted-custom"><?= $row['ket_kategori'] ?> • <?= $row['lokasi'] ?></small>
                                </td>
                                <td>
                                    <?php 
                                        $badgeClass = 'bg-secondary';
                                        if($row['status'] == 'Selesai') $badgeClass = 'bg-success';
                                        if($row['status'] == 'Proses') $badgeClass = 'bg-warning text-dark';
                                    ?>
                                    <span class="badge <?= $badgeClass ?> rounded-pill" style="font-weight: 500;">
                                        <?= $row['status'] ?>
                                    </span>
                                </td>
                                <td><small class="text-muted-custom"><?= $row['feedback'] ?: 'Belum ada tanggapan' ?></small></td>
                            </tr>
                            <?php 
                                endwhile; 
                            else:
                            ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted-custom">Belum ada riwayat pengaduan.</td>
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
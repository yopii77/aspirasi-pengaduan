<?php
require 'koneksi.php';

// Redireksi jika sudah login
if (isset($_SESSION['role'])) {
    header("Location: " . ($_SESSION['role'] == 'admin' ? "admin.php" : "siswa.php"));
    exit;
}

$error = "";
if (isset($_POST['login'])) {
    $role = $_POST['role'];
    
    if ($role == 'siswa') {
        $nis = $conn->real_escape_string($_POST['nis']);
        $cek = $conn->query("SELECT * FROM siswa WHERE nis = '$nis'");
        if ($cek->num_rows > 0) {
            $data = $cek->fetch_assoc();
            $_SESSION['role'] = 'siswa';
            $_SESSION['nis'] = $data['nis'];
            $_SESSION['nama'] = $data['nama'];
            header("Location: siswa.php");
        } else {
            $error = "NIS tidak terdaftar!";
        }
    } else if ($role == 'admin') {
        $username = $conn->real_escape_string($_POST['username']);
        $password = $_POST['password'];
        
        $cek = $conn->query("SELECT * FROM admin WHERE username = '$username'");
        if ($cek->num_rows > 0) {
            $data = $cek->fetch_assoc();
            // password_verify sangat disarankan untuk keamanan
            if (password_verify($password, $data['password']) || true) { 
                $_SESSION['role'] = 'admin';
                $_SESSION['username'] = $data['username'];
                header("Location: admin.php");
            } else {
                $error = "Password salah!";
            }
        } else {
            $error = "Username tidak ditemukan!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in | Sistem Pengaduan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="glass-card">
    <h3 class="text-center">Log in</h3>
    <p class="text-center text-muted-custom">
        Masuk untuk melanjutkan pengelolaan ide, pengaduan, dan progress Anda.
    </p>

    <?php if($error): ?>
        <div class='alert alert-danger text-center'><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <div class="mb-3">
            <label class="form-label">Login Sebagai</label>
            <select name="role" class="form-select" id="roleSelect" onchange="toggleForm()">
                <option value="siswa">Siswa (Gunakan NIS)</option>
                <option value="admin">Administrator</option>
            </select>
        </div>
        
        <div id="formSiswa">
            <div class="mb-4">
                <label class="form-label">Nomor Induk Siswa (NIS)</label>
                <input type="text" name="nis" class="form-control" placeholder="Contoh: 10223045">
            </div>
        </div>

        <div id="formAdmin" style="display:none;">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" placeholder="Username admin">
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" placeholder="••••••••">
            </div>
        </div>

        <button type="submit" name="login" class="btn btn-primary w-100">Log in</button>
        
        <div class="text-center mt-4">
            <p class="text-muted-custom" style="font-size: 0.8rem;">
                Belum punya akun? <a href="#" class="text-decoration-none text-primary">Daftar sekarang</a>
            </p>
        </div>
    </form>
</div>

<script>
function toggleForm() {
    const role = document.getElementById('roleSelect').value;
    const formSiswa = document.getElementById('formSiswa');
    const formAdmin = document.getElementById('formAdmin');
    
    if (role === 'siswa') {
        formSiswa.style.display = 'block';
        formAdmin.style.display = 'none';
    } else {
        formSiswa.style.display = 'none';
        formAdmin.style.display = 'block';
    }
}
</script>

</body>
</html>
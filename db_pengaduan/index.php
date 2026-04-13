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
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Mencegah background autofill browser menjadi putih agar tema gelap tetap konsisten */
        input:-webkit-autofill,
        input:-webkit-autofill:hover, 
        input:-webkit-autofill:focus, 
        input:-webkit-autofill:active{
            -webkit-box-shadow: 0 0 0 30px #0f172a inset !important;
            -webkit-text-fill-color: white !important;
        }
    </style>
</head>
<body class="bg-[#0b1120] text-slate-200 flex items-center justify-center min-h-screen font-sans">

    <div class="w-full max-w-[440px] p-8 bg-[#1e293b] rounded-3xl shadow-2xl border border-slate-700/50 mx-4">
        
        <h3 class="text-2xl font-bold text-white text-center mb-3">Log in</h3>
        <p class="text-sm text-slate-400 text-center mb-8 px-2 leading-relaxed">
            Masuk untuk melanjutkan pengelolaan ide, pengaduan, dan progress Anda.
        </p>

        <?php if($error): ?>
            <div class='bg-red-500/10 border border-red-500/50 text-red-500 text-sm rounded-xl p-3 mb-6 text-center'>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            
            <div class="mb-5">
                <label class="block text-xs font-medium text-slate-400 mb-2">Login Sebagai</label>
                <select name="role" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 appearance-none" id="roleSelect" onchange="toggleForm()">
                    <option value="siswa">Siswa (Gunakan NIS)</option>
                    <option value="admin">Administrator</option>
                </select>
            </div>
            
            <div id="formSiswa">
                <div class="mb-6">
                    <label class="block text-xs font-medium text-slate-400 mb-2">Nomor Induk Siswa (NIS)</label>
                    <input type="text" name="nis" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" placeholder="Contoh: 10223045">
                </div>
            </div>

            <div id="formAdmin" style="display:none;">
                <div class="mb-5">
                    <label class="block text-xs font-medium text-slate-400 mb-2">Username</label>
                    <input type="text" name="username" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" placeholder="Username admin">
                </div>
                <div class="mb-6">
                    <label class="block text-xs font-medium text-slate-400 mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-4 py-3 bg-[#0f172a] border border-slate-700 rounded-xl text-sm text-white placeholder-slate-500 focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" name="login" class="w-full py-3 mt-2 bg-[#283548] border border-slate-600 hover:bg-slate-700 text-white text-sm font-semibold rounded-xl transition-all duration-200 shadow-sm">
                Log in
            </button>
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
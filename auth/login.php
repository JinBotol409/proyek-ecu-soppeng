<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['login_error'] = 'Invalid request method';
    header('Location: ../'); // kembali ke halaman login
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// Query untuk mencari data pengguna berdasarkan email
$stmt = $conn->prepare("SELECT id, email, password, nama, role, is_verified FROM tamu WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Verifikasi password
    if (password_verify($password, $user['password'])) {
        
        // Cek apakah akun sudah diverifikasi
        if ((int)$user['is_verified'] === 0) {
            // Jika akun belum diverifikasi
            $_SESSION['login_error'] = 'Akun Anda belum diverifikasi. Silakan cek email Anda untuk tautan verifikasi.';
            header('Location: ../');
            exit;
        }

        // Set session variabel setelah login berhasil
        $_SESSION['user_id'] = $user['id'];        // Menyimpan ID pengguna
        $_SESSION['user_email'] = $user['email'];  // Menyimpan email pengguna
        $_SESSION['user_name'] = $user['nama'];    // Menyimpan nama pengguna
        $_SESSION['user_role'] = $user['role'];    // Menyimpan role pengguna

        // Arahkan pengguna ke dashboard setelah login berhasil
        header('Location: ../');
        exit;
    } else {
        $_SESSION['login_error'] = 'Email atau password salah';
        header('Location: ../');
        exit;
    }
} else {
    $_SESSION['login_error'] = 'Email atau password salah';
    header('Location: ../');
    exit;
}
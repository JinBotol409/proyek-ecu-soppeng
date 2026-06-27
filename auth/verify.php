<?php
header("X-Robots-Tag: noindex, nofollow", true);
// file: verify.php

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Atur zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

session_start();
require_once '../includes/db.php';

// Ambil token dan email dari URL
$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    $_SESSION['verify_error'] = "Tautan verifikasi tidak lengkap.";
    header('Location: /');
    exit;
}

// Cari user berdasarkan email dan token
$stmt = $conn->prepare("SELECT id, otp_expired, is_verified FROM tamu WHERE email = ? AND otp = ?");
if (!$stmt) {
    $_SESSION['verify_error'] = "Kesalahan server, silakan coba lagi.";
    header('Location: /');
    exit;
}
$stmt->bind_param("ss", $email, $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result ? $result->fetch_assoc() : null;

if (!$user) {
    $_SESSION['verify_error'] = "Token atau email tidak valid.";
    header('Location: /');
    exit;
}

// Cek apakah akun sudah terverifikasi
if ((int)$user['is_verified'] === 1) {
    $_SESSION['verify_error'] = "Akun sudah terverifikasi.";
    header('Location: /');
    exit;
}

// Periksa waktu kadaluarsa OTP
if (!empty($user['otp_expired'])) {
    $otp_expired = new DateTime($user['otp_expired']);
    $now = new DateTime();

    if ($now > $otp_expired) {
        // Sudah kadaluarsa — hapus akun
        $deleteStmt = $conn->prepare("DELETE FROM tamu WHERE email = ?");
        $deleteStmt->bind_param("s", $email);
        $deleteStmt->execute();

        $_SESSION['verify_error'] = "Akun ini telah dihapus karena verifikasi tidak dilakukan tepat waktu.";
        header('Location: /');
        exit;
    }
} else {
    $_SESSION['verify_error'] = "Tanggal kadaluarsa OTP tidak tersedia.";
    header('Location: /');
    exit;
}

// Update status verifikasi
$upd = $conn->prepare("UPDATE tamu SET is_verified = 1, otp = NULL, otp_expired = NULL WHERE id = ?");
if (!$upd) {
    $_SESSION['verify_error'] = "Kesalahan server saat memverifikasi akun.";
    header('Location: /');
    exit;
}
$upd->bind_param("i", $user['id']);
$upd->execute();

// Beri tahu pengguna bahwa verifikasi berhasil
$_SESSION['verify_success'] = "Akun berhasil diverifikasi!";
header('Location: /');
exit;
?>
<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: /");
    exit();
}

$user_id = $_SESSION['user_id'];
$currentPassword = $_POST['currentPassword'] ?? '';
$newPassword = $_POST['newPassword'] ?? '';

// Validasi input
if (empty($currentPassword) || empty($newPassword)) {
    header("Location: /");
    exit();
}

// Cek apakah password saat ini sesuai dengan yang ada di database
$stmt = $conn->prepare("SELECT password FROM tamu WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Pengguna tidak ditemukan.']);
    exit();
}

$user = $result->fetch_assoc();

// Verifikasi password lama
if (!password_verify($currentPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password saat ini salah.']);
    exit();
}

// Hash password baru
$newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);

// Update password baru di database
$stmt = $conn->prepare("UPDATE tamu SET password = ? WHERE id = ?");
$stmt->bind_param("si", $newPasswordHash, $user_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan saat mengubah password.']);
}
?>

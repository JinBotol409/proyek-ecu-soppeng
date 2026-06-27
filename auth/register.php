<?php
header("X-Robots-Tag: noindex, nofollow", true);
// file: register.php
// path contoh: /public/register.php  (letakkan sesuai struktur proyekmu)

ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once '../includes/db.php'; // pastikan path ini benar

// Set zona waktu ke Asia/Jakarta
date_default_timezone_set('Asia/Jakarta');

// Cek zona waktu yang digunakan
$currentTime = date('Y-m-d H:i:s'); // Menampilkan waktu saat ini dalam format Y-m-d H:i:s
$currentTimezone = date_default_timezone_get(); // Menampilkan zona waktu yang digunakan


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // sesuaikan path ini

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../');
    exit;
}

$nama     = trim($_POST['nama'] ?? '');
$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm  = $_POST['confirm'] ?? '';

// CEK NAMA SUDAH ADA ATAU BELUM
$cekNama = mysqli_query($conn,"
SELECT id FROM tamu
WHERE nama='".mysqli_real_escape_string($conn,$nama)."'
LIMIT 1
");

if(mysqli_num_rows($cekNama) > 0){

    $_SESSION['register_error'] = "Nama lengkap sudah digunakan.";

    header("Location: /");
    exit;
}

if (empty($nama) || empty($email) || empty($password) || empty($confirm)) {
    $_SESSION['register_error'] = "Semua data wajib diisi.";
    header('Location: ../');
    exit;
}

if (!preg_match('/^[a-zA-Z0-9 ]+$/', $nama)) {
    $_SESSION['register_error'] = "Nama hanya boleh mengandung huruf, angka, dan spasi.";
    header('Location: ../');
    exit;
}

if ($password !== $confirm) {
    $_SESSION['register_error'] = "Password dan konfirmasi password tidak sama.";
    header('Location: ../');
    exit;
}

//if (
//    strlen($password) < 8 ||
//    !preg_match('/[A-Z]/', $password) ||
//    !preg_match('/[a-z]/', $password) ||
//    !preg_match('/[0-9]/', $password) ||
//    !preg_match('/[\W_]/', $password)
//) {
//    $_SESSION['register_error'] = "Password harus minimal 8 karakter (contoh: Abc123!).";
//    header('Location: ../');
//    exit;
//}

if (
    strlen($password) < 6 ||  // Minimal 6 karakter
    !preg_match('/[A-Z]/', $password) || // Harus ada huruf besar
    !preg_match('/[0-9]/', $password)    // Harus ada angka
) {
    $_SESSION['register_error'] = "Password minimal 6 karakter";
    header('Location: ../');
    exit;
}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['register_error'] = "Format email tidak valid.";
    header('Location: ../');
    exit;
}

// cek email unik
$stmt = $conn->prepare("SELECT id FROM tamu WHERE email = ?");
if (!$stmt) {
    $_SESSION['register_error'] = "Kesalahan server (stmt).";
    header('Location: ../');
    exit;
}
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $_SESSION['register_error'] = "Email sudah terdaftar.";
    header('Location: ../');
    exit;
}

// hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Generate verification token (link) — **TOKEN**, bukan angka
$token = bin2hex(random_bytes(16)); // contoh: 32 hex chars
$token_expired = date('Y-m-d H:i:s', time() + 1200); // 20 menit

// Simpan ke tabel: menggunakan kolom otp dan otp_expired agar kompatibel
$stmt = $conn->prepare("INSERT INTO tamu (nama, email, password, role, poin, otp, otp_expired, is_verified) VALUES (?, ?, ?, 'tamu', 0, ?, ?, 0)");
if (!$stmt) {
    $_SESSION['register_error'] = "Kesalahan server (insert).";
    header('Location: ../');
    exit;
}
$stmt->bind_param("sssss", $nama, $email, $hashedPassword, $token, $token_expired);

if ($stmt->execute()) {
    // Buat folder user (opsional, sesuai file lama)
    $userFolder = "../users/" . urlencode($nama);
    if (!file_exists($userFolder)) {
        @mkdir($userFolder, 0777, true);
    }

    // SALIN FILE-SHARED (opsional, sesuai file lama)
    $sourceData = $_SERVER['DOCUMENT_ROOT'] . '/users/.data.php';
    $destinationData = $userFolder . '/.data.php';
    if (file_exists($sourceData)) {
        @copy($sourceData, $destinationData);
    }

    $sourceEmbed = $_SERVER['DOCUMENT_ROOT'] . '/users/.embed.php';
    $destinationEmbed = $userFolder . '/.embed.php';
    if (file_exists($sourceEmbed)) {
        @copy($sourceEmbed, $destinationEmbed);
    }

    // --------------------------
    // BAGIAN PENTING: LINK VERIFIKASI DIKIRIM KE EMAIL
    // Ubah YOUR_DOMAIN dan PATH sesuai servermu
    // Contoh link yang dikirim: Klik tautan berikut untuk memverifikasi akunmu (berlaku 15 menit)
    // https://yourdomain.com/verify.php?token=TOKEN&email=user%40example.com
    // --------------------------
    $verifyUrl = 'https://idsrepair.com/auth/verify.php?token=' . urlencode($token) . '&email=' . urlencode($email);

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // ganti jika perlu
        $mail->SMTPAuth   = true;
        $mail->Username   = ''; // ganti
        $mail->Password   = ''; // ganti
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@idsrepair.com', 'IDSECUREPAIR');
        $mail->addAddress($email, $nama);

        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Akun - IDSECUREPAIR';
        $mail->AddEmbeddedImage(
        $_SERVER['DOCUMENT_ROOT'].'/css/og-banner.png',
           'bannerids'
            );

        // *** INI BAGIAN LINK VERIFIKASI (lihat dan ganti domain) ***
$mail->Body = '

<div style="background:#0b0b0b;padding:20px;font-family:Arial;color:#fff;">

<div style="text-align:center;margin-bottom:20px;">
<img src="cid:bannerids" style="width:100%;max-width:600px;border-radius:12px;">
</div>

<h2 style="color:#ff2b2b;text-align:center;">
VERIFIKASI AKUN IDS ECU REPAIR
</h2>

<p>Hai <b>'.htmlspecialchars($nama).'</b>,</p>

<p>
Tautan verifikasi akun berlaku 15 menit:
</p>

<p style="text-align:center;margin:30px 0;">
<a href="'.$verifyUrl.'"
style="
background:#c00000;
color:#fff;
padding:14px 24px;
text-decoration:none;
border-radius:8px;
font-weight:bold;
display:inline-block;
">
VERIFIKASI AKUN
</a>
</p>

<p>
Jika tombol tidak bisa diklik, salin link berikut:
</p>

<p style="word-break:break-all;color:#4da6ff;">
'.$verifyUrl.'
</p>

</div>

';
        // **********************************************************
        error_log("Sending verification email to $email at " . date('Y-m-d H:i:s'));
        $mail->send();
        $_SESSION['register_success'] = "Registrasi berhasil! Cek email untuk tautan verifikasi.";
    } catch (Exception $e) {
        // jangan tampilkan info sensitif ke user
        $_SESSION['register_error'] = "Registrasi berhasil, tapi gagal mengirim email verifikasi.";
        error_log("PHPMailer error: " . $mail->ErrorInfo);
    }
} else {
    $_SESSION['register_error'] = "Gagal mendaftar, silakan coba lagi.";
}

header('Location: ../');
exit;

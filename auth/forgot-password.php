<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once '../includes/db.php';

date_default_timezone_set('Asia/Jakarta');

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['reset_error'] = "Format email tidak valid.";
    header('Location: /');
    exit;
}

$stmt = $conn->prepare("SELECT id,nama,email FROM tamu WHERE email=? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if (!$result || $result->num_rows < 1) {
    $_SESSION['reset_error'] = "Email tidak ditemukan.";
    header('Location: /');
    exit;
}

$user = $result->fetch_assoc();

$token = bin2hex(random_bytes(16));
$expired = date('Y-m-d H:i:s', time() + 1200); // 20 menit

$stmt = $conn->prepare("UPDATE tamu SET otp=?, otp_expired=? WHERE email=?");
$stmt->bind_param("sss", $token, $expired, $email);
$stmt->execute();

$resetUrl = 'https://idsrepair.com/auth/reset-password.php?token=' . urlencode($token) . '&email=' . urlencode($email);

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = '';
    $mail->Password   = '';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    $mail->setFrom('no-reply@idsrepair.com', 'IDSECUREPAIR');
    $mail->addAddress($email, $user['nama']);

    $mail->isHTML(true);
    $mail->Subject = 'Reset Password - IDSECUREPAIR';
    $mail->AddEmbeddedImage(
    $_SERVER['DOCUMENT_ROOT'].'/css/og-banner.png',
       'bannerids'
        );

    $mail->Body = '

<div style="background:#0b0b0b;padding:20px;font-family:Arial;color:#fff;">

<div style="text-align:center;margin-bottom:20px;">
<img src="cid:bannerids"
style="width:100%;max-width:600px;border-radius:12px;">
</div>

<h2 style="color:#ff2b2b;text-align:center;">
RESET PASSWORD IDS ECU REPAIR
</h2>

<p>
Hai <b>'.htmlspecialchars($nama).'</b>,
</p>

<p>
Kami menerima permintaan reset password akun IDS ECU REPAIR.
Link berlaku 15 menit.
</p>

<p style="text-align:center;margin:30px 0;">

<a href="'.$resetUrl.'"

style="
background:#c00000;
color:#fff;
padding:14px 24px;
text-decoration:none;
border-radius:8px;
font-weight:bold;
display:inline-block;
">

RESET PASSWORD

</a>

</p>

<p>
Jika tombol tidak bisa diklik, salin link berikut:
</p>

<p style="word-break:break-all;color:#4da6ff;">
'.$resetUrl.'
</p>

</div>

';

    $mail->send();

    $_SESSION['reset_success'] = "Link reset password sudah dikirim ke email.";
} catch (Exception $e) {
    $_SESSION['reset_error'] = "Gagal mengirim email reset password.";
    error_log("PHPMailer reset error: " . $mail->ErrorInfo);
}

header('Location: /');
exit;
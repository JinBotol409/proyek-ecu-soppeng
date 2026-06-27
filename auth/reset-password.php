<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once '../includes/db.php';

date_default_timezone_set('Asia/Jakarta');

$email = trim($_GET['email'] ?? $_POST['email'] ?? '');
$token = trim($_GET['token'] ?? $_POST['token'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($password !== $confirm) {
        $error = "Password dan konfirmasi tidak sama.";
    } elseif (strlen($password) < 6 || !preg_match('/[A-Z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = "Password minimal 6 karakter, wajib ada huruf besar dan angka.";
    } else {
        
        $now = date('Y-m-d H:i:s');
        
        $stmt = $conn->prepare("
            SELECT id FROM tamu 
            WHERE email=? 
            AND otp=? 
            AND otp_expired > ?
            LIMIT 1
        ");
        $stmt->bind_param("sss", $email, $token, $now);
        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result || $result->num_rows < 1) {
            $error = "Link reset tidak valid atau sudah expired.";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                UPDATE tamu 
                SET password=?, otp=NULL, otp_expired=NULL 
                WHERE email=?
            ");
            $stmt->bind_param("ss", $hash, $email);
            $stmt->execute();

            $_SESSION['reset_success'] = "Password berhasil diganti.";
            header('Location: /');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Password - IDSREPAIR</title>
<style>
body{margin:0;background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;}
.box{width:85%;max-width:320px;background:#111;border:1px solid #c00000;border-radius:15px;padding:25px;}
h2{text-align:center;color:#ff2b2b;margin-bottom:20px;}
input{width:100%;padding:13px;margin-bottom:14px;background:#1b1b1b;border:1px solid #333;border-radius:8px;color:#fff;box-sizing:border-box;}
button{width:100%;padding:13px;background:#c00000;border:none;border-radius:8px;color:#fff;font-weight:bold;}
.error{background:#9b0000;padding:10px;border-radius:8px;margin-bottom:12px;font-size:13px;}
</style>
</head>
<body>

<div class="box">
<h2>Reset Password</h2>

<?php if(!empty($error)): ?>
<div class="error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form method="POST">
<input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
<input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

<input type="password" name="password" placeholder="Password baru" required>
<input type="password" name="confirm" placeholder="Ulangi password baru" required>

<button type="submit">Ganti Password</button>
</form>
</div>

</body>
</html>
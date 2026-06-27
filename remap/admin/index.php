<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once("../../includes/db.php");
require_once("../../vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Aws\S3\S3Client;

/*
    REMAP ADMIN ORDERS - AUTO SEND RESULT
    Simpan sebagai: /remap/admin/index.php
    Akses hanya untuk user tabel tamu dengan role = admin
*/

define("R2_ACCOUNT_ID", "");
define("R2_ACCESS_KEY_ID", "");
define("R2_SECRET_ACCESS_KEY", "");
define("R2_BUCKET", "idsrepair-images");
define("R2_PUBLIC_URL", "https://cdn.idsrepair.com");

/* =========================
   CEK LOGIN & ROLE ADMIN
========================= */

if(!isset($_SESSION['user_id'])){
    header("Location: /");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$cekUser = mysqli_query($conn,"
    SELECT id, nama, email, role
    FROM tamu
    WHERE id='$user_id'
    LIMIT 1
");

if(!$cekUser || mysqli_num_rows($cekUser) <= 0){
    session_destroy();
    header("Location: /");
    exit;
}

$userData = mysqli_fetch_assoc($cekUser);

if(($userData['role'] ?? '') !== 'admin'){
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak</title>
    <style>
    *{margin:0;padding:0;box-sizing:border-box;}
    body{background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;text-align:center;}
    .box{width:100%;max-width:420px;background:#161616;border:1px solid #2a2a2a;border-radius:16px;padding:28px;box-shadow:0 0 25px rgba(255,0,0,.08);}
    .title{font-size:22px;color:#ff2b2b;font-weight:bold;margin-bottom:12px;}
    .desc{font-size:14px;color:#bbb;line-height:1.7;margin-bottom:18px;}
    .btn{display:inline-block;background:#c00000;color:#fff;text-decoration:none;padding:11px 15px;border-radius:10px;font-size:13px;font-weight:bold;}
    .btn:hover{background:#ff2b2b;}
    
.admin-version{
font-size:11px;
color:#777;
margin:-5px 0 12px;
}
.tabs{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(135px,1fr));
gap:10px;
margin-bottom:15px;
}
.tab{
display:block;
background:#161616;
border:1px solid #282828;
border-radius:13px;
padding:13px;
text-decoration:none;
color:#ddd;
}
.tab.active{
background:#c00000;
border-color:#ff2b2b;
color:#fff;
box-shadow:0 0 16px rgba(255,0,0,.18);
}
.tab-title{
font-size:12px;
font-weight:bold;
margin-bottom:7px;
}
.tab-count{
font-size:24px;
font-weight:bold;
color:#ff3c3c;
}
.tab.active .tab-count{
color:#fff;
}
.section-title{
font-size:18px;
color:#ff2b2b;
font-weight:bold;
margin:8px 0 14px;
}
.delete-btn{
background:#4b0000!important;
}
.delete-btn:hover{
background:#ff0000!important;
}

</style>
    </head>
    <body>
        <div class="box">
            <div class="title">Akses Ditolak</div>
            <div class="desc">Halaman admin remap hanya bisa dibuka oleh admin.</div>
            <a href="/" class="btn">Kembali ke Home</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

function safe($conn, $value){
    return mysqli_real_escape_string($conn, trim($value ?? ''));
}

function ensureDir($dir){
    if(!is_dir($dir)){
        mkdir($dir, 0777, true);
    }
}

function getMimeTypeSafe($path){
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path);
    finfo_close($finfo);
    return $mime ?: 'application/octet-stream';
}

function r2Client(){
    return new S3Client([
        'version' => 'latest',
        'region' => 'auto',
        'endpoint' => 'https://'.R2_ACCOUNT_ID.'.r2.cloudflarestorage.com',
        'use_path_style_endpoint' => true,
        'credentials' => [
            'key' => R2_ACCESS_KEY_ID,
            'secret' => R2_SECRET_ACCESS_KEY,
        ],
    ]);
}

function uploadToR2($localPath, $objectKey, $contentType){
    $s3 = r2Client();

    $s3->putObject([
        'Bucket' => R2_BUCKET,
        'Key' => $objectKey,
        'SourceFile' => $localPath,
        'ContentType' => $contentType,
    ]);

    return rtrim(R2_PUBLIC_URL, '/').'/'.$objectKey;
}

function deleteR2ObjectByUrl($url){
    if(empty($url)) return;

    $prefix = rtrim(R2_PUBLIC_URL, '/').'/';

    if(strpos($url, $prefix) !== 0){
        return;
    }

    $key = substr($url, strlen($prefix));

    $s3 = r2Client();
    $s3->deleteObject([
        'Bucket' => R2_BUCKET,
        'Key' => $key,
    ]);
}

function ensureRemapResultColumns($conn){
    $checkCol = mysqli_query($conn, "SHOW COLUMNS FROM remap_orders LIKE 'result_file'");
    if($checkCol && mysqli_num_rows($checkCol) == 0){
        mysqli_query($conn, "ALTER TABLE remap_orders ADD result_file VARCHAR(255) NULL, ADD result_file_original VARCHAR(255) NULL");
    }
}

function addRemapNotification($conn, $user_id, $order_id, $title, $message){
    $user_id = (int)$user_id;
    $order_id = (int)$order_id;
    $title = safe($conn, $title);
    $message = safe($conn, $message);

    mysqli_query($conn,"
        INSERT INTO remap_notifications
        (user_id, order_id, title, message, is_read)
        VALUES
        ('$user_id','$order_id','$title','$message',0)
    ");
}

function sendResultEmail($toEmail, $toName, $orderId, $kendaraan, $ecuType, $downloadUrl){
    $mail = new PHPMailer(true);

    try{
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = '';
        $mail->Password   = '';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@idsrepair.com', 'IDS ECU REPAIR');
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = 'File Remap ECU Selesai - IDS ECU REPAIR';

        $safeName = htmlspecialchars($toName);
        $safeKendaraan = htmlspecialchars($kendaraan);
        $safeEcuType = htmlspecialchars($ecuType);
        $safeUrl = htmlspecialchars($downloadUrl);

        $mail->Body = "
            Hai <b>{$safeName}</b>,<br><br>
            File hasil remap ECU kamu sudah selesai diproses.<br><br>
            <b>Order:</b> #{$orderId}<br>
            <b>Kendaraan:</b> {$safeKendaraan}<br>
            <b>Tipe ECU:</b> {$safeEcuType}<br><br>
            Download file hasil remap melalui link berikut:<br>
            <a href=\"{$safeUrl}\">Download Hasil Remap ECU</a><br><br>
            Jika link tidak bisa diklik, salin URL ini ke browser:<br>
            {$safeUrl}<br><br>
            Terima kasih,<br>
            <b>IDS ECU REPAIR SOPPENG</b>
        ";

        $mail->AltBody = "File hasil remap ECU kamu sudah selesai. Download: ".$downloadUrl;

        $mail->send();
        return true;

    }catch(Exception $e){
        error_log("PHPMailer result email error: ".$mail->ErrorInfo);
        return false;
    }
}

$success = '';
$error = '';

/* =========================
   UPDATE STATUS ORDER
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])){

    $id = (int)($_POST['order_id'] ?? 0);
    $status = safe($conn, $_POST['status'] ?? '');

    $allowedStatus = ['menunggu_pembayaran','diproses','selesai','ditolak'];

    if($id <= 0 || !in_array($status, $allowedStatus)){
        $error = 'Data status tidak valid.';
    }else{
        $update = mysqli_query($conn,"UPDATE remap_orders SET status='$status' WHERE id='$id'");

        if($update){
            $success = 'Status order berhasil diperbarui.';
        }else{
            $error = 'Gagal update status: '.mysqli_error($conn);
        }
    }
}

/* =========================
   UPLOAD HASIL REMAP + EMAIL
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_result'])){

    $id = (int)($_POST['order_id'] ?? 0);

    if($id <= 0){
        $error = 'Order tidak valid.';
    }elseif(!isset($_FILES['result_file']) || $_FILES['result_file']['error'] !== 0){
        $error = 'File hasil remap wajib diupload.';
    }else{

        $getOrder = mysqli_query($conn,"SELECT * FROM remap_orders WHERE id='$id' LIMIT 1");

        if(!$getOrder || mysqli_num_rows($getOrder) <= 0){
            $error = 'Order tidak ditemukan.';
        }else{

            $order = mysqli_fetch_assoc($getOrder);

            ensureRemapResultColumns($conn);

            $maxSize = 150 * 1024 * 1024;
            $allowed = ['bin','ori','hex','zip','rar'];
            $ext = strtolower(pathinfo($_FILES['result_file']['name'], PATHINFO_EXTENSION));

            if($_FILES['result_file']['size'] > $maxSize){
                $error = 'Ukuran file hasil maksimal 150MB.';
            }elseif(!in_array($ext, $allowed)){
                $error = 'Format hasil harus BIN, ORI, HEX, ZIP, atau RAR.';
            }else{
                $resultOriginal = safe($conn, $_FILES['result_file']['name']);
                $resultName = 'result_'.$id.'_'.time().'_'.rand(1000,9999).'.'.$ext;
                $target = sys_get_temp_dir()."/".$resultName;

                if(move_uploaded_file($_FILES['result_file']['tmp_name'], $target)){

                    try{
                        $resultUrl = uploadToR2($target, "remap_results/".$resultName, getMimeTypeSafe($target));
                        @unlink($target);
                    }catch(Exception $e){
                        @unlink($target);
                        $error = 'Cloudflare R2 error: '.$e->getMessage();
                        $resultUrl = '';
                    }

                    if(empty($error)){

                    $update = mysqli_query($conn,"
                        UPDATE remap_orders
                        SET result_file='$resultUrl',
                            result_file_original='$resultOriginal',
                            status='selesai'
                        WHERE id='$id'
                    ");

                    if($update){

                        $downloadUrl = $resultUrl;

                        if((int)($order['user_id'] ?? 0) > 0){
                            addRemapNotification(
                                $conn,
                                (int)$order['user_id'],
                                $id,
                                "File remap ECU selesai",
                                "File hasil remap untuk ".$order['kendaraan']." sudah selesai. Silakan download hasilnya."
                            );
                        }

                        $emailSent = false;

                        if(!empty($order['email'])){
                            $emailSent = sendResultEmail(
                                $order['email'],
                                $order['nama'] ?? 'Customer',
                                $id,
                                $order['kendaraan'] ?? '-',
                                $order['ecu_type'] ?? '-',
                                $downloadUrl
                            );
                        }

                        if($emailSent){
                            $success = 'File hasil remap berhasil diupload, status selesai, dan email hasil sudah terkirim.';
                        }else{
                            $success = 'File hasil remap berhasil diupload dan status selesai, tapi email hasil gagal terkirim. Cek konfigurasi SMTP/log server.';
                        }

                    }else{
                        try{
                            deleteR2ObjectByUrl($resultUrl);
                        }catch(Exception $e){}
                        $error = 'Gagal simpan hasil: '.mysqli_error($conn);
                    }

                    }

                }else{
                    $error = 'Gagal upload file hasil remap.';
                }
            }
        }
    }
}


/* =========================
   HAPUS ORDER REMAP FINAL
========================= */

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_order'])){

    $id = (int)($_POST['order_id'] ?? 0);

    if($id <= 0){
        $error = 'Order tidak valid.';
    }else{

        $getOrder = mysqli_query($conn,"SELECT * FROM remap_orders WHERE id='$id' LIMIT 1");

        if($getOrder && mysqli_num_rows($getOrder) > 0){

            $order = mysqli_fetch_assoc($getOrder);

            if(!empty($order['ecu_file'])){
                try{
                    deleteR2ObjectByUrl($order['ecu_file']);
                }catch(Exception $e){}
            }

            if(!empty($order['payment_proof'])){
                try{
                    deleteR2ObjectByUrl($order['payment_proof']);
                }catch(Exception $e){}
            }

            if(!empty($order['result_file'])){
                try{
                    deleteR2ObjectByUrl($order['result_file']);
                }catch(Exception $e){}
            }

            mysqli_query($conn,"DELETE FROM remap_notifications WHERE order_id='$id'");

            if(mysqli_query($conn,"DELETE FROM remap_orders WHERE id='$id'")){
                $success = 'Order remap berhasil dihapus.';
            }else{
                $error = 'Gagal hapus order: '.mysqli_error($conn);
            }

        }else{
            $error = 'Order tidak ditemukan.';
        }
    }
}

/* =========================
   AMBIL DATA ORDER
========================= */

ensureRemapResultColumns($conn);

$filter = $_GET['filter'] ?? 'menunggu_pembayaran';
$allowedFilter = ['menunggu_pembayaran','diproses','selesai','ditolak','semua'];

if(!in_array($filter, $allowedFilter)){
    $filter = 'menunggu_pembayaran';
}

$countMenunggu = 0;
$countProses = 0;
$countSelesai = 0;
$countDitolak = 0;
$countSemua = 0;

$qCount = mysqli_query($conn,"
    SELECT status, COUNT(*) AS total
    FROM remap_orders
    GROUP BY status
");

if($qCount){
    while($c = mysqli_fetch_assoc($qCount)){
        if($c['status'] === 'menunggu_pembayaran') $countMenunggu = (int)$c['total'];
        if($c['status'] === 'diproses') $countProses = (int)$c['total'];
        if($c['status'] === 'selesai') $countSelesai = (int)$c['total'];
        if($c['status'] === 'ditolak') $countDitolak = (int)$c['total'];
        $countSemua += (int)$c['total'];
    }
}

$where = "";
if($filter !== 'semua'){
    $filterSafe = safe($conn, $filter);
    $where = "WHERE status='$filterSafe'";
}

$orders = mysqli_query($conn,"SELECT * FROM remap_orders $where ORDER BY id DESC");

$filterTitleMap = [
    'menunggu_pembayaran' => 'Order Menunggu Pembayaran',
    'diproses' => 'Order Sedang Diproses',
    'selesai' => 'Order Selesai',
    'ditolak' => 'Order Ditolak',
    'semua' => 'Semua Order Remap'
];

$filterTitle = $filterTitleMap[$filter] ?? 'Order Remap';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Order Remap - IDS ECU REPAIR</title>
<link rel="icon" href="/css/ecu-logo.png" type="image/png">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:#0d0d0d;color:#fff;font-family:Arial,sans-serif;}
.header{position:sticky;top:0;z-index:99;background:#111;border-bottom:2px solid #c00000;padding:14px 15px;display:flex;align-items:center;justify-content:space-between;gap:10px;box-shadow:0 0 20px rgba(255,0,0,.12);}
.header-title{font-size:16px;font-weight:bold;color:#ff2b2b;line-height:1.4;}
.header-user{font-size:11px;color:#888;margin-top:3px;}
.nav-btn{background:#1b1b1b;border:1px solid #333;border-radius:9px;padding:8px 12px;color:#fff;text-decoration:none;font-size:12px;white-space:nowrap;}
.nav-btn:hover{background:#ff2b2b;}
.container{width:100%;max-width:1050px;margin:auto;padding:14px;}
.alert{padding:12px;border-radius:10px;margin-bottom:14px;font-size:13px;line-height:1.6;font-weight:bold;}
.success{background:#006b2d;}
.error{background:#9b0000;}
.order-card{background:#161616;border:1px solid #242424;border-radius:15px;margin-bottom:16px;overflow:hidden;box-shadow:0 0 18px rgba(255,0,0,.04);}
.order-head{padding:14px;background:#111;border-bottom:1px solid #242424;display:flex;justify-content:space-between;gap:10px;align-items:flex-start;}
.order-title{font-size:15px;color:#ff3c3c;font-weight:bold;line-height:1.4;}
.order-id{font-size:12px;color:#999;margin-top:4px;line-height:1.5;}
.badge{padding:6px 9px;border-radius:999px;font-size:11px;font-weight:bold;white-space:nowrap;text-align:center;}
.badge.pending{background:#5c4500;color:#ffd35c;}
.badge.paid{background:#005c2a;color:#7dffb4;}
.badge.failed{background:#5c0000;color:#ff8a8a;}
.order-body{padding:14px;}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px;margin-bottom:13px;}
.item{background:#101010;border:1px solid #282828;border-radius:10px;padding:10px;}
.label{font-size:11px;color:#888;margin-bottom:5px;}
.value{font-size:13px;color:#fff;line-height:1.5;word-break:break-word;}
.file-row{display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;}
.file-btn{display:inline-block;background:#c00000;color:#fff;text-decoration:none;border-radius:9px;padding:9px 11px;font-size:12px;font-weight:bold;}
.file-btn.dark{background:#333;}
.forms{display:grid;grid-template-columns:repeat(auto-fit,minmax(230px,1fr));gap:10px;margin-top:12px;}
.form-box{background:#101010;border:1px solid #282828;border-radius:12px;padding:12px;}
select,input[type=file]{width:100%;background:#1b1b1b;border:1px solid #333;border-radius:9px;padding:10px;color:#fff;font-size:13px;margin-bottom:9px;}
button{width:100%;background:#c00000;color:#fff;border:none;border-radius:9px;padding:10px;font-weight:bold;cursor:pointer;}
button:hover,.file-btn:hover{background:#ff2b2b;}
.empty{text-align:center;color:#777;padding:50px 15px;}
.price{color:#00ff7b;font-weight:bold;}
.small-muted{font-size:11px;color:#888;margin-top:6px;line-height:1.5;}
@media(max-width:600px){.order-head{flex-direction:column;}.badge{align-self:flex-start;}.header-title{font-size:15px;}.container{padding:10px;}}

.admin-version{
font-size:11px;
color:#777;
margin:-5px 0 12px;
}
.tabs{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(135px,1fr));
gap:10px;
margin-bottom:15px;
}
.tab{
display:block;
background:#161616;
border:1px solid #282828;
border-radius:13px;
padding:13px;
text-decoration:none;
color:#ddd;
}
.tab.active{
background:#c00000;
border-color:#ff2b2b;
color:#fff;
box-shadow:0 0 16px rgba(255,0,0,.18);
}
.tab-title{
font-size:12px;
font-weight:bold;
margin-bottom:7px;
}
.tab-count{
font-size:24px;
font-weight:bold;
color:#ff3c3c;
}
.tab.active .tab-count{
color:#fff;
}
.section-title{
font-size:18px;
color:#ff2b2b;
font-weight:bold;
margin:8px 0 14px;
}
.delete-btn{
background:#4b0000!important;
}
.delete-btn:hover{
background:#ff0000!important;
}

</style>
</head>
<body>

<div class="header">
    <div>
        <div class="header-title">⚡ Admin Order Remap</div>
        <div class="header-user">Login sebagai: <?= htmlspecialchars($userData['nama'] ?? 'Admin') ?> / <?= htmlspecialchars($userData['role'] ?? '') ?></div>
    </div>
    <a href="/dashboard/" class="nav-btn">Kembali</a>
</div>

<div class="container">

<?php if($success): ?><div class="alert success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="admin-version">VERSI BARU: kategori order + tombol hapus order</div>

<div class="tabs">
<a class="tab <?= $filter === 'menunggu_pembayaran' ? 'active' : '' ?>" href="?filter=menunggu_pembayaran">
    <div class="tab-title">Menunggu Bayar</div>
    <div class="tab-count"><?= (int)$countMenunggu ?></div>
</a>
<a class="tab <?= $filter === 'diproses' ? 'active' : '' ?>" href="?filter=diproses">
    <div class="tab-title">Diproses</div>
    <div class="tab-count"><?= (int)$countProses ?></div>
</a>
<a class="tab <?= $filter === 'selesai' ? 'active' : '' ?>" href="?filter=selesai">
    <div class="tab-title">Selesai</div>
    <div class="tab-count"><?= (int)$countSelesai ?></div>
</a>
<a class="tab <?= $filter === 'ditolak' ? 'active' : '' ?>" href="?filter=ditolak">
    <div class="tab-title">Ditolak</div>
    <div class="tab-count"><?= (int)$countDitolak ?></div>
</a>
<a class="tab <?= $filter === 'semua' ? 'active' : '' ?>" href="?filter=semua">
    <div class="tab-title">Semua</div>
    <div class="tab-count"><?= (int)$countSemua ?></div>
</a>
</div>

<div class="section-title"><?= htmlspecialchars($filterTitle) ?></div>

<?php if($orders && mysqli_num_rows($orders) > 0): ?>

<?php while($o = mysqli_fetch_assoc($orders)): ?>

<?php
$payStatus = $o['payment_status'] ?? 'pending';
$payClass = 'pending';
if($payStatus === 'paid') $payClass = 'paid';
if($payStatus === 'failed') $payClass = 'failed';
?>

<div class="order-card">

    <div class="order-head">
        <div>
            <div class="order-title"><?= htmlspecialchars($o['kendaraan'] ?? '-') ?> — <?= htmlspecialchars($o['ecu_type'] ?? '-') ?></div>
            <div class="order-id">Order #<?= (int)$o['id'] ?> | <?= htmlspecialchars($o['created_at'] ?? '-') ?></div>
        </div>
        <div class="badge <?= $payClass ?>">
            <?= htmlspecialchars(strtoupper($payStatus)) ?> / <?= htmlspecialchars($o['status'] ?? '-') ?>
        </div>
    </div>

    <div class="order-body">

        <div class="grid">
            <div class="item"><div class="label">Nama</div><div class="value"><?= htmlspecialchars($o['nama'] ?? '-') ?></div></div>
            <div class="item"><div class="label">WhatsApp</div><div class="value"><?= htmlspecialchars($o['whatsapp'] ?? '-') ?></div></div>
            <div class="item"><div class="label">Email Hasil</div><div class="value"><?= htmlspecialchars($o['email'] ?? '-') ?></div></div>
            <div class="item"><div class="label">Harga</div><div class="value price">Rp <?= number_format((int)($o['price'] ?? 0),0,',','.') ?></div></div>
            <div class="item"><div class="label">Duitku Reference</div><div class="value"><?= htmlspecialchars($o['duitku_reference'] ?? '-') ?></div></div>
            <div class="item"><div class="label">Merchant Order ID</div><div class="value"><?= htmlspecialchars($o['merchant_order_id'] ?? '-') ?></div></div>
        </div>

        <div class="item">
            <div class="label">Permintaan Setting</div>
            <div class="value"><?= nl2br(htmlspecialchars($o['permintaan'] ?? '-')) ?></div>
        </div>

        <div class="file-row">
            <?php if(!empty($o['ecu_file'])): ?>
            <a class="file-btn" href="<?= htmlspecialchars($o['ecu_file']) ?>" download>⬇ Download File ECU</a>
            <?php endif; ?>

            <?php if(!empty($o['result_file'])): ?>
            <a class="file-btn dark" href="<?= htmlspecialchars($o['result_file']) ?>" download>✅ Download Hasil Remap</a>
            <?php endif; ?>
        </div>

        <div class="forms">
            <form method="POST" class="form-box">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <select name="status">
                    <option value="menunggu_pembayaran" <?= (($o['status'] ?? '')=='menunggu_pembayaran'?'selected':'') ?>>Menunggu Pembayaran</option>
                    <option value="diproses" <?= (($o['status'] ?? '')=='diproses'?'selected':'') ?>>Diproses</option>
                    <option value="selesai" <?= (($o['status'] ?? '')=='selesai'?'selected':'') ?>>Selesai</option>
                    <option value="ditolak" <?= (($o['status'] ?? '')=='ditolak'?'selected':'') ?>>Ditolak</option>
                </select>
                <button type="submit" name="update_status">Update Status</button>
                <div class="small-muted">Ubah status manual kalau diperlukan.</div>
            </form>

            <form method="POST" enctype="multipart/form-data" class="form-box">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <input type="file" name="result_file" accept=".bin,.ori,.hex,.zip,.rar" required>
                <button type="submit" name="upload_result">Upload Hasil Remap + Kirim Email</button>
                <div class="small-muted">Setelah upload, status otomatis selesai dan email hasil dikirim ke customer.</div>
            </form>

            <form method="POST" class="form-box" onsubmit="return confirm('Yakin hapus order remap ini? Semua file dan notifikasi order ini ikut dihapus.');">
                <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                <button type="submit" name="delete_order" class="delete-btn">Hapus Order</button>
                <div class="small-muted">Hapus order dari database + file ECU + hasil remap.</div>
            </form>
        </div>

    </div>
</div>

<?php endwhile; ?>

<?php else: ?>
<div class="empty">Belum ada order remap.</div>
<?php endif; ?>

</div>

</body>
</html>

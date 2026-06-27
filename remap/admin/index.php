<?php
if($_SERVER['REQUEST_URI'] === '/remap/index.php'){
    header("Location: /remap/", true, 301);
 exit;
}

session_start();
require_once("../includes/db.php");
require_once("../vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Aws\S3\S3Client;

/*
    REMAP IDSREPAIR - ONE FILE VERSION
    Simpan sebagai: /remap/index.php

    Callback URL Duitku:
    https://idsrepair.com/remap/index.php?duitku_callback=1
*/

define("DUITKU_MERCHANT", "");
define("DUITKU_APIKEY", "");
define("DUITKU_ENDPOINT", "https://api-prod.duitku.com/api/merchant/createInvoice");
define("DUITKU_CALLBACK", "https://idsrepair.com/remap/index.php?duitku_callback=1");
define("DUITKU_RETURN", "https://idsrepair.com/remap/?payment_return=1");
define("REMAP_PRICE", 1500000);

define("R2_ACCOUNT_ID", "");
define("R2_ACCESS_KEY_ID", "");
define("R2_SECRET_ACCESS_KEY", "");
define("R2_BUCKET", "idsrepair-images");
define("R2_PUBLIC_URL", "https://cdn.idsrepair.com");

function ensureDir($dir){
    if(!is_dir($dir)){
        return mkdir($dir, 0777, true);
    }
    return true;
}

function deleteIfExists($path){
    if($path && file_exists($path)){
        @unlink($path);
    }
}

function safe($conn, $value){
    return mysqli_real_escape_string($conn, trim($value ?? ''));
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


function sendRemapEmail($to, $name, $subject, $body){
    if(empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)){
        return false;
    }

    $mail = new PHPMailer(true);

    try{
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'skyfilecloud@gmail.com';
        $mail->Password   = 'odle wcnk eulp ozsc';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('no-reply@idsrepair.com', 'IDS ECU REPAIR');
        $mail->addAddress($to, $name ?: $to);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        $mail->send();
        return true;

    }catch(Exception $e){
        error_log("Remap email error: ".$mail->ErrorInfo);
        return false;
    }
}

function addRemapNotification($conn, $user_id, $order_id, $title, $message){
    $user_id = (int)$user_id;
    $order_id = (int)$order_id;

    if($user_id <= 0 || $order_id <= 0){
        return false;
    }

    $titleSafe = mysqli_real_escape_string($conn, $title);
    $msgSafe = mysqli_real_escape_string($conn, $message);

    return mysqli_query($conn,"
        INSERT INTO remap_notifications
        (user_id, order_id, title, message, is_read)
        VALUES
        ('$user_id', '$order_id', '$titleSafe', '$msgSafe', 0)
    ");
}

function rupiah($amount){
    return 'Rp '.number_format((int)$amount, 0, ',', '.');
}

function failOrder($message, $ecuFileName = ''){
    if($ecuFileName && preg_match('/^https?:\/\//i', $ecuFileName)){
        try{
            deleteR2ObjectByUrl($ecuFileName);
        }catch(Exception $e){}
    }

    $_SESSION['remap_error'] = $message;
    header("Location: /remap/");
    exit;
}

function duitkuRequest($params){
    $json = json_encode($params);
    $timestamp = round(microtime(true) * 1000);
    $signature = hash('sha256', DUITKU_MERCHANT . $timestamp . DUITKU_APIKEY);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, DUITKU_ENDPOINT);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        "Accept: application/json",
        "Content-Type: application/json",
        "Content-Length: ".strlen($json),
        "x-duitku-signature: ".$signature,
        "x-duitku-timestamp: ".$timestamp,
        "x-duitku-merchantcode: ".DUITKU_MERCHANT
    ));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if($error){
        return array("error" => $error);
    }

    $result = json_decode($response, true);

    if(!is_array($result)){
        return array("httpCode" => $httpCode, "raw" => $response);
    }

    $result["httpCode"] = $httpCode;
    return $result;
}

/* ===================== CALLBACK DUITKU ===================== */
if(isset($_GET['duitku_callback'])){

    $raw = file_get_contents("php://input");

    file_put_contents(
        __DIR__."/duitku_callback_log.txt",
        date("Y-m-d H:i:s")."\nPOST:\n".print_r($_POST,true)."\nRAW:\n".$raw."\n--------------------\n",
        FILE_APPEND
    );

    $merchantCode = $_POST['merchantCode'] ?? '';
    $amount = $_POST['amount'] ?? '';
    $merchantOrderId = $_POST['merchantOrderId'] ?? '';
    $resultCode = $_POST['resultCode'] ?? '';
    $reference = $_POST['reference'] ?? '';
    $signature = $_POST['signature'] ?? '';

    $checkSignature = md5($merchantCode.$amount.$merchantOrderId.DUITKU_APIKEY);

    if($signature !== $checkSignature){
        http_response_code(400);
        echo "BAD SIGNATURE";
        exit;
    }

    $referenceSafe = safe($conn, $reference);
    $orderIdSafe = safe($conn, $merchantOrderId);

    $orderQuery = mysqli_query($conn,"
        SELECT *
        FROM remap_orders
        WHERE duitku_reference='$referenceSafe'
           OR merchant_order_id='$orderIdSafe'
        LIMIT 1
    ");

    $orderData = $orderQuery ? mysqli_fetch_assoc($orderQuery) : null;
    $alreadyPaid = $orderData && (($orderData['payment_status'] ?? '') === 'paid');

    if($resultCode === "00"){

        mysqli_query($conn,"
            UPDATE remap_orders
            SET payment_status='paid',
                status='diproses'
            WHERE duitku_reference='$referenceSafe'
               OR merchant_order_id='$orderIdSafe'
        ");

        if($orderData && !$alreadyPaid){
            $orderId = (int)$orderData['id'];
            $userId = (int)($orderData['user_id'] ?? 0);
            $nama = $orderData['nama'] ?? '';
            $email = $orderData['email'] ?? '';
            $kendaraan = $orderData['kendaraan'] ?? '';
            $ecuType = $orderData['ecu_type'] ?? '';

            addRemapNotification(
                $conn,
                $userId,
                $orderId,
                'Pembayaran Remap Berhasil',
                'Pembayaran order remap '.$kendaraan.' - '.$ecuType.' berhasil. File ECU kamu masuk proses dan estimasi pengerjaan 1 x 24 jam.'
            );

            $subject = 'Pembayaran Remap Berhasil - IDS ECU REPAIR';
            $body = "
                Hai <b>".htmlspecialchars($nama)."</b>,<br><br>
                Pembayaran order remap ECU kamu sudah berhasil kami terima.<br><br>
                <b>Detail order:</b><br>
                Kendaraan: ".htmlspecialchars($kendaraan)."<br>
                Tipe ECU: ".htmlspecialchars($ecuType)."<br>
                Nomor Order: #".$orderId."<br><br>
                File ECU kamu sedang masuk proses. Estimasi pengerjaan maksimal <b>1 x 24 jam</b>.<br><br>
                Terima kasih,<br>
                <b>IDS ECU REPAIR</b>
            ";

            sendRemapEmail($email, $nama, $subject, $body);
        }

    }else{

        mysqli_query($conn,"
            UPDATE remap_orders
            SET payment_status='failed',
                status='menunggu_pembayaran'
            WHERE duitku_reference='$referenceSafe'
               OR merchant_order_id='$orderIdSafe'
        ");
    }

    http_response_code(200);
    echo "OK";
    exit;
}

/* ===================== SUBMIT ORDER ===================== */
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remap_submit'])){

    $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

    $nama = safe($conn, $_POST['nama'] ?? '');
    $whatsapp = safe($conn, $_POST['whatsapp'] ?? '');
    $email = safe($conn, $_POST['email'] ?? '');
    $kendaraan = safe($conn, $_POST['kendaraan'] ?? '');
    $ecu_type = safe($conn, $_POST['ecu_type'] ?? '');
    $permintaan = safe($conn, $_POST['permintaan'] ?? '');

    if($nama === '' || $whatsapp === '' || $email === '' || $kendaraan === '' || $ecu_type === ''){
        failOrder("Data wajib belum lengkap.");
    }

    if(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        failOrder("Format email tidak valid.");
    }


    if(!isset($_FILES['ecu_file']) || $_FILES['ecu_file']['error'] !== 0){
        failOrder("File ECU original wajib diupload.");
    }

    $maxSize = 150 * 1024 * 1024;

    if($_FILES['ecu_file']['size'] > $maxSize){
        failOrder("Ukuran file ECU maksimal 150MB.");
    }

    $allowedEcu = array('bin','ori','hex','zip','rar');
    $extEcu = strtolower(pathinfo($_FILES['ecu_file']['name'], PATHINFO_EXTENSION));

    if(!in_array($extEcu, $allowedEcu)){
        failOrder("Format file ECU harus BIN, ORI, HEX, ZIP, atau RAR.");
    }

    $ecuFileOriginal = safe($conn, $_FILES['ecu_file']['name']);
    $newEcuFileName = time().'_'.rand(1000,9999).'.'.$extEcu;
    $ecuPath = sys_get_temp_dir()."/".$newEcuFileName;

    if(!move_uploaded_file($_FILES['ecu_file']['tmp_name'], $ecuPath)){
        failOrder("Gagal upload file ECU.");
    }

    try{
        $ecuFileName = uploadToR2($ecuPath, "remap_files/".$newEcuFileName, getMimeTypeSafe($ecuPath));
        deleteIfExists($ecuPath);
    }catch(Exception $e){
        deleteIfExists($ecuPath);
        failOrder("Cloudflare R2 error: ".$e->getMessage());
    }

    $price = REMAP_PRICE;

    $insert = mysqli_query($conn,"
        INSERT INTO remap_orders
        (user_id,nama,whatsapp,email,kendaraan,ecu_type,permintaan,ecu_file,ecu_file_original,payment_proof,price,status,payment_status)
        VALUES
        ('$user_id','$nama','$whatsapp','$email','$kendaraan','$ecu_type','$permintaan','$ecuFileName','$ecuFileOriginal','','$price','menunggu_pembayaran','pending')
    ");

    if(!$insert){
        failOrder("Gagal simpan order: ".mysqli_error($conn), $ecuFileName);
    }

    $orderId = mysqli_insert_id($conn);
    $merchantOrderId = "REMAP".$orderId.time();
    $merchantOrderIdSafe = safe($conn, $merchantOrderId);

       mysqli_query($conn,"
       UPDATE remap_orders
       SET merchant_order_id='$merchantOrderIdSafe'
       WHERE id='$orderId'
    ");
    $params = array(
        "paymentAmount" => $price,
        "merchantOrderId" => $merchantOrderId,
        "productDetails" => "Remap ECU IDSREPAIR",
        "additionalParam" => "",
        "merchantUserInfo" => $email,
        "customerVaName" => substr($nama, 0, 20),
        "email" => $email,
        "phoneNumber" => $whatsapp,
        "itemDetails" => array(
            array(
                "name" => "Remap ECU IDSREPAIR",
                "price" => $price,
                "quantity" => 1
            )
        ),
        "customerDetail" => array(
            "firstName" => $nama,
            "lastName" => "",
            "email" => $email,
            "phoneNumber" => $whatsapp
        ),
        "callbackUrl" => DUITKU_CALLBACK,
        "returnUrl" => DUITKU_RETURN,
        "expiryPeriod" => 60
    );

    $result = duitkuRequest($params);

    if(isset($result['reference'])){
        $ref = safe($conn, $result['reference']);
        mysqli_query($conn,"UPDATE remap_orders SET duitku_reference='$ref' WHERE id='$orderId'");
    }

    if(isset($result['paymentUrl'])){

        addRemapNotification(
            $conn,
            $user_id,
            $orderId,
            'Order Remap Dibuat',
            'Order remap '.$kendaraan.' - '.$ecu_type.' berhasil dibuat dan sedang menunggu pembayaran.'
        );

        $subject = 'Order Remap Dibuat - Menunggu Pembayaran';
        $body = "
            Hai <b>".htmlspecialchars($nama)."</b>,<br><br>
            Order remap ECU kamu sudah berhasil dibuat dan sedang menunggu pembayaran.<br><br>
            <b>Detail order:</b><br>
            Kendaraan: ".htmlspecialchars($kendaraan)."<br>
            Tipe ECU: ".htmlspecialchars($ecu_type)."<br>
            Permintaan: ".nl2br(htmlspecialchars($permintaan))."<br>
            Nomor Order: #".$orderId."<br>
            Total: ".rupiah($price)."<br><br>
            Silakan selesaikan pembayaran melalui halaman Duitku yang terbuka setelah order dibuat.<br><br>
            Terima kasih,<br>
            <b>IDS ECU REPAIR</b>
        ";

        sendRemapEmail($email, $nama, $subject, $body);

        header("Location: ".$result['paymentUrl']);
        exit;
    }

    if($ecuFileName && preg_match('/^https?:\/\//i', $ecuFileName)){
        try{
            deleteR2ObjectByUrl($ecuFileName);
        }catch(Exception $e){}
    }
    mysqli_query($conn,"DELETE FROM remap_orders WHERE id='$orderId'");

    $_SESSION['remap_error'] = "Gagal membuat pembayaran Duitku. Response: ".print_r($result, true);
    header("Location: /remap/");
    exit;
}

$success = $_SESSION['remap_success'] ?? '';
$error = $_SESSION['remap_error'] ?? '';
unset($_SESSION['remap_success'], $_SESSION['remap_error']);

if(isset($_GET['payment_return'])){
    $success = "Jika pembayaran berhasil, order kamu akan otomatis masuk proses. Cek email notifikasi atau masuk halaman dashboard jika sudah login.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<!-- BASIC META -->
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Remap ECU IDS ECU REPAIR | Stage 1, File ECU, Tuning Diesel Indonesia</title>
<meta name="description" content="Layanan remap ECU IDS ECU REPAIR untuk tuning diesel, stage 1, optimasi tenaga, torque, throttle response, EGR OFF, DPF OFF dan setting file ECU kendaraan Indonesia.">
<meta name="theme-color" content="#c00000">
<meta name="keywords" content="Remap Ecu terdekat, Remap ECU Indonesia, Stage 1 Diesel, File ECU, Tuning ECU, Remap Hilux, Remap Fortuner, Remap Pajero, Denso, Bosch, EGR OFF, DPF OFF, IDS ECU REPAIR">
<meta name="robots" content="index, follow">
<meta name="author" content="Jinbotol">
<!-- GEO LOCATION -->
<meta name="geo.region" content="ID-SN">
<meta name="geo.placename" content="Soppeng, Sulawesi Selatan">
<meta name="geo.position" content="-4.3519;119.8786">
<meta name="ICBM" content="-4.3519, 119.8786">
<!-- OPEN GRAPH / FACEBOOK -->
<meta property="og:url" content="https://idsrepair.com/remap/">
<meta property="og:type" content="website">
<meta property="og:title" content="Remap ECU IDS ECU REPAIR | Stage 1 & Tuning Diesel Indonesia">
<meta property="og:description" content="Order remap ECU online untuk kendaraan diesel dan bensin. Upload file ECU original, pembayaran otomatis, hasil remap dikirim ke email.">
<meta property="og:image" content="https://cdn.idsrepair.com/css/og-banner-remap.png">
<meta property="og:locale" content="id_ID">
<!-- WHATSAPP OPTIMIZATION -->
<meta property="og:image:secure_url" content="https://cdn.idsrepair.com/css/og-banner-remap.png">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1300">
<meta property="og:image:height" content="630">
<!-- TWITTER -->
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:domain" content="idsrepair.com">
<meta property="twitter:url" content="https://idsrepair.com/remap/">
<meta name="twitter:title" content="Remap ECU IDS ECU REPAIR | Stage 1 & Tuning Diesel Indonesia">
<meta name="twitter:description" content="Layanan remap ECU online Indonesia untuk tuning tenaga, torque dan optimasi performa kendaraan diesel maupun bensin.">
<meta name="twitter:image" content="https://cdn.idsrepair.com/css/og-banner-remap.png">
<!-- SCHEMA GOOGLE -->
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "AutomotiveBusiness",
  "name": "IDS ECU REPAIR",
  "url": "https://idsrepair.com/remap/",
  "logo": "https://cdn.idsrepair.com/css/ecu-logo.png",
  "image": "https://cdn.idsrepair.com/css/og-banner-remap.png",
  "description": "Layanan remap ECU dan tuning kendaraan Indonesia.",
  "address": {
    "@type": "PostalAddress",
    "addressLocality": "Soppeng",
    "addressRegion": "Sulawesi Selatan",
    "addressCountry": "ID"
  }
}
</script>

<!-- ICON -->
<link rel="icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/png">
<link rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/x-icon">
<link rel="apple-touch-icon" href="https://cdn.idsrepair.com/css/ecu-logo.png">
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
</head>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{background:
linear-gradient(rgba(0,0,0,.92),rgba(0,0,0,.94)),
url('https://cdn.idsrepair.com/css/bg-ecu.png');
font-family:Arial,sans-serif;color:#fff;}
.header{
position:sticky;
top:0;
z-index:99;
background:#111;
padding:10px 14px;
border-bottom:2px solid #c00000;
display:flex;
justify-content:space-between;
align-items:center;
border:none!important;
box-shadow:0 0 14px rgba(255,0,0,.45);

}
.header-title{
font-weight:bold;
margin-left:14px;
color:#ff2b2b;
font-size:17px;
letter-spacing:1px;
}

.back{
background:#1d1d1d;
border:1px solid #333;
padding:7px 11px;
border-radius:10px;
text-decoration:none;
color:#fff;
font-size:13px;
margin-right:10px;
}.container{width:100%;max-width:880px;margin:auto;padding:16px;}
.hero{text-align:center;padding:24px 0 15px;}
.banner{
width:100%;
height:220px;
object-fit:cover;
border-radius:18px;
margin-bottom:20px;
border:1px solid #222;
box-shadow:0 0 20px rgba(255,0,0,.15);
opacity:0;
transform:scale(1.03);
animation:bannerLoad .9s ease forwards;
}
.hero h1{font-size:26px;color:#ff2b2b;line-height:1.35;margin-bottom:12px;}
.hero p{font-size:14px;line-height:1.8;color:#ccc;max-width:760px;margin:auto;}
.card{background:#161616;border:1px solid #242424;border-radius:16px;padding:18px;margin-bottom:16px;box-shadow:0 0 18px rgba(255,0,0,.04);}
.card h2{font-size:19px;color:#ff3c3c;margin-bottom:12px;}
.card p,.card li{font-size:14px;line-height:1.8;color:#ccc;}
.card ul{padding-left:20px;}
.service-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin-top:14px;}
.service-box{background:#101010;border:1px solid #2a2a2a;border-radius:12px;padding:13px;font-size:13px;line-height:1.6;color:#ddd;}
.price-box{margin-top:15px;background:#101010;border:1px solid #333;border-radius:14px;padding:15px;text-align:center;}
.price-box span{display:block;font-size:13px;color:#aaa;margin-bottom:7px;}
.price-box b{font-size:24px;color:#00ff7b;}
.continue-btn{width:100%;background:#c00000;color:#fff;border:none;border-radius:12px;padding:15px;font-size:15px;font-weight:bold;cursor:pointer;margin-top:14px;box-shadow:0 0 18px rgba(255,0,0,.2);}
.continue-btn:hover{background:#ff2b2b;}
.alert{
padding:11px 14px;
border-radius:12px;
margin:0 auto 15px;
font-size:13px;
line-height:1.55;
text-align:center;
font-weight:bold;
width:100%;
max-width:520px;
}
.alert-success{background:#005c2a;color:#fff;box-shadow:0 0 15px rgba(0,255,100,.12);}
.alert-error{background:#9b0000;color:#fff;white-space:pre-wrap;text-align:left;}
#orderForm{display:none;}
.form-group{margin-bottom:14px;}
.form-group label{display:block;font-size:13px;color:#bbb;margin-bottom:7px;}
.form-group input,.form-group textarea{width:100%;background:#1b1b1b;border:1px solid #333;border-radius:10px;padding:13px;color:#fff;font-size:14px;outline:none;}
.form-group textarea{min-height:115px;resize:vertical;}
.form-group input:focus,.form-group textarea:focus{border-color:#c00000;box-shadow:0 0 0 2px rgba(255,0,0,.12);}
.payment-box{background:#101010;border:1px solid #333;border-radius:14px;padding:16px;text-align:center;margin-bottom:15px;}
.payment-box h3{color:#ff3c3c;font-size:17px;margin-bottom:8px;}
.payment-box p{font-size:13px;color:#ccc;line-height:1.7;}
.note{font-size:12px;color:#888;line-height:1.7;margin-top:8px;}
.submit-btn{width:100%;background:#c00000;border:none;border-radius:12px;padding:15px;color:#fff;font-weight:bold;font-size:15px;cursor:pointer;}
.submit-btn:hover{background:#ff2b2b;}
.footer-note{font-size:12px;color:#777;line-height:1.7;text-align:center;padding:10px 0 25px;}
@keyframes bannerLoad{

0%{
opacity:0;
transform:scale(1.05);
filter:blur(8px);
}

100%{
opacity:1;
transform:scale(1);
filter:blur(0);
}

}
@media(max-width:600px){.banner{height:150px;}.hero h1{font-size:21px;}.card{padding:15px;}.price-box b{font-size:21px;}}

.top-maintenance{
position:sticky;
top:58px;
z-index:998;
width:100%;
padding:11px 15px;

background:rgba(20,20,20,.55);
backdrop-filter:blur(10px);
-webkit-backdrop-filter:blur(10px);

border-bottom:1px solid rgba(255,0,0,.35);
box-shadow:0 4px 18px rgba(0,0,0,.25);

color:#fff;
font-size:12px;
text-align:center;
font-weight:bold;
letter-spacing:.5px;

animation:maintenanceSlideLoop 6s infinite ease-in-out;
}

@keyframes maintenanceSlideLoop{

0%{
opacity:0;
transform:translateY(-35px);
}

15%{
opacity:1;
transform:translateY(0);
}

55%{
opacity:1;
transform:translateY(0);
}

75%{
opacity:0;
transform:translateY(0);
}

100%{
opacity:0;
transform:translateY(0);
}

}

</style>
<body>

<div class="header"><div class="header-title">⚡ Remap ECU</div><a href="/" class="back">Home</a></div>
<!--
<div class="top-maintenance">
⚠️ Pembayaran sedang maintenance sementara. Silakan gunakan metode transfer manual atau hubungi admin.
</div>
-->
<div class="container">

<?php if($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<section class="hero">
    <img src="https://cdn.idsrepair.com/css/og-banner-remap.png" class="banner" alt="Remap ECU IDS Repair">
    <h1>Remap ECU & Penambahan Tenaga Mesin</h1>
    <p>Layanan tuning file ECU untuk meningkatkan performa mesin, respons pedal, torsi, dan karakter tenaga kendaraan. File original kamu akan diproses oleh teknisi, lalu hasil setting dikirim kembali ke email yang kamu isi.</p>
</section>

<div class="card">
    <h2>Apa Itu Remap ECU?</h2>
    <p>Remap ECU adalah proses pengaturan ulang data pada file ECU kendaraan. Tujuannya untuk mengoptimalkan tenaga, torsi, respons akselerasi, dan karakter mesin sesuai kebutuhan kendaraan.</p><br>
    <p>Layanan ini cocok untuk kendaraan diesel/common rail, mobil harian, unit operasional, kendaraan niaga, dan kebutuhan tuning ringan sampai performa.</p>
    <div class="price-box"><span>Biaya layanan per 1 file ECU</span><b>Rp 1.500.000</b></div>
    <button type="button" class="continue-btn" onclick="showOrderForm()">LANJUTKAN ORDER REMAP</button>
</div>

<div class="card">
    <h2>Layanan Yang Bisa Diminta</h2>
    <div class="service-grid">
        <div class="service-box">⚡ <b>Stage 1</b><br>Optimasi tenaga dan torsi untuk penggunaan harian.</div>
        <div class="service-box">🚗 <b>Throttle Response</b><br>Respons pedal lebih cepat dan tarikan lebih ringan.</div>
        <div class="service-box">🛠 <b>EGR / DPF Request</b><br>Penanganan sesuai kebutuhan repair dan troubleshooting.</div>
        <div class="service-box">📁 <b>File Checking</b><br>Pemeriksaan file original sebelum proses tuning.</div>
    </div>
</div>

<div class="card">
    <h2>Alur Order</h2>
    <ul>
        <li>Isi data kendaraan dan kontak penerima.</li>
        <li>Upload file ECU original.</li>
        <li>Sistem membuka halaman pilihan pembayaran.</li>
        <li>Setelah pembayaran berhasil, status order otomatis masuk proses.</li>
        <li>Admin memproses file dan mengirim hasil ke email kamu.</li>
    </ul>
</div>

<div id="orderForm">
    <div class="card">
        <h2>Form Order Remap ECU</h2>
        <div class="service-box">
          💳 <b>Pembayaran Otomatis</b><br>
          Mendukung QRIS, E-Wallet, transfer bank,
          dan minimarket dengan verifikasi otomatis.
        </div>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="remap_submit" value="1">
            <div class="form-group"><label>Nama Lengkap</label><input type="text" name="nama" required></div>
            <div class="form-group"><label>Nomor WhatsApp</label><input type="text" name="whatsapp" placeholder="Contoh: 082346783838" required></div>
            <div class="form-group"><label>Email Penerima File Hasil Setting</label><input type="email" name="email" placeholder="contoh@email.com" required></div>
            <div class="form-group"><label>Jenis Kendaraan</label><input type="text" name="kendaraan" placeholder="Contoh: Fortuner 2GD / Hilux / Pajero" required></div>
            <div class="form-group"><label>Tipe ECU</label><input type="text" name="ecu_type" placeholder="Contoh: Denso / Bosch / Delphi / Continental" required></div>
            <div class="form-group"><label>Permintaan Setting</label><textarea name="permintaan" placeholder="Contoh: Stage 1, tenaga bawah tambah, EGR OFF, DPF OFF, torque naik, dll"></textarea></div>
            <div class="form-group"><label>Upload File ECU Original</label><input type="file" name="ecu_file" accept=".bin,.ori,.hex,.zip,.rar" required><div class="note">Format: BIN, ORI, HEX, ZIP, RAR. Maksimal 150MB.</div></div>
            <button type="submit" class="submit-btn">KIRIM ORDER & BAYAR SEKARANG</button>
        </form>
    </div>
</div>

<div class="footer-note">IDS ECU REPAIR SOPPENG — Remap ECU, tuning file, dan penambahan tenaga mesin.</div>

</div>

<script>
function showOrderForm(){
    const form = document.getElementById("orderForm");
    form.style.display = "block";
    setTimeout(function(){form.scrollIntoView({behavior:"smooth", block:"start"});}, 100);
}
</script>
</footer>
<script src="/js/theme-mode.js"></script>
</body>
</html>
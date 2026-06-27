<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once("../../includes/db.php");

$query = mysqli_query($conn,"
SELECT 
repair_posts.*,
tamu.nama AS nama_author
FROM repair_posts
LEFT JOIN tamu
ON tamu.id = repair_posts.user_id
WHERE repair_posts.ecu_file IS NOT NULL
AND repair_posts.ecu_file != ''
ORDER BY repair_posts.pinned DESC, repair_posts.id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>File ECU</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c00000">
<meta name="robots" content="noindex, nofollow" />
<meta name="author" content="Jinbotol">
<!-- Geo Location Meta Tags -->
<meta name="geo.region" content="ID-SN">
<meta name="geo.placename" content="Soppeng, Sulawesi Selatan">
<meta name="geo.position" content="-4.3519;119.8786">
<meta name="ICBM" content="-4.3519, 119.8786">
<!-- Facebook Meta Tags -->
<meta property="og:url" content="https://idsrepair.com">
<meta property="og:type" content="website">
<meta property="og:title" content="IDS ECU REPAIR SOPPENG | Forum Repair ECU Indonesia">
<meta property="og:description" content="Forum komunitas repair ECU Indonesia, sharing file ECU, IMMO OFF, cloning ECU dan diagnosa kendaraan.">
<meta property="og:image" content="https://cdn.idsrepair.com/css/og-banner2.png">
<meta property="og:locale" content="id_ID">
<!-- Optimasi Tambahan Khusus WhatsApp -->
<meta property="og:image:secure_url" content="https://cdn.idsrepair.com/css/og-banner2.png">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<!-- Twitter Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:domain" content="idsrepair.com">
<meta property="twitter:url" content="https://idsrepair.com">
<meta name="twitter:title" content="IDS ECU REPAIR SOPPENG | Forum Repair ECU Indonesia">
<meta name="twitter:description" content="Forum komunitas repair ECU Indonesia, sharing file ECU, IMMO OFF, cloning ECU dan diagnosa kendaraan.">
<meta name="twitter:image" content="https://cdn.idsrepair.com/css/og-banner2.png">
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- Schema.org markup for Google -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "url": "https://idsrepair.com",
    "logo": "https://cdn.idsrepair.com/css/ecu-logo.png",
    "name": "IDSREPAIR"
  }
  </script>
<link rel="icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/png">
<link rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/x-icon">
<link rel="apple-touch-icon" href="https://cdn.idsrepair.com/css/ecu-logo.png">
<link data-rh="true" rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/x-icon">
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
<style>
body{
margin:0;
background:#0d0d0d;
color:#fff;
font-family:Arial;
}

.navbar{
background:#111;
padding:10px 15px;
background:#0d0d0d;
border-bottom:2px solid #c00000;
display:flex;
justify-content:space-between;
align-items:center;
position:sticky;
top:0;
z-index:999;
border:none!important;
box-shadow:0 0 14px rgba(255,0,0,.45);
}

.navbar .logo{
color:#ff2b2b;
margin-left:14px;
font-weight:bold;
font-size:17px;
letter-spacing:1px;
}

.nav-btn{
background:#1c1c1c;
border:1px solid #333;
color:white;
padding:8px 12px;
border-radius:8px;
text-decoration:none;
font-size:12px;
}

.container{
max-width:750px;
margin:auto;
padding:15px;
}

.file-card{
background:#161616;
border:1px solid #242424;
border-radius:12px;
padding:12px;
margin-bottom:12px;
position:relative;
overflow:hidden;
}

.file-title{
color:#ff3c3c;
font-weight:bold;
font-size:15px;
text-decoration:none;
}

.file-info{
font-size:12px;
color:#888;
margin-top:6px;
}

.file-box{
margin-top:12px;
background:#1a1a1a;
border:1px solid #2a2a2a;
border-radius:12px;
padding:12px;
display:flex;
align-items:center;
justify-content:space-between;
gap:10px;
}

.file-left{
display:flex;
align-items:center;
gap:10px;
overflow:hidden;
}

.file-icon{
width:42px;
height:42px;
border-radius:10px;
background:#2a2a2a;
display:flex;
align-items:center;
justify-content:center;
font-size:20px;
flex-shrink:0;
}

.file-name{
font-size:13px;
font-weight:bold;
color:#fff;
white-space:nowrap;
overflow:hidden;
text-overflow:ellipsis;
max-width:190px;
}

.ecu-file-size{
font-size:11px;
color:#888;
margin-top:3px;
}

.download-btn{
width:40px;
height:40px;
border-radius:10px;
background:#c00000;
display:flex;
align-items:center;
justify-content:center;
text-decoration:none;
color:white;
font-size:17px;
flex-shrink:0;
}

.empty{
text-align:center;
padding:50px 20px;
color:#777;
}

/* LOCK FILE */
.locked-file > *:not(.lock-overlay){
filter:blur(6px);
pointer-events:none;
user-select:none;
}

.lock-overlay{
position:absolute;
inset:0;
z-index:20;
background:rgba(0,0,0,.58);
display:flex;
align-items:center;
justify-content:center;
text-align:center;
color:#fff;
font-weight:bold;
font-size:15px;
padding:20px;
}

.lock-overlay a{
display:inline-block;
margin-top:10px;
background:#c00000;
color:#fff;
padding:10px 14px;
border-radius:8px;
text-decoration:none;
font-size:13px;
}
</style>
</head>

<body>

<div class="navbar">
<div class="logo">📦 File ECU</div>
<a href="javascript:void(0)" 
onclick="document.referrer ? history.back() : window.location='/'"
class="nav-btn">
Home
</a>
</div>

<div class="container">

<?php if(mysqli_num_rows($query) > 0): ?>

<?php $no = 0; ?>

<?php while($row = mysqli_fetch_assoc($query)): ?>

<?php
$no++;
$locked = (!isset($_SESSION['user_id']) && $no > 5);
?>

<div class="file-card <?= $locked ? 'locked-file' : '' ?>">

<?php if($locked): ?>
<div class="lock-overlay">
<div>
🔒<br>
Login atau buat akun<br>
untuk melihat file ECU ini
<br><br>
<a href="/">Masuk / Daftar</a>
</div>
</div>
<?php endif; ?>

<a class="file-title" href="/posts/repair.php#post<?= $row['id'] ?>">
<?= htmlspecialchars($row['title']) ?>
</a>

<div class="file-info">
👨‍🔧 <?= htmlspecialchars($row['nama_author'] ?? $row['author']) ?>
•
<?= date('d M Y H:i', strtotime($row['created_at'])) ?>
</div>

<div class="file-box">

<div class="file-left">

<div class="file-icon">📦</div>

<div>

<div class="file-name">
<?= htmlspecialchars($row['ecu_file_original'] ?: basename(parse_url($row['ecu_file'], PHP_URL_PATH) ?: $row['ecu_file'])) ?>
</div>

<div class="ecu-file-size">
<?php
$size = (int)($row['ecu_file_size'] ?? 0);

if($size > 0){
    echo $size >= 1048576 ? round($size / 1048576,2).' MB' : round($size / 1024,2).' KB';
}else{
    $filePath = $_SERVER['DOCUMENT_ROOT'].'/uploads/ecu_files/'.$row['ecu_file'];

    if(file_exists($filePath)){
        $size = filesize($filePath);
        echo $size >= 1048576 ? round($size / 1048576,2).' MB' : round($size / 1024,2).' KB';
    }else{
        echo 'File tidak ditemukan';
    }
}
?>
</div>

</div>

</div>

<?php if(isset($_SESSION['user_id'])): ?>

<?php if(preg_match('/^https?:\/\//i', $row['ecu_file'])): ?>

<a href="/posts/download_ecu.php?id=<?= $row['id'] ?>"
class="download-btn">
⬇
</a>

<?php else: ?>

<a href="/posts/download_ecu.php?id=<?= $row['id'] ?>"
class="download-btn">
⬇
</a>

<?php endif; ?>

<?php else: ?>

<a href="/index.php"
class="download-btn"
style="background:#333;color:#aaa;"
onclick="alert('Silakan login dulu untuk download file ECU');">
🔒
</a>

<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty">
Belum ada file ECU.
</div>

<?php endif; ?>

</div>
<script src="/js/theme-mode.js"></script>
</body>
</html>

<?php
 if($_SERVER['REQUEST_URI'] === '/posts/view_repair/index.php'){
    header("Location: /posts/view_repair/", true, 301);
 exit;
}

session_start();
include '../../includes/db.php';

function getYoutubeId($url){

if(empty($url)) return '';

preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([^&\?\/]+)/', $url, $matches);

return $matches[1] ?? '';

}

// CLOUDFLARE R2 MEDIA URL
define('R2_PUBLIC_URL', 'https://cdn.idsrepair.com');

function cloudflareMediaUrl($value, $folder){
    if(empty($value)) return '';

    $value = trim($value);

    if(preg_match('/^https?:\/\//i', $value)){
        return $value;
    }

    return rtrim(R2_PUBLIC_URL, '/').'/'.trim($folder, '/').'/'.ltrim($value, '/');
}

// MODIFIKASI REDIRECT UNTUK SEO GOOGLE
if(!isset($_GET['id']) || empty($_GET['id']) || (int)$_GET['id'] <= 0){
    // Alihkan bot/user langsung ke halaman utama secara permanen (301 Redirect)
    header("Location: https://idsrepair.com/", true, 301);
    exit();
}

$id = (int)$_GET['id'];

$query = mysqli_query($conn,"
SELECT 
repair_posts.*,
tamu.nama AS nama_author,
tamu.foto_profil
FROM repair_posts
LEFT JOIN tamu
ON tamu.id = repair_posts.user_id
WHERE repair_posts.id='$id'
LIMIT 1
");

$post = mysqli_fetch_assoc($query);
$locked = false;

if(!isset($_SESSION['user_id'])){

    $checkTop = mysqli_query($conn,"
    SELECT id
    FROM repair_posts
    ORDER BY pinned DESC, id DESC
    LIMIT 5
    ");

    $allowedIds = [];

    while($r = mysqli_fetch_assoc($checkTop)){
        $allowedIds[] = $r['id'];
    }

    if(!in_array($id, $allowedIds)){
        $locked = true;
    }
}

if(!$post){
    // Jika data postingan tidak ada di database, lempar juga ke halaman utama
    header("Location: https://idsrepair.com/", true, 301);
    exit();
}

/* KODE KOMENTAR DI SINI */
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['comment_submit'])){

    if(!isset($_SESSION['user_id'])){
        header("Location: /index.php");
        exit;
    }

    $repair_id = (int)$_POST['repair_id'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    $user_id = (int)$_SESSION['user_id'];
    $user_name = $_SESSION['user_name'] ?? 'Member';

    mysqli_query($conn,"
        INSERT INTO repair_comments
        (repair_id,user_id,user_name,comment)
        VALUES
        ('$repair_id','$user_id','$user_name','$comment')
    ");

    header("Location: /posts/view_repair/?id=".$repair_id."#comments");
    exit;
}

$totalKomentarQuery = mysqli_query($conn,"
SELECT COUNT(*) AS total
FROM repair_comments
WHERE repair_id='$id'
");

$jumlahKomentar = mysqli_fetch_assoc($totalKomentarQuery)['total'];

$comments = mysqli_query($conn,"
SELECT 
repair_comments.*,
tamu.nama AS nama_komentar,
tamu.foto_profil
FROM repair_comments
LEFT JOIN tamu
ON tamu.id = repair_comments.user_id
WHERE repair_comments.repair_id='$id'
ORDER BY repair_comments.id ASC
");

$title = $post['title'];
$content = $post['content'];
$image = $post['image'];
$video = $post['video'];
$desc = substr(strip_tags($content),0,160);

$firstImage = '';
if(!empty($image)){
    $imageListForOg = array_filter(array_map('trim', explode(',', $image)));
    $firstImage = reset($imageListForOg);
}

$ogImage = !empty($post['og_image'])
? cloudflareMediaUrl($post['og_image'], 'og_images')
: (!empty($firstImage)
    ? cloudflareMediaUrl($firstImage, 'repairs')
    : "https://cdn.idsrepair.com/css/og-banner2.png");
    
$imgExt = strtolower(pathinfo($ogImage, PATHINFO_EXTENSION));

if($imgExt == 'png'){
    $ogType = 'image/png';
}elseif($imgExt == 'webp'){
    $ogType = 'image/webp';
}elseif($imgExt == 'gif'){
    $ogType = 'image/gif';
}else{
    $ogType = 'image/jpeg';
}
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
$canonicalUrl = "https://idsrepair.com/posts/view_repair/?id=".$id;
?>
<!-- TITLE -->
<title><?= htmlspecialchars($title) ?> | IDS ECU REPAIR</title>
<link rel="canonical" href="<?= $canonicalUrl ?>">
<!-- SEO -->
<meta name="description" content="<?= htmlspecialchars($desc) ?>">
<meta property="og:image:alt" content="<?= htmlspecialchars($title) ?>">
<meta name="theme-color" content="#c00000">
<meta name="keywords" content="ECU Repair Indonesia, Servis ecu terdekat, servis ecu makassar, mks ecu repair, otomotif diesel makassar, File ECU, IMMO OFF, Cloning ECU, Repair ECU Soppeng, ECU Diesel, ECU Mobil, ECU Denso, ECU Bosch, Forum ECU Indonesia, Teknisi ECU">
<meta name="robots" content="index, follow">
<meta name="author" content="Jinbotol">
<!-- FACEBOOK / WHATSAPP -->
<meta property="og:url" content="<?= $canonicalUrl ?>">
<meta property="og:type" content="article">
<meta property="og:title" content="<?= htmlspecialchars($title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($desc) ?>">
<meta property="og:image" content="<?= htmlspecialchars($ogImage) ?>">
<meta property="og:image:secure_url" content="<?= htmlspecialchars($ogImage) ?>">
<meta property="og:image:type" content="<?= $ogType ?>">
<meta property="og:image:width" content="800">
<meta property="og:image:height" content="586">
<meta property="og:site_name" content="IDS ECU REPAIR SOPPENG">
<meta property="og:locale" content="id_ID">
<!-- TWITTER / X -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:domain" content="idsrepair.com">
<meta name="twitter:url" content="<?= $canonicalUrl ?>">
<meta name="twitter:title" content="<?= htmlspecialchars($title) ?>">
<meta name="twitter:description" content="<?= htmlspecialchars($desc) ?>">
<meta name="twitter:image" content="<?= htmlspecialchars($ogImage) ?>">
<!-- ICON -->
<link rel="icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/png">
<link rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/png">
<link rel="apple-touch-icon" href="https://cdn.idsrepair.com/css/ecu-logo.png">
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
}
body{
background:#0d0d0d;
color:#fff;
font-family:'Orbitron',sans-serif;
overflow-x:hidden;
font-size:14px;
}

.header{
background:#111;
padding:12px 15px;
border-bottom:1px solid #222;
display:flex;
justify-content:space-between;
align-items:center;
position:sticky;
top:0;
z-index:999;
border:none!important;
box-shadow:0 0 14px rgba(255,0,0,.45);
}

.header .logo{
font-size:17px;
margin-left:15px;
font-weight:bold;
color:#ff2b2b;
letter-spacing:1px;
}

.nav a{
display:inline-block;
padding:8px 12px;
background:#1b1b1b;
border:1px solid #333;
border-radius:8px;
color:#fff;
text-decoration:none;
font-size:12px;
margin-left:6px;
}

.nav a:hover{
background:#c00000;
}

.container{
width:100%;
max-width:750px;
margin:auto;
padding:15px;
}

.post-card{
background:#161616;
border:1px solid #242424;
border-radius:14px;
overflow:hidden;
}

/* SLIDER MEDIA */
.media-slider{
display:flex;
overflow-x:auto;
scroll-snap-type:x mandatory;
background:#000;
height:360px;
}

.media-slider::-webkit-scrollbar-thumb{
background:#444;
border-radius:10px;
}

.slide{
min-width:100%;
height:360px;
scroll-snap-align:start;
background:#000;
display:flex;
align-items:center;
justify-content:center;
}

.post-img,
.post-video,
.media-slider .post-video,
.media-slider iframe{
width:100%;
height:100%;
object-fit:contain;
display:block;
background:#000;
border:none;
}

.content{
padding:14px;
}

.pin-label{
display:inline-block;
background:#c00000;
color:#fff;
padding:5px 10px;
border-radius:8px;
font-size:11px;
margin-bottom:8px;
font-weight:bold;
}

.post-title{
font-size:22px;
font-weight:600;
color:#ff2b2b;
margin-bottom:12px;
line-height:1.5;
}

.post-info{
margin-top:14px;
margin-bottom:14px;
font-size:12px;
color:#888;
display:flex;
align-items:center;
gap:8px;
}

.author-img{
width:32px;
height:32px;
border-radius:50%;
object-fit:cover;
border:1px solid #ff2b2b;
}

.author-name{
color:#fff;
font-size:13px;
}

.author-date{
color:#888;
font-size:11px;
}

.post-content{
font-size:14px;
line-height:1.8;
color:#ccc;
word-break:break-word;
}

.ecu-file-box{
margin-top:18px;
background:#1b1b1b;
border:1px solid #2a2a2a;
border-radius:14px;
padding:14px;
display:flex;
justify-content:space-between;
align-items:center;
gap:12px;
flex-wrap:wrap;
}

.ecu-file-left{
display:flex;
align-items:center;
gap:12px;
overflow:hidden;
}

.ecu-file-icon{
width:42px;
height:42px;
border-radius:10px;
background:#2a2a2a;
display:flex;
align-items:center;
justify-content:center;
font-size:22px;
flex-shrink:0;
}

.ecu-file-info{
overflow:hidden;
}

.ecu-file-name{
font-size:13px;
font-weight:600;
color:#fff;
white-space:nowrap;
overflow:hidden;
text-overflow:ellipsis;
max-width:220px;
}

.ecu-file-size{
font-size:11px;
color:#888;
margin-top:4px;
}

.ecu-download-btn{
background:#c00000;
color:#fff;
padding:10px 14px;
border-radius:10px;
text-decoration:none;
font-size:12px;
font-weight:bold;
}

.ecu-download-btn:hover{
background:#ff2b2b;
}

.ecu-lock-btn{
background:#333;
color:#aaa;
padding:10px 14px;
border-radius:10px;
text-decoration:none;
font-size:12px;
font-weight:bold;
}

.btn-back{
display:inline-block;
margin-top:18px;
padding:10px 14px;
background:#c00000;
color:#fff;
border-radius:9px;
text-decoration:none;
font-size:12px;
}

.btn-back:hover{
background:#ff2b2b;
}

footer{
margin-top:25px;
padding:20px;
text-align:center;
background:#111;
border-top:1px solid #222;
color:#777;
font-size:13px;
}

@media(max-width:600px){
.logo{
font-size:15px;
}

.nav a{
font-size:11px;
padding:7px 10px;
}

.container{
padding:10px;
}

.post-title{
font-size:19px;
}

.post-content{
font-size:13px;
}

.post-img,
.post-video{
height:260px;
}

.ecu-file-name{
max-width:170px;
}
}
.post-card{
position:relative;
}

.locked-post > *:not(.lock-overlay){
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
.media-loading-spin{
width:34px!important;
height:34px!important;
border:4px solid #333!important;
border-top:4px solid red!important;
border-radius:50%!important;
animation:mediaSpin .8s linear infinite!important;
margin:15px auto!important;
display:block!important;
z-index:999!important;
}

@keyframes mediaSpin{
100%{transform:rotate(360deg);}
}

img:not(.no-loader),
video:not(.no-loader){
opacity:0;
transition:.3s;
}

img.media-loaded,
video.media-loaded{
opacity:1!important;
}
.media-slider iframe{
width:100%;
height:420px;
border:none;
background:#000;
display:block;
}

@media(max-width:600px){
.media-slider iframe{
height:260px;
}
}

.comment-section{
margin-top:18px;
background:#101010;
border-radius:10px;
padding:12px;
}

.comment-section h3{
font-size:15px;
margin-bottom:12px;
color:#ff3c3c;
}

.comment-item{
background:#1a1a1a;
padding:10px;
border-radius:8px;
margin-bottom:10px;
}

.comment-top{
display:flex;
align-items:center;
gap:10px;
margin-bottom:8px;
}

.comment-avatar{
width:38px;
height:38px;
border-radius:50%;
object-fit:cover;
border:1px solid #444;
background:#111;
}

.comment-name{
font-size:13px;
font-weight:bold;
color:#fff;
}

.comment-date{
font-size:11px;
color:#888;
}

.comment-text{
font-size:13px;
line-height:1.7;
color:#ccc;
margin-left:48px;
}

textarea{
width:100%;
height:80px;
resize:none;
outline:none;
background:#1b1b1b;
border:1px solid #333;
border-radius:8px;
padding:10px;
color:#fff;
font-size:13px;
margin-top:10px;
}

button{
background:#c00000;
border:none;
padding:10px 14px;
color:#fff;
border-radius:8px;
margin-top:10px;
cursor:pointer;
font-size:13px;
font-weight:bold;
}

.login-link{
color:#ff3c3c;
font-size:13px;
text-decoration:none;
}
</style>

</head>

<body>

<div class="header">

<div class="logo">
IDS ECU REPAIR
</div>

<div class="nav">
<a href="/">Home</a>
<a href="/posts/repair/">Semua Posting</a>
</div>

</div>

<div class="container">

<div class="post-card <?= $locked ? 'locked-post' : '' ?>">

<?php if($locked): ?>
<div class="lock-overlay">
<div>
🔒<br>
Login atau buat akun<br>
untuk melihat detail postingan ini
<br><br>
<a href="/index.php">Masuk / Daftar</a>
</div>
</div>
<?php endif; ?>

<?php if(!empty($post['image']) || !empty($post['video']) || !empty($post['youtube_url'])): ?>

<div class="media-slider">

<?php if(!empty($image)): ?>

<?php
$images = array_filter(array_map('trim', explode(',', $image)));
?>

<?php foreach($images as $img): ?>

<div class="slide">
<img class="post-img"
src="<?= htmlspecialchars(cloudflareMediaUrl($img, 'repairs')) ?>"
alt="<?= htmlspecialchars($title) ?>">
</div>

<?php endforeach; ?>

<?php endif; ?>

<?php if(!empty($video)): ?>
<div class="slide">
<video class="post-video" controls playsinline preload="metadata">
<source src="<?= htmlspecialchars(cloudflareMediaUrl($video, 'videos')) ?>" type="video/mp4">
</video>
</div>
<?php endif; ?>

<?php if(!empty($post['youtube_url'])): ?>

<?php $ytId = getYoutubeId($post['youtube_url']); ?>

<?php if($ytId): ?>

<div class="slide">

<iframe
class="post-video"
src="https://www.youtube.com/embed/<?= htmlspecialchars($ytId) ?>"
title="YouTube video"
frameborder="0"
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
allowfullscreen>
</iframe>

</div>

<?php endif; ?>

<?php endif; ?>

</div>

<?php endif; ?>

<div class="content">

<?php if(isset($post['pinned']) && $post['pinned'] == 1): ?>
<div class="pin-label">
📌 POSTINGAN TERSEMAT
</div>
<?php endif; ?>


<h1 class="post-title">
<?= htmlspecialchars($title) ?>
</h1>

<div class="post-info">

<img src="<?= !empty($post['foto_profil']) 
? htmlspecialchars($post['foto_profil']) 
: '/css/default-profile.png'; ?>"
class="author-img"
alt="Foto profil <?= htmlspecialchars($post['nama_author'] ?? $post['author'] ?? 'Member') ?>">
<div>
<div class="author-name">
<?= htmlspecialchars($post['nama_author'] ?? $post['author'] ?? 'Member') ?>
</div>

<div class="author-date">
<?= date('d M Y H:i', strtotime($post['created_at'])) ?>
</div>
</div>

</div>

<div class="post-content">
<?= nl2br(htmlspecialchars($content)) ?>
</div>

<div id="comments" class="comment-section">

<h3>💬 Komentar (<?= $jumlahKomentar ?>)</h3>

<?php if(mysqli_num_rows($comments) > 0): ?>
<?php while($c = mysqli_fetch_assoc($comments)): ?>

<div class="comment-item">

<div class="comment-top">

<img src="<?= !empty($c['foto_profil']) 
? $c['foto_profil'] 
: '/css/default-profile.png'; ?>"
class="comment-avatar">

<div>
<div class="comment-name">
<?= htmlspecialchars($c['nama_komentar'] ?? $c['user_name']) ?>
</div>

<small class="comment-date">
<?= date('d M Y H:i', strtotime($c['created_at'])) ?>
</small>
</div>

</div>

<p class="comment-text">
<?= nl2br(htmlspecialchars($c['comment'])) ?>
</p>

</div>

<?php endwhile; ?>
<?php else: ?>

<p style="color:#777;font-size:13px;">
Belum ada komentar.
</p>

<?php endif; ?>

<?php if(isset($_SESSION['user_id'])): ?>

<form method="POST">
<input type="hidden" name="repair_id" value="<?= $id ?>">
<textarea name="comment" placeholder="Tulis komentar atau pertanyaan..." required></textarea>
<button type="submit" name="comment_submit">Kirim Komentar</button>
</form>

<?php else: ?>

<a href="/index.php" class="login-link">
Login untuk komentar
</a>

<?php endif; ?>

</div>

<?php if(!empty($post['ecu_file'])): ?>

<div class="ecu-file-box">

<div class="ecu-file-left">

<div class="ecu-file-icon">
📦
</div>

<div class="ecu-file-info">

<div class="ecu-file-name">
<?= htmlspecialchars($post['ecu_file_original'] ?: $post['ecu_file']) ?>
</div>

<div class="ecu-file-size">
<?php
$size = (int)($post['ecu_file_size'] ?? 0);

if($size > 0){
    echo $size >= 1048576 ? round($size / 1048576,2).' MB' : round($size / 1024,2).' KB';
}else{
    $filePath = $_SERVER['DOCUMENT_ROOT'].'/uploads/ecu_files/'.$post['ecu_file'];

    if(file_exists($filePath)){
        $size = filesize($filePath);

        if($size >= 1048576){
            echo round($size / 1048576,2).' MB';
        }else{
            echo round($size / 1024,2).' KB';
        }
    }else{
        echo 'File tidak ditemukan';
    }
}
?>
</div>

</div>

</div>

<?php if(isset($_SESSION['user_id'])): ?>

<a href="/posts/download_ecu.php?id=<?= $post['id'] ?>"
class="ecu-download-btn">
⬇ Download
</a>

<?php else: ?>

<a href="/index.php"
class="ecu-lock-btn"
onclick="alert('Login dulu untuk download file ECU');">
🔒 Login untuk Download
</a>

<?php endif; ?>

</div>

<?php endif; ?>

<!--<a href="/posts/repair/" class="btn-back">
Kembali ke Semua Posting
</a>
-->
</div>

</div>

</div>

<footer>
© 2025 IDS ECU REPAIR SOPPENG
</footer>

<script>
document.addEventListener("DOMContentLoaded", function(){

    document.querySelectorAll("img:not(.no-loader)").forEach(function(img){

        let loader = document.createElement("div");
        loader.className = "media-loading-spin";

        img.parentNode.insertBefore(loader, img);

        function selesai(){
            img.classList.add("media-loaded");
            if(loader) loader.remove();
        }

        img.addEventListener("load", selesai);
        img.addEventListener("error", selesai);

        if(img.complete && img.naturalWidth > 0){
            selesai();
        }

    });

    document.querySelectorAll("video:not(.no-loader)").forEach(function(video){

        let loader = document.createElement("div");
        loader.className = "media-loading-spin";

        video.parentNode.insertBefore(loader, video);

        function selesai(){
            video.classList.add("media-loaded");
            if(loader) loader.remove();
        }

        video.addEventListener("loadeddata", selesai);
        video.addEventListener("error", selesai);

        if(video.readyState >= 2){
            selesai();
        }

    });

});
</script>
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
<script src="/js/theme-mode.js"></script>
</body>
</html>

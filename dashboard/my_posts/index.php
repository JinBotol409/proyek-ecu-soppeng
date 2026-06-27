<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

if (!isTamu()) {
    header("Location: ../");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

function getYoutubeId($url){
    if(empty($url)) return '';

    preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([^&\?\/]+)/', $url, $matches);

    return $matches[1] ?? '';
}

$posts = mysqli_query($conn,"
SELECT * FROM repair_posts
WHERE user_id='$user_id'
ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c00000">

<title>Postingan Saya</title>
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
</head>
<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
background:#0d0d0d;
font-family:Arial,sans-serif;
color:#fff;
}

.navbar{
position:sticky;
top:0;
z-index:999;
background:#111;
border-bottom:1px solid #222;
padding:12px 15px;
display:flex;
align-items:center;
justify-content:space-between;
}

.logo{
font-size:17px;
font-weight:bold;
color:#ff2b2b;
}

.nav-right{
display:flex;
gap:8px;
align-items:center;
}

.nav-btn{
background:#1c1c1c;
border:1px solid #333;
color:#fff;
padding:8px 12px;
border-radius:8px;
text-decoration:none;
font-size:12px;
}

.nav-btn:hover{
background:#ff2b2b;
}

.container{
width:100%;
max-width:750px;
margin:auto;
padding:15px;
}

.page-title{
font-size:17px;
color:#ff3c3c;
margin-bottom:14px;
}

.post{
background:#161616;
border:1px solid #242424;
border-radius:14px;
overflow:hidden;
margin-bottom:18px;
}

.post img{
width:100%;
max-height:240px;
object-fit:cover;
display:block;
}

.content{
padding:14px;
}

.title{
font-size:17px;
font-weight:bold;
color:#ff3c3c;
margin-bottom:8px;
line-height:1.4;
}

.desc{
font-size:13px;
color:#d2d2d2;
line-height:1.7;
}

.info{
margin-top:12px;
font-size:12px;
color:#888;
}

.action{
display:flex;
gap:8px;
margin-top:12px;
flex-wrap:wrap;
}

.action a{
display:inline-block;
padding:8px 12px;
border-radius:8px;
text-decoration:none;
color:white;
font-size:12px;
}

.view{
background:#333;
}

.delete{
background:#a00000;
}

.view:hover,
.delete:hover{
background:#ff2b2b;
}

.empty{
padding:50px 20px;
text-align:center;
color:#777;
font-size:14px;
}

@media(max-width:600px){
.logo{
font-size:15px;
}

.nav-btn{
font-size:11px;
padding:7px 10px;
}

.container{
padding:10px;
}

.title{
font-size:16px;
}

.post img{
max-height:210px;
}
}
.ecu-file-box{
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

.ecu-file-left{
display:flex;
align-items:center;
gap:10px;
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
font-size:20px;
flex-shrink:0;
}

.ecu-file-info{
overflow:hidden;
}

.ecu-file-name{
font-size:13px;
font-weight:bold;
color:#fff;
white-space:nowrap;
overflow:hidden;
text-overflow:ellipsis;
max-width:180px;
}

.ecu-file-size{
font-size:11px;
color:#888;
margin-top:3px;
}

.ecu-download-btn{
width:40px;
height:40px;
border-radius:10px;
background:#c00000;
display:flex;
align-items:center;
justify-content:center;
text-decoration:none;
color:#fff;
font-size:17px;
flex-shrink:0;
}

.ecu-download-btn:hover{
background:#ff2b2b;
}
.post-video{
width:100%;
max-height:220px;
object-fit:cover;
border-radius:8px;
margin-bottom:10px;
background:#000;
border:1px solid #333;
}
.media-slider{
display:flex;
overflow-x:auto;
scroll-snap-type:x mandatory;
background:#000;
}

.slide{
min-width:100%;
scroll-snap-align:start;
background:#000;
display:flex;
align-items:center;
justify-content:center;
}

.media-slider .post-img,
.media-slider .post-video,
.media-slider iframe{
width:100%;
height:240px;
object-fit:contain;
display:block;
background:#000;
border:none;
border-radius:0;
margin:0;
}

@media(max-width:600px){
.media-slider .post-img,
.media-slider .post-video,
.media-slider iframe{
height:210px;
}
}
</style>
<body>

<div class="navbar">

<div class="logo">
Postingan Saya
</div>

<div class="nav-right">

<a href="javascript:void(0)" 
onclick="document.referrer ? history.back() : window.location='/'"
class="nav-btn">
 Kembali
</a>

<a href="/posts/create_repair/" class="nav-btn">
+ Posting
</a>

</div>

</div>

<div class="container">

<div class="page-title">
🛠 Postingan ECU Milik Saya
</div>

<?php if(mysqli_num_rows($posts) > 0): ?>

<?php while($p = mysqli_fetch_assoc($posts)): ?>

<div class="post">

<?php if(!empty($p['image']) || !empty($p['video']) || !empty($p['youtube_url'])): ?>

<div class="media-slider">

<?php if(!empty($p['image'])): ?>

<?php
$images = array_filter(array_map('trim', explode(',', $p['image'])));
?>

<?php foreach($images as $img): ?>

<div class="slide">
<a href="/posts/view_repair/?id=<?= $p['id'] ?>">
<img class="post-img"
src="<?= htmlspecialchars($img) ?>">
</a>
</div>

<?php endforeach; ?>

<?php endif; ?>

<?php if(!empty($p['video'])): ?>
<div class="slide">
<video class="post-video" controls playsinline preload="metadata">
<source src="<?= htmlspecialchars($p['video']) ?>" type="video/mp4">
</video>
</div>
<?php endif; ?>

<?php if(!empty($p['youtube_url'])): ?>
<?php $ytId = getYoutubeId($p['youtube_url']); ?>

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

<div class="title">
<?= htmlspecialchars($p['title']) ?>
</div>

<div class="desc">
<?= nl2br(htmlspecialchars(substr($p['content'],0,180))) ?>...
</div>

<?php if(!empty($p['ecu_file'])): ?>

<div class="ecu-file-box">

<div class="ecu-file-left">

<div class="ecu-file-icon">
📦
</div>

<div class="ecu-file-info">

<div class="ecu-file-name">
<?= htmlspecialchars($p['ecu_file_original'] ?: $p['ecu_file']) ?>
</div>

<div class="ecu-file-size">

<?php
if(preg_match('/^https?:\/\//i', $p['ecu_file'])){
    echo 'Cloudflare R2';
}else{
    $filePath = $_SERVER['DOCUMENT_ROOT'].'/uploads/ecu_files/'.$p['ecu_file'];

    if(file_exists($filePath)){

        $size = filesize($filePath);

        if($size >= 1048576){
            echo round($size / 1048576,2).' MB';
        }else{
            echo round($size / 1024,2).' KB';
        }

    }
}
?>

</div>

</div>

</div>

<?php if(isset($_SESSION['user_id'])): ?>

<a href="/posts/download_ecu.php?id=<?= $p['id'] ?>"
class="ecu-download-btn">
⬇
</a>

<?php else: ?>

<a href="/"
class="ecu-download-btn"
style="background:#333;color:#aaa;"
onclick="alert('Login dulu untuk download file ECU');">
🔒
</a>

<?php endif; ?>
</div>

<?php endif; ?>

<div class="info">
<?= date('d M Y H:i', strtotime($p['created_at'])) ?>
</div>

<div class="action">

<a class="view" href="/posts/view_repair/?id=<?= $p['id'] ?>">
Lihat
</a>

<a class="delete"
href="/posts/delete_repair.php?id=<?= $p['id'] ?>"
onclick="return confirm('Yakin hapus postingan ini?')">
Hapus
</a>

</div>

</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty">
Kamu belum punya postingan.
</div>

<?php endif; ?>

</div>
<script src="/js/theme-mode.js"></script>
</body>
</html>

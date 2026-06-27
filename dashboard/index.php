<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isTamu()) {
    header("Location: ../");
    exit();
}

$tamu_id = (int)($_SESSION['user_id'] ?? 0);

$stmt_user = $conn->prepare("SELECT * FROM tamu WHERE id = ?");
$stmt_user->bind_param("i", $tamu_id);
$stmt_user->execute();
$user = $stmt_user->get_result()->fetch_assoc();

$userName = $user['nama'] ?? 'Member';
$userEmail = $user['email'] ?? '';

$totalPosting = 0;
$totalFile = 0;
$totalKomentar = 0;

$q1 = mysqli_query($conn,"SELECT COUNT(*) AS total FROM repair_posts WHERE user_id='$tamu_id'");
if($q1){ $totalPosting = mysqli_fetch_assoc($q1)['total']; }

$q2 = mysqli_query($conn,"SELECT COUNT(*) AS total FROM repair_posts WHERE user_id='$tamu_id' AND ecu_file!=''");
if($q2){ $totalFile = mysqli_fetch_assoc($q2)['total']; }

$q3 = mysqli_query($conn,"
SELECT COUNT(repair_comments.id) AS total
FROM repair_comments
INNER JOIN repair_posts
ON repair_posts.id = repair_comments.repair_id
WHERE repair_posts.user_id='$tamu_id'
");
if($q3){ $totalKomentar = mysqli_fetch_assoc($q3)['total']; }

$my_posts = mysqli_query($conn,"
SELECT 
repair_posts.*,
COUNT(repair_comments.id) AS total_comments
FROM repair_posts
LEFT JOIN repair_comments
ON repair_comments.repair_id = repair_posts.id
WHERE repair_posts.user_id='$tamu_id'
GROUP BY repair_posts.id
ORDER BY repair_posts.id DESC
LIMIT 6
");

$latest_comments = mysqli_query($conn,"
SELECT 
repair_comments.*,
repair_posts.title AS post_title,
repair_posts.id AS post_id,
tamu.nama AS nama_komentar,
tamu.foto_profil
FROM repair_comments
INNER JOIN repair_posts
ON repair_posts.id = repair_comments.repair_id
LEFT JOIN tamu
ON tamu.id = repair_comments.user_id
WHERE repair_posts.user_id='$tamu_id'
ORDER BY repair_comments.id DESC
LIMIT 5
");

$totalRemapOrders = 0;
$totalRemapPending = 0;
$totalRemapPaid = 0;

$emailSafeDash = mysqli_real_escape_string($conn, $userEmail);

$qRemapTotal = mysqli_query($conn,"SELECT COUNT(*) AS total FROM remap_orders WHERE user_id='$tamu_id' OR email='$emailSafeDash'");
if($qRemapTotal){ $totalRemapOrders = mysqli_fetch_assoc($qRemapTotal)['total']; }

$qRemapPending = mysqli_query($conn,"SELECT COUNT(*) AS total FROM remap_orders WHERE (user_id='$tamu_id' OR email='$emailSafeDash') AND payment_status='pending'");
if($qRemapPending){ $totalRemapPending = mysqli_fetch_assoc($qRemapPending)['total']; }

$qRemapPaid = mysqli_query($conn,"SELECT COUNT(*) AS total FROM remap_orders WHERE (user_id='$tamu_id' OR email='$emailSafeDash') AND payment_status='paid'");
if($qRemapPaid){ $totalRemapPaid = mysqli_fetch_assoc($qRemapPaid)['total']; }

$my_remap_orders = mysqli_query($conn,"
SELECT *
FROM remap_orders
WHERE user_id='$tamu_id' OR email='$emailSafeDash'
ORDER BY id DESC
LIMIT 10
");

$my_completed_remap_files = mysqli_query($conn,"
SELECT *
FROM remap_orders
WHERE (user_id='$tamu_id' OR email='$emailSafeDash')
  AND status='selesai'
  AND result_file IS NOT NULL
  AND result_file!=''
ORDER BY id DESC
LIMIT 10
");

$remap_notifications = mysqli_query($conn,"
SELECT *
FROM remap_notifications
WHERE user_id='$tamu_id'
ORDER BY id DESC
LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c00000">

<title>Dashboard Teknisi | IDS ECU REPAIR</title>
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
</head>
<style>
*{margin:0;padding:0;box-sizing:border-box;}

body{
background:#0d0d0d;
color:#fff;
font-family:Arial,sans-serif;
overflow-x:hidden;
}

.header{
position:sticky;
top:0;
z-index:999;
background:#111;
border-bottom:2px solid #c00000;
padding:9px 14px;
display:flex;
justify-content:space-between;
align-items:center;
border:none!important;
box-shadow:0 0 14px rgba(255,0,0,.45);
}

.header .logo{
color:#ff2b2b;
font-size:17px;
font-weight:bold;
margin-left:14px;
letter-spacing:1px;
}

.user-menu{
display:flex;
align-items:center;
gap:10px;
background:#1a1a1a;
padding:2px 6px;
border-radius:50px;
cursor:pointer;
}

.profile-img{
width:30px;
height:30px;
border-radius:50%;
object-fit:cover;
border:2px solid #ff2b2b;
}

.user-name{
font-size:13px;
font-weight:bold;
}

.user-role{
font-size:11px;
color:#999;
}

.dropdown-menu{
position:fixed;
top:75px;
right:18px;
width:280px;
background:#111;
border:1px solid #222;
border-radius:15px;
overflow:hidden;
display:none;
z-index:9999;
}

.dropdown-menu.active{
display:block;
}

.dropdown-profile{
padding:20px;
text-align:center;
border-bottom:1px solid #222;
}

.dropdown-img{
width:75px;
height:75px;
border-radius:50%;
object-fit:cover;
border:3px solid #ff2b2b;
margin-bottom:10px;
}

.dropdown-profile h3{
font-size:16px;
margin-bottom:4px;
}

.dropdown-profile p{
font-size:12px;
color:#999;
}

.dropdown-links a{
display:block;
padding:13px 18px;
text-decoration:none;
color:#ddd;
border-bottom:1px solid #1d1d1d;
font-size:14px;
}

.dropdown-links a:hover{
background:#1d1d1d;
color:#ff2b2b;
}

.logout-btn{
color:#ff4d4d!important;
}

.container{
max-width:1100px;
margin:auto;
padding:18px;
}

.hero-panel{
background:#141414;
border:1px solid #222;
border-radius:16px;
padding:18px;
margin-bottom:18px;
display:flex;
gap:15px;
align-items:center;
}

.hero-panel img{
width:78px;
height:78px;
border-radius:50%;
object-fit:cover;
border:3px solid #ff2b2b;
}

.hero-panel h1{
font-size:22px;
color:#ff2b2b;
margin-bottom:8px;
}

.hero-panel p{
font-size:14px;
color:#ccc;
line-height:1.6;
}

.quick-actions{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
gap:12px;
margin-bottom:18px;
}

.action-btn{
background:#c00000;
color:#fff;
text-decoration:none;
padding:14px;
border-radius:12px;
text-align:center;
font-size:13px;
font-weight:bold;
}

.action-btn.dark{
background:#1b1b1b;
border:1px solid #333;
}

.stats-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(160px,1fr));
gap:12px;
margin-bottom:18px;
}

.stat-card{
background:#141414;
border:1px solid #222;
border-radius:15px;
padding:18px;
text-align:center;
}

.stat-card h2{
font-size:30px;
color:#ff2b2b;
margin-bottom:6px;
}

.stat-card p{
font-size:13px;
color:#aaa;
}

.section{
background:#141414;
border:1px solid #222;
border-radius:16px;
padding:18px;
margin-bottom:18px;
}

.section h2{
color:#ff2b2b;
font-size:20px;
margin-bottom:15px;
}

.posts-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(240px,1fr));
gap:15px;
}

.post-card{
background:#1b1b1b;
border:1px solid #2a2a2a;
border-radius:14px;
overflow:hidden;
padding:12px;
}

.media-slider{
display:flex;
overflow-x:auto;
scroll-snap-type:x mandatory;
background:#000;
border-radius:10px;
margin-bottom:10px;
}

.slide{
min-width:100%;
scroll-snap-align:start;
display:flex;
align-items:center;
justify-content:center;
background:#000;
}

.post-img{
width:100%;
height:170px;
object-fit:cover;
display:block;
}

.post-video{
width:100%;
height:170px;
object-fit:contain;
display:block;
background:#000;
}

.post-title{
display:block;
color:#fff;
text-decoration:none;
font-size:15px;
font-weight:bold;
line-height:1.4;
margin-bottom:8px;
}

.post-title:hover{
color:#ff2b2b;
}

.post-desc{
font-size:13px;
color:#bbb;
line-height:1.6;
display:-webkit-box;
-webkit-line-clamp:3;
-webkit-box-orient:vertical;
overflow:hidden;
margin-bottom:10px;
}

.post-meta{
font-size:11px;
color:#888;
display:flex;
justify-content:space-between;
gap:8px;
}

.comment-item{
background:#1b1b1b;
border:1px solid #2a2a2a;
border-radius:12px;
padding:12px;
margin-bottom:10px;
}

.comment-top{
display:flex;
align-items:center;
gap:10px;
margin-bottom:8px;
}

.comment-top img{
width:36px;
height:36px;
border-radius:50%;
object-fit:cover;
border:1px solid #444;
}

.comment-name{
font-size:13px;
font-weight:bold;
color:#fff;
}

.comment-date{
font-size:11px;
color:#777;
}

.comment-text{
font-size:13px;
color:#ccc;
line-height:1.6;
margin-bottom:8px;
}

.comment-post{
font-size:12px;
color:#ff3c3c;
text-decoration:none;
}

.order-list{
display:flex;
flex-direction:column;
gap:12px;
}

.order-card{
background:#1b1b1b;
border:1px solid #2a2a2a;
border-radius:14px;
padding:14px;
}

.order-top{
display:flex;
justify-content:space-between;
gap:10px;
align-items:flex-start;
margin-bottom:10px;
}

.order-title{
font-size:15px;
font-weight:bold;
color:#fff;
line-height:1.4;
}

.order-date{
font-size:11px;
color:#888;
margin-top:4px;
}

.badge{
display:inline-block;
padding:6px 9px;
border-radius:999px;
font-size:11px;
font-weight:bold;
white-space:nowrap;
}

.badge.pending{
background:#5c4500;
color:#ffd35c;
}

.badge.paid{
background:#005c2a;
color:#7dffb4;
}

.badge.failed{
background:#5c0000;
color:#ff8a8a;
}

.order-info{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
gap:8px;
margin-bottom:10px;
}

.order-info div{
background:#101010;
border:1px solid #282828;
border-radius:10px;
padding:9px;
font-size:12px;
color:#bbb;
line-height:1.5;
}

.order-info b{
display:block;
color:#fff;
font-size:13px;
margin-top:3px;
word-break:break-word;
}

.order-actions{
display:flex;
gap:8px;
flex-wrap:wrap;
}

.order-btn{
display:inline-block;
background:#c00000;
color:#fff;
text-decoration:none;
padding:9px 11px;
border-radius:9px;
font-size:12px;
font-weight:bold;
}

.order-btn.dark{
background:#333;
}

.notification-item{
background:#1b1b1b;
border:1px solid #2a2a2a;
border-radius:12px;
padding:12px;
margin-bottom:10px;
}

.notification-title{
font-size:14px;
font-weight:bold;
color:#ff3c3c;
margin-bottom:6px;
}

.notification-message{
font-size:13px;
color:#ccc;
line-height:1.6;
}

.notification-date{
font-size:11px;
color:#777;
margin-top:7px;
}

.empty{
color:#888;
font-size:13px;
padding:10px 0;
}

.footer{
margin-top:28px;
padding:20px;
text-align:center;
background:#111;
border-top:1px solid #222;
color:#777;
font-size:13px;
}

.modal-bg{
position:fixed;
inset:0;
background:rgba(0,0,0,.75);
display:none;
justify-content:center;
align-items:center;
z-index:99999;
}

.modal-bg.active{
display:flex;
}

.modal{
background:#111;
padding:25px;
border-radius:15px;
width:90%;
max-width:400px;
border:1px solid #c00000;
position:relative;
}

.close{
position:absolute;
top:10px;
right:15px;
font-size:26px;
cursor:pointer;
}

.modal h3{
color:#ff2b2b;
margin-bottom:18px;
}

.form-row input{
width:100%;
padding:13px;
margin-bottom:12px;
border:none;
border-radius:8px;
background:#1d1d1d;
color:#fff;
border:1px solid #333;
}

.small-btn{
width:100%;
padding:13px;
border:none;
border-radius:8px;
background:#c00000;
color:#fff;
font-weight:bold;
cursor:pointer;
}

@media(max-width:700px){
.logo{font-size:16px;}
.user-role{display:none;}
.hero-panel{flex-direction:column;text-align:center;}
.dropdown-menu{width:92%;right:4%;}
.post-img,.post-video{height:180px;}
}
</style>
<body>

<div class="header">

<div class="logo">IDS ECU REPAIR</div>

<div class="user-menu" onclick="toggleMenu()">

<img src="<?= !empty($user['foto_profil']) 
? $user['foto_profil'] 
: '../css/default-profile.png'; ?>"
class="profile-img">

<div>
<div class="user-name"><?= htmlspecialchars($userName) ?></div>
<div class="user-role">Teknisi ECU</div>
</div>

</div>

</div>

<div class="dropdown-menu" id="menuModal">

<div class="dropdown-profile">

<img src="<?= !empty($user['foto_profil']) 
? $user['foto_profil'] 
: '../css/default-profile.png'; ?>"
class="dropdown-img">

<h3><?= htmlspecialchars($userName) ?></h3>
<p><?= htmlspecialchars($userEmail) ?></p>

</div>

<div class="dropdown-links">
<a href="/">🏠 Home</a>
<a href="/dashboard/my_posts/">🛠 Postingan Saya</a>
<a href="/posts/create_repair/">➕ Buat Posting ECU</a>
<a href="/posts/repair/">📄 Semua Posting ECU</a>
<a href="/posts/ecu_files/">📦 File ECU</a>
<a href="/dashboard/profile/">⚙️ Edit Profil</a>
<a href="#" onclick="openChangePass();return false;">🔒 Ganti Password</a>
<a href="../auth/logout.php" class="logout-btn">🚪 Logout</a>
</div>

</div>

<div class="container">

<div class="hero-panel">

<img src="<?= !empty($user['foto_profil']) 
? $user['foto_profil'] 
: '../css/default-profile.png'; ?>">

<div>
<h1>Panel Teknisi</h1>
<p>
Kelola postingan repair ECU, file ECU, komentar member,
dan profil teknisi dari halaman dashboard ini.
</p>
</div>

</div>

<div class="quick-actions">

<a href="/posts/create_repair/" class="action-btn">➕ Buat Posting</a>
<a href="/dashboard/my_posts/" class="action-btn dark">🛠 Postingan Saya</a>
<a href="/posts/repair/" class="action-btn dark">📄 Semua Posting</a>
<a href="/posts/ecu_files/" class="action-btn dark">📦 File ECU</a>
<a href="/dashboard/profile/" class="action-btn dark">⚙️ Edit Profil</a>
<a href="/remap/" class="action-btn dark">🛒 Order Remap</a>
<a href="#" onclick="openChangePass();return false;" class="action-btn dark">🔒 Ganti Password</a>


</div>

<div class="stats-grid">

<div class="stat-card">
<h2><?= (int)$totalPosting ?></h2>
<p>Total Postingan Saya</p>
</div>

<div class="stat-card">
<h2><?= (int)$totalFile ?></h2>
<p>File ECU Saya</p>
</div>

<div class="stat-card">
<h2><?= (int)$totalKomentar ?></h2>
<p>Komentar Masuk</p>
</div>

<div class="stat-card">
<h2><?= (int)$totalRemapOrders ?></h2>
<p>Order ECU Saya</p>
</div>

</div>

<div class="section">

<h2>🛒 Order ECU Saya</h2>

<?php if($my_remap_orders && mysqli_num_rows($my_remap_orders) > 0): ?>

<div class="order-list">

<?php while($o = mysqli_fetch_assoc($my_remap_orders)): ?>

<?php
$payStatus = $o['payment_status'] ?? 'pending';
$badgeClass = 'pending';
if($payStatus === 'paid') $badgeClass = 'paid';
if($payStatus === 'failed') $badgeClass = 'failed';
?>

<div class="order-card">

<div class="order-top">
<div>
<div class="order-title">
<?= htmlspecialchars($o['kendaraan'] ?? '-') ?> — <?= htmlspecialchars($o['ecu_type'] ?? '-') ?>
</div>
<div class="order-date">
Order #<?= (int)$o['id'] ?> • <?= date('d M Y H:i', strtotime($o['created_at'])) ?>
</div>
</div>

<div class="badge <?= $badgeClass ?>">
<?= htmlspecialchars(strtoupper($payStatus)) ?>
</div>
</div>

<div class="order-info">
<div>Status Order <b><?= htmlspecialchars($o['status'] ?? '-') ?></b></div>
<div>Total <b>Rp <?= number_format((int)($o['price'] ?? 0),0,',','.') ?></b></div>
<div>Reference <b><?= htmlspecialchars($o['duitku_reference'] ?? '-') ?></b></div>
</div>

<div class="order-info">
<div>Permintaan <b><?= nl2br(htmlspecialchars($o['permintaan'] ?? '-')) ?></b></div>
</div>

<div class="order-actions">

<?php if(!empty($o['ecu_file'])): ?>
<a href="<?= htmlspecialchars($o['ecu_file']) ?>" class="order-btn dark" download>
⬇ File Original
</a>
<?php endif; ?>

<?php if(!empty($o['66_file'])): ?>
<a href="<?= htmlspecialchars($o['result_file']) ?>" class="order-btn" download>
✅ Download Hasil Remap
</a>
<?php endif; ?>

<a href="/remap/" class="order-btn dark">
➕ Order Baru
</a>

</div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty">Belum ada order remap ECU.</div>

<?php endif; ?>

</div>


<div class="section">

<h2>✅ File Remap Saya</h2>

<?php if($my_completed_remap_files && mysqli_num_rows($my_completed_remap_files) > 0): ?>

<div class="order-list">

<?php while($f = mysqli_fetch_assoc($my_completed_remap_files)): ?>

<div class="order-card">

<div class="order-top">
<div>
<div class="order-title">
<?= htmlspecialchars($f['kendaraan'] ?? '-') ?> — <?= htmlspecialchars($f['ecu_type'] ?? '-') ?>
</div>
<div class="order-date">
Selesai • Order #<?= (int)$f['id'] ?> • <?= date('d M Y H:i', strtotime($f['created_at'])) ?>
</div>
</div>
<div class="badge paid">SELESAI</div>
</div>

<div class="order-info">
<div>File Original <b><?= htmlspecialchars($f['ecu_file_original'] ?? '-') ?></b></div>
<div>Hasil Remap <b><?= htmlspecialchars($f['result_file_original'] ?? $f['result_file'] ?? '-') ?></b></div>
</div>

<div class="order-actions">
<a href="<?= htmlspecialchars($f['result_file']) ?>" class="order-btn" download>
✅ Download File Remap
</a>
</div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty">Belum ada file remap yang selesai.</div>

<?php endif; ?>

</div>

<div class="section">

<h2>🔔 Notifikasi Order Remap</h2>

<?php if($remap_notifications && mysqli_num_rows($remap_notifications) > 0): ?>

<?php while($n = mysqli_fetch_assoc($remap_notifications)): ?>

<div class="notification-item">
<div class="notification-title">
<?= htmlspecialchars($n['title']) ?>
</div>

<div class="notification-message">
<?= nl2br(htmlspecialchars($n['message'])) ?>
</div>

<div class="notification-date">
<?= date('d M Y H:i', strtotime($n['created_at'])) ?>
</div>
</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty">Belum ada notifikasi order.</div>

<?php endif; ?>

</div>

<div class="section">

<h2>🛠 Postingan Saya Terbaru</h2>

<?php if(mysqli_num_rows($my_posts) > 0): ?>

<div class="posts-grid">

<?php while($p = mysqli_fetch_assoc($my_posts)): ?>

<div class="post-card">

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

</div>

<?php endif; ?>

<a class="post-title" href="/posts/view_repair/?id=<?= $p['id'] ?>">
<?= htmlspecialchars($p['title']) ?>
</a>

<div class="post-desc">
<?= nl2br(htmlspecialchars($p['content'])) ?>
</div>

<div class="post-meta">
<span><?= date('d M Y', strtotime($p['created_at'])) ?></span>
<span>💬 <?= (int)$p['total_comments'] ?></span>
</div>

</div>

<?php endwhile; ?>

</div>

<?php else: ?>

<div class="empty">Kamu belum membuat posting ECU.</div>

<?php endif; ?>

</div>

<div class="section">

<h2>💬 Komentar Terbaru di Postingan Saya</h2>

<?php if(mysqli_num_rows($latest_comments) > 0): ?>

<?php while($c = mysqli_fetch_assoc($latest_comments)): ?>

<div class="comment-item">

<div class="comment-top">

<img src="<?= !empty($c['foto_profil']) 
? htmlspecialchars($c['foto_profil']) 
: '/css/default-profile.png'; ?>">

<div>
<div class="comment-name">
<?= htmlspecialchars($c['nama_komentar'] ?? $c['user_name'] ?? 'Member') ?>
</div>

<div class="comment-date">
<?= date('d M Y H:i', strtotime($c['created_at'])) ?>
</div>
</div>

</div>

<div class="comment-text">
<?= nl2br(htmlspecialchars($c['comment'])) ?>
</div>

<a class="comment-post" href="/posts/repair/#post<?= $c['post_id'] ?>">
Lihat posting: <?= htmlspecialchars($c['post_title']) ?>
</a>

</div>

<?php endwhile; ?>

<?php else: ?>

<div class="empty">Belum ada komentar baru di postingan kamu.</div>

<?php endif; ?>

</div>

</div>

<div class="modal-bg" id="changePassModal">

<div class="modal">

<span class="close" onclick="closeChangePass()">×</span>

<h3>Ganti Password</h3>

<div class="form-row">
<input type="password" id="currentPassword" placeholder="Password Lama">
<input type="password" id="newPassword" placeholder="Password Baru">
<input type="password" id="confirmPassword" placeholder="Konfirmasi Password">
</div>

<button class="small-btn" onclick="submitChangePassword()">
Simpan Password
</button>

<div id="changePassMsg" style="margin-top:10px;"></div>

</div>

</div>

<div class="footer">
© 2025 IDS ECU REPAIR SOPPENG
</div>

<script>
function toggleMenu(){
document.getElementById('menuModal').classList.toggle('active');
}

window.addEventListener('click',function(e){
const menu = document.getElementById('menuModal');
const button = document.querySelector('.user-menu');

if(menu && button && !menu.contains(e.target) && !button.contains(e.target)){
menu.classList.remove('active');
}
});

function openChangePass(){
document.getElementById('changePassModal').classList.add('active');
document.getElementById('menuModal').classList.remove('active');
}

function closeChangePass(){
document.getElementById('changePassModal').classList.remove('active');
}

function submitChangePassword(){

const currentPassword = document.getElementById('currentPassword').value.trim();
const newPassword = document.getElementById('newPassword').value.trim();
const confirmPassword = document.getElementById('confirmPassword').value.trim();
const msgDiv = document.getElementById('changePassMsg');

msgDiv.style.color='red';

if(!currentPassword || !newPassword || !confirmPassword){
msgDiv.innerText = 'Semua kolom wajib diisi';
return;
}

if(newPassword !== confirmPassword){
msgDiv.innerText = 'Konfirmasi password tidak cocok';
return;
}

const formData = new FormData();
formData.append('currentPassword', currentPassword);
formData.append('newPassword', newPassword);

fetch('change_password.php',{
method:'POST',
body:formData,
credentials:'same-origin'
})
.then(res=>res.json())
.then(data=>{
if(data.success){
msgDiv.style.color='lime';
msgDiv.innerText = 'Password berhasil diubah';
}else{
msgDiv.innerText = data.message || 'Gagal mengubah password';
}
})
.catch(()=>{
msgDiv.innerText = 'Terjadi kesalahan server';
});
}
</script>
<script src="/js/theme-mode.js"></script>
</body>
</html>
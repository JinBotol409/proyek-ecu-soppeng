<?php
if($_SERVER['REQUEST_URI'] === '/index.php'){
    header("Location: /", true, 301);
    exit;
 }
// Header untuk penemuan agen AI (RFC 8288 & RFC 9727)
header('Link: </.well-known/api-catalog>; rel="api-catalog"; type="application/linkset+json"', false);
header('Link: </posts/repair/index.php>; rel="service-doc"; type="text/html"', false);
// 2. KODE BARU: Deteksi jika Bot AI meminta format Markdown (Content Negotiation)
$headers = function_exists('getallheaders') ? getallheaders() : [];
$acceptHeader = isset($headers['Accept']) ? $headers['Accept'] : (isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : '');

if (strpos($acceptHeader, 'text/markdown') !== false) {
    // Set Header respons sebagai Markdown sesuai permintaan Cloudflare
    header('Content-Type: text/markdown; charset=utf-8');
    header('x-markdown-tokens: 150'); // Estimasi token halaman utama Anda
    
    // Tampilkan isi konten versi teks/markdown sederhana untuk Bot AI
    echo "# IDS Repair\n";
    echo "Selamat datang di IDS Repair. Pusat panduan read and write ECU, remap prosedur order, dan perbaikan elektronik otomotif.\n\n";
    echo "## Menu Utama\n";
    echo "- [Panduan Read & Write ECU](/panduan-read-and-write-ecu/index.php)\n";
    echo "- [Prosedur Order Remap](/panduan-read-and-write-ecu/remap-prosedur-order/index.php)\n";
    exit; // Menghentikan loading HTML biasa agar bot hanya menerima markdown ini
}
session_start();
require_once(__DIR__ . '/includes/db.php');

$totalPost = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM repair_posts")
)['total'];

$totalMember = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM tamu")
)['total'];

$verifyError = $_SESSION['verify_error'] ?? '';
$verifySuccess = $_SESSION['verify_success'] ?? '';
unset($_SESSION['verify_error'], $_SESSION['verify_success']);

$loginError = $_SESSION['login_error'] ?? '';
$registerError = $_SESSION['register_error'] ?? '';
$registerSuccess = $_SESSION['register_success'] ?? '';
unset($_SESSION['login_error'], $_SESSION['register_error'], $_SESSION['register_success']);

$resetError = $_SESSION['reset_error'] ?? '';
$resetSuccess = $_SESSION['reset_success'] ?? '';

unset($_SESSION['reset_error'], $_SESSION['reset_success']);
$isLogin = isset($_SESSION['user_id']);

function getYoutubeId($url){

if(empty($url)) return '';

preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|youtube\.com\/shorts\/)([^&\?\/]+)/', $url, $matches);

return $matches[1] ?? '';

}

$latest_posts = mysqli_query($conn,"
SELECT 
repair_posts.*,
tamu.nama AS nama_author,
tamu.foto_profil,
COUNT(repair_comments.id) AS total_comments
FROM repair_posts
LEFT JOIN tamu
ON tamu.id = repair_posts.user_id
LEFT JOIN repair_comments
ON repair_comments.repair_id = repair_posts.id
GROUP BY repair_posts.id
ORDER BY repair_posts.pinned DESC, repair_posts.id DESC
LIMIT 5
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<!-- HTML Meta Tags -->
<title>IDS ECU REPAIR SOPPENG | Forum Repair ECU Indonesia</title>
<meta name="description" content="IDS ECU REPAIR SOPPENG adalah forum komunitas teknisi ECU Indonesia untuk berbagi pengalaman repair ECU, diagnosa kerusakan elektronik kendaraan, troubleshooting, dokumentasi pekerjaan dan diskusi otomotif.">
<meta name="theme-color" content="#c00000">
<meta name="keywords" content="Ids Repair, ECU Repair Indonesia, Servis ecu terdekat, servis ecu makassar, mks ecu repair, otomotif diesel makassar, File ECU, IMMO OFF, Cloning ECU, Repair ECU Soppeng, ECU Diesel, ECU Mobil, ECU Denso, ECU Bosch, Forum ECU Indonesia, Teknisi ECU">
<meta name="robots" content="index, follow">
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
<meta property="og:description" content="Forum komunitas repair ECU Indonesia untuk berbagi pengalaman repair, diagnosa kerusakan elektronik kendaraan, troubleshooting dan diskusi teknisi ECU.">
<meta property="og:image" content="https://cdn.idsrepair.com/css/og-banner.png">
<meta property="og:locale" content="id_ID">
<!-- Optimasi Tambahan Khusus WhatsApp -->
<meta property="og:image:secure_url" content="https://cdn.idsrepair.com/css/og-banner.png">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<!-- Twitter Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:domain" content="idsrepair.com">
<meta property="twitter:url" content="https://idsrepair.com">
<meta name="twitter:title" content="IDS ECU REPAIR SOPPENG | Forum Repair ECU Indonesia">
<meta name="twitter:description" content="Forum komunitas repair ECU Indonesia untuk berbagi pengalaman repair, diagnosa kerusakan elektronik kendaraan, troubleshooting dan diskusi teknisi ECU.">
<meta name="twitter:image" content="https://cdn.idsrepair.com/css/og-banner.png">
<meta name="viewport" content="width=device-width, initial-scale=1" />
<!-- Schema.org markup for Google -->
 <!-- ORGANIZATION -->
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"Organization",
  "name":"IDS ECU REPAIR SOPPENG",
  "alternateName":"IDS Repair",
  "url":"https://idsrepair.com",
  "logo":"https://cdn.idsrepair.com/css/ecu-logo.png",
  "image":"https://cdn.idsrepair.com/css/og-banner-ai-ecu.png",
  "description":"IDS ECU REPAIR SOPPENG adalah forum komunitas teknisi ECU Indonesia untuk berbagi pengalaman repair ECU, diagnosa kendaraan, troubleshooting, dokumentasi pekerjaan bengkel dan diskusi teknisi otomotif.",
  "keywords":"ECU Repair, Diagnosa ECU, Scanner Mobil, Troubleshooting ECU, Repair Elektronik Mobil, Forum Teknisi ECU Indonesia",
  "sameAs":[
    "https://www.facebook.com/profile.php?id=100063920717172",
    "https://youtube.com/@idrismidun"
  ],
  "address":{
    "@type":"PostalAddress",
    "streetAddress":"Jl. Poros Maros - Soppeng No.30",
    "addressLocality":"Soppeng",
    "addressRegion":"Sulawesi Selatan",
    "addressCountry":"ID"
  }
}
</script>

<!-- WEBSITE -->
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"WebSite",
  "name":"IDS ECU REPAIR",
  "url":"https://idsrepair.com",
  "potentialAction":{
    "@type":"SearchAction",
    "target":"https://idsrepair.com/search?q={search_term_string}",
    "query-input":"required name=search_term_string"
  }
}
</script>

<!-- FAQ -->
<script type="application/ld+json">
{
  "@context":"https://schema.org",
  "@type":"FAQPage",
  "mainEntity":[
    {
      "@type":"Question",
      "name":"Apa itu ECU mobil?",
      "acceptedAnswer":{
        "@type":"Answer",
        "text":"ECU adalah Electronic Control Unit yang mengatur sistem mesin kendaraan seperti injeksi, timing, turbo dan sensor kendaraan."
      }
    },
    {
      "@type":"Question",
      "name":"Apa fungsi remap ECU?",
      "acceptedAnswer":{
        "@type":"Answer",
        "text":"Remap ECU digunakan untuk meningkatkan performa, torsi, respons pedal dan efisiensi kendaraan melalui modifikasi software ECU."
      }
    },
    {
      "@type":"Question",
      "name":"Apa itu IMMO?",
      "acceptedAnswer":{
        "@type":"Answer",
        "text":"IMMO adalah sistem kunci digital pada ECU agar kendaraan dapat hidup dan berfungsi."
      }
    }
  ]
}
</script>
<link rel="canonical" href="https://idsrepair.com/">
<link rel="icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/png">
<link rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/x-icon">
<link rel="apple-touch-icon" href="https://cdn.idsrepair.com/css/ecu-logo.png">
<link data-rh="true" rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/x-icon">
<link rel="ai-policy" href="/.well-known/ai-policy.json">
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
<link rel="stylesheet" type="text/css" media="screen" href="/css/home.css?v=<?= time() ?>"/>
</head>
<body>
<header>
<div class="logo">IDS ECU REPAIR</div>
<div style="display:flex;align-items:center;gap:22px;">
<button id="themeToggle" class="theme-toggle" type="button">☀️</button>
<div class="menu-button" onclick="openMenu()">☰</div>
</div>
</header>

<div class="side-menu" id="sideMenu">

<div class="side-header">
<h2>MENU</h2>
<div class="close-btn" onclick="closeMenu()">×</div>
</div>

<div class="side-body">

<a href="/sport">
🏆 Sport
</a>

<a href="/posts/repair/">
🛠 Semua Posting ECU
</a>

<a href="/posts/ecu_files/">
📦 List File ECU
</a>

<a href="/panduan-read-and-write-ecu/">
🚀 Layanan Remap ECU
</a>

<a href="/panduan-read-and-write-ecu">
📘 Prosedur Remap ECU
</a>

<a href="/ai-ecu/">
🤖 Tanya AI ECU
</a>

<?php if($isLogin): ?>

<a href="/dashboard/">
👤 Dashboard
</a>

<a href="/dashboard/my_posts/">
📄 Postingan Saya
</a>

<a href="/posts/create_repair/">
➕ Buat Posting ECU
</a>

<?php
if(isset($_SESSION['user_id'])){

    $uid = (int)$_SESSION['user_id'];

    $cekAdmin = mysqli_query($conn,"
        SELECT role
        FROM tamu
        WHERE id='$uid'
        LIMIT 1
    ");

    $adminData = mysqli_fetch_assoc($cekAdmin);

    if($adminData && $adminData['role'] == 'admin'){
?>
<a href="/remap/admin/">
🖥 Admin Panel
</a>
<?php
    }
}
?>

<a href="/auth/logout.php">
🚪 Logout
</a>

<?php else: ?>

<a href="#" onclick="openLoginModal();closeMenu();return false;">
🔐 Login
</a>

<a href="#" onclick="openRegisterModal();closeMenu();return false;">
📝 Register
</a>

<?php endif; ?>

<div class="menu-contact-mini">

<img 
src="/css/ecu-logo.png"
alt="LOGO IDS Repair"
class="menu-mini-banner"
loading="lazy"
decoding="async">
<a class="menu-mini-email" href="mailto:admin@idsrepair.com">
admin@idsrepair.com
</a>

<a class="menu-mini-email" href="mailto:cs@idsrepair.com">
cs@idsrepair.com
</a>

</div>

</div>
</div>

<?php if($verifySuccess): ?>
<div class="alert alert-success">
<?= htmlspecialchars($verifySuccess) ?>
</div>
<?php endif; ?>

<?php if($verifyError): ?>
<div class="alert alert-error">
<?= htmlspecialchars($verifyError) ?>
</div>
<?php endif; ?>

<section class="hero">

<?php if($isLogin): ?>

<h4>
Selamat Datang, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Member') ?> 👋
</h4>

<p>
Semoga harimu menyenangkan. Silakan lihat update posting ECU terbaru,
dokumentasi repair, file ECU, dan diskusi teknisi di forum IDS ECU REPAIR SOPPENG.
</p>

<?php else: ?>

<div class="hero-banner">
<img src="https://cdn.idsrepair.com/css/ecu-logo.png" alt="IDS ecu Repair Soppeng">
</div>

<h1>IDS ECU REPAIR</h1>

<p>
Forum komunitas repair ECU Indonesia. Tempat berbagi hasil diagnosa, pengalaman repair ECU, troubleshooting kendaraan, diskusi teknisi, dan dokumentasi pekerjaan ECU mobil Indonesia.</p>

<div class="hero-stats">
<div>✔ <?= number_format($totalPost) ?> Posting ECU</div>
<div>✔ 200+ Anggota</div>
<div>✔ Update Harian</div>
</div>
<!--
<div class="hero-stats">
    <div>✔ <?= number_format($totalPost) ?> Posting ECU</div>
    <div>✔ <?= number_format($totalMember) ?> Anggota</div>
    <div>✔ Update Harian</div>
</div>
-->

<button onclick="openLoginModal()">MASUK FORUM</button>

<div class="scroll-indicator" id="scrollIndicator">
    <span>SCROLL</span>
    <div class="scroll-arrow"></div>
</div>

<?php endif; ?>

</section>

</section>


<h2 class="section-title">
🛠 Posting ECU Terbaru
</h2>

<section class="post-section">

<?php if(mysqli_num_rows($latest_posts) > 0): ?>

<?php while($p = mysqli_fetch_assoc($latest_posts)): ?>

<div class="post-card">

<div class="media-slider">

<?php if(!empty($p['image'])): ?>

<?php
$images = array_filter(array_map('trim', explode(',', $p['image'])));
?>

<?php foreach($images as $img): ?>

<?php
$imgSrc = trim($img);

if(!preg_match('/^https?:\/\//i', $imgSrc)){
    $imgSrc = 'https://cdn.idsrepair.com/repairs/'.ltrim($imgSrc, '/');
}
?>

<div class="slide">
<a href="/posts/view_repair/?id=<?= $p['id'] ?>">
<img class="post-img"
src="<?= htmlspecialchars($imgSrc) ?>"
loading="lazy"
alt="uploads ecu repair">
</a>
</div>

<?php endforeach; ?>

<?php endif; ?>

<?php if(!empty($p['video'])): ?>

<?php
$videoSrc = trim($p['video']);

if(!preg_match('/^https?:\/\//i', $videoSrc)){
    $videoSrc = 'https://cdn.idsrepair.com/videos/'.ltrim($videoSrc, '/');
}
?>

<div class="slide">
<video class="post-video" controls playsinline preload="none">
<source src="<?= htmlspecialchars($videoSrc) ?>" type="video/mp4">
</video>
</div>

<?php endif; ?>

<?php if(!empty($p['youtube_url'])): ?>

<?php $ytId = getYoutubeId($p['youtube_url']); ?>

<?php if($ytId): ?>

<div class="slide">

<iframe
class="post-video"
loading="lazy"
src="https://www.youtube-nocookie.com/embed/<?= htmlspecialchars($ytId) ?>"
title="YouTube video"
frameborder="0"
allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
allowfullscreen>
</iframe>

</div>

<?php endif; ?>

<?php endif; ?>

</div>

<div class="post-content">

<?php if($p['pinned'] == 1): ?>

<div style="
display:inline-block;
background:#c00000;
color:#fff;
padding:5px 10px;
border-radius:8px;
font-size:11px;
margin-bottom:8px;
font-weight:bold;
">
📌 POSTINGAN TERSEMAT
</div>

<?php endif; ?>

<h3>
<a href="/posts/view_repair/?id=<?= $p['id'] ?>">
<?= htmlspecialchars($p['title']) ?>
</a>
</h3>

<p>
<?= nl2br(htmlspecialchars(substr($p['content'],0,120))) ?>...
</p>

<?php if(!empty($p['ecu_file'])): ?>

<div class="ecu-file-box">

<div class="ecu-file-left">

<div class="ecu-file-icon">📦</div>

<div class="ecu-file-info">

<div class="ecu-file-name">
<?= htmlspecialchars($p['ecu_file_original'] ?: $p['ecu_file']) ?>
</div>

<div class="ecu-file-size">
<?php
$size = (int)($p['ecu_file_size'] ?? 0);

if($size > 0){
    echo $size >= 1048576 ? round($size / 1048576,2).' MB' : round($size / 1024,2).' KB';
}else{
    $filePath = $_SERVER['DOCUMENT_ROOT'].'/uploads/ecu_files/'.$p['ecu_file'];

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

<?php if($isLogin): ?>

<a href="/posts/download_ecu.php?id=<?= $p['id'] ?>"
class="ecu-download-btn">
⬇
</a>

<?php else: ?>

<a href="#"
onclick="openLoginModal();return false;"
class="ecu-lock-btn">
🔒
</a>

<?php endif; ?>

</div>

<?php endif; ?>

<div class="post-info">

<img class="author-img" src="<?= !empty($p['foto_profil'])
? $p['foto_profil']
: '/css/default-profile.png'; ?>" loading="lazy" alt="Author Profile">

<div>
<?= htmlspecialchars($p['nama_author'] ?? $p['author']) ?><br>
<?= date('d M Y H:i', strtotime($p['created_at'])) ?>
</div>

</div>

<a class="comment-link" href="/posts/view_repair/?id=<?= $p['id'] ?>#comments">
💬 <?= (int)$p['total_comments'] ?> komentar
</a>

</div>

</div>

<?php endwhile; ?>

<?php else: ?>

<p style="color:#888;padding:20px;">
Belum ada posting ECU.
</p>

<?php endif; ?>
</section>

<div class="view-all-posts">
<a href="/posts/repair/">
📚 LIHAT SEMUA POSTINGAN ECU
</a>
</div>

<section class="trust-banner-section">
<!--
  <h2 class="trust-title">
    🏆 Website Teruji & Terpercaya
  </h2>

  <p class="trust-desc">
    IDS ECU REPAIR telah lolos pengecekan SEO Google dan AI Agent Ready.
  </p>
-->
  <div class="trust-banner-box">
    <img 
      src="https://cdn.idsrepair.com/gallery/banner-trusted-ai-ready.png" 
      alt="IDS ECU Repair teruji terpercaya SEO Google dan AI Agent Ready"
      loading="lazy"
      class="trust-banner-img">
  </div>

</section>


<div class="info-section">

<div class="info-card">
<h2>Tentang IDS ECU REPAIR</h2>
<p>
IDS ECU REPAIR adalah ruang dokumentasi dan komunitas teknisi untuk berbagi hasil repair ECU,
diagnosa kerusakan ECU, IMMO, ABS, EPS, BCM, TCM, ETAC serta solusi elektronik kendaraan.
</p>
</div>

<div class="info-card">
<h2>Layanan Utama</h2>

<div class="service-grid">

<div class="service-box">
🛠 <b>Repair ECU</b><br>
ECU mati total, no start, short jalur, regulator, dan kerusakan komponen.
</div>

<div class="service-box">
⚡ <b>Cloning ECU</b><br>
Pemindahan data ECU lama ke ECU pengganti.
</div>

<div class="service-box">
🔐 <b>IMMO</b><br>
Penanganan immobilizer bermasalah untuk kebutuhan repair.
</div>

<div class="service-box">
📟 <b>Diagnosa DTC</b><br>
Diskusi kode error, no communication, CKP/CMP, dan fault ECU.
</div>

<div class="service-box">
🚀 <b>Peningkatan daya ECU</b><br>
Layanan tuning dan remap ECU diesel & bensin untuk meningkatkan performa dan efisiensi kendaraan.
</div>

<div class="service-box">
💾 <b>Read & Write ECU</b><br>
Panduan pembacaan dan penulisan data ECU dengan prosedur aman dan backup original file.
</div>

</div>
</div>

<div class="info-card">
<h2>Galeri IDS</h2>
<p>
Beberapa dokumentasi pekerjaan dan aktivitas repair ECU.
</p>

<div class="gallery-grid">

<img src="https://cdn.idsrepair.com/gallery/ecu1.jpg" loading="lazy" alt="IDS Repair">
<img src="https://cdn.idsrepair.com/gallery/ecu2.jpg" loading="lazy" alt="IDS Repair">
<img src="https://cdn.idsrepair.com/gallery/ecu3.jpg"  loading="lazy" alt="IDS Repair">
<video controls>
<source src="https://cdn.idsrepair.com/gallery/video1.mp4" type="video/mp4">
</video>

</div>
</div>

<div class="info-card">

<h2>
📍 Detail Lokasi Servis ECU Terdekat
</h2>

<p>
IDS ECU REPAIR SOPPENG melayani repair ECU mobil, truck, dan alat berat,
cloning ECU, IMMO, diagnosa kerusakan elektronik kendaraan,
dan file ECU tuning untuk berbagai jenis kendaraan.

Lokasi workshop berada di:

<br><br>

<b>
Jl. Poros Maros - Soppeng No.30,
Appanang, Kec. Lili Riaja,
Kabupaten Soppeng,
Sulawesi Selatan 90861
</b>

<br><br>

Kami menerima konsultasi kerusakan ECU,
no start, injector mati, no communication,
kerusakan jalur ECU, dan berbagai masalah elektronik kendaraan lainnya.

<br><br>

📞 WhatsApp:
<a href="https://wa.me/6282346783838"
target="_blank"
style="color:#25D366;text-decoration:none;">

0823-4678-3838

</a>

<br><br>

🗺️ Google Maps:
<a href="https://maps.app.goo.gl/bzv5ZUoat9ThzHX36"
target="_blank"
style="color:#ff3c3c;text-decoration:none;">

Lihat Lokasi Workshop

</a>

</p>

</div>
</div>

<div class="modal <?= ($loginError || $resetError || $resetSuccess) ? 'active' : '' ?>" id="loginModal">
<div class="modal-box">

<div class="modal-close" onclick="closeLoginModal()">×</div>

<h2>LOGIN</h2>

<?php if($resetSuccess): ?>
<div class="alert alert-success">
<?= htmlspecialchars($resetSuccess) ?>
</div>
<?php endif; ?>

<?php if($resetError): ?>
<div class="alert alert-error">
<?= htmlspecialchars($resetError) ?>
</div>
<?php endif; ?>

<?php if($loginError): ?>
<div class="alert alert-error">
<?= htmlspecialchars($loginError) ?>
</div>
<?php endif; ?>

<form method="POST" action="/auth/login.php">

<input type="email" name="email" placeholder="Email" required>

<input type="password" name="password" placeholder="Password" required>

<button type="submit">
MASUK
</button>
<!--
<div class="register-securityy">
    <img src="/css/security-icon7.gif" loading="lazy" alt="Secure">
</div>
-->

</form>
<div class="forgot-password">

<a href="#" onclick="openForgotModal();return false;">
🔑 Lupa password?
</a>

<div class="forgot-info">
Ganti password melalui email akun.
</div>

</div>

</div>
</div>

<div class="modal" id="forgotModal">
<div class="modal-box">
<div class="modal-close" onclick="closeForgotModal()">×</div>
<h2>RESET PASSWORD</h2>
<form method="POST" action="/auth/forgot-password.php">
<input type="email" name="email" placeholder="Email akun" required>
<button type="submit">KIRIM LINK RESET</button>
</form>
<div class="forgot-info1">
Reset password melalui email terdaftar.
</div>
</div>
</div>

<div class="modal <?= ($registerError || $registerSuccess) ? 'active' : '' ?>" id="registerModal">
<div class="modal-box">

<div class="modal-close" onclick="closeRegisterModal()">×</div>

<h2>REGISTER</h2>

<?php if($registerError): ?>
<div class="alert alert-error">
<?= htmlspecialchars($registerError) ?>
</div>
<?php endif; ?>

<?php if($registerSuccess): ?>
<div class="alert alert-success">
<?= htmlspecialchars($registerSuccess) ?>
</div>
<?php endif; ?>

<form method="POST" action="/auth/register.php">

<input type="text" name="nama" placeholder="Nama Lengkap" required>

<input type="email" name="email" placeholder="Email" required>

<input type="password" name="password" placeholder="Buat Password Akun" required>

<input type="password" name="confirm" placeholder="Konfirmasi Password Akun" required>

<button type="submit">
DAFTAR
</button>

<!--
<div class="register-security">
    <img src="/css/security-icon7.gif" loading="lazy" alt="Secure">
</div>
-->

</form>

</div>
</div>

<footer>
© 2025 IDS ECU REPAIR SOPPENG
</footer>

<script>
function openMenu(){
document.getElementById('sideMenu').classList.add('active');
}

function closeMenu(){
document.getElementById('sideMenu').classList.remove('active');
}

function openLoginModal(){
document.getElementById('loginModal').classList.add('active');
document.getElementById('registerModal').classList.remove('active');
}

function closeLoginModal(){
document.getElementById('loginModal').classList.remove('active');
}

function openRegisterModal(){
document.getElementById('registerModal').classList.add('active');
document.getElementById('loginModal').classList.remove('active');
}

function closeRegisterModal(){
document.getElementById('registerModal').classList.remove('active');
}

function openForgotModal(){
document.getElementById('loginModal').classList.remove('active');
document.getElementById('forgotModal').classList.add('active');
}

function closeForgotModal(){
document.getElementById('forgotModal').classList.remove('active');
}

window.onclick = function(e){

const login = document.getElementById('loginModal');
const register = document.getElementById('registerModal');
const forgot = document.getElementById('forgotModal');

if(e.target == login){
closeLoginModal();
}

if(e.target == register){
closeRegisterModal();
}

if(e.target == forgot){
closeForgotModal();
}

}
</script>

<script>
const scrollIndicator = document.getElementById("scrollIndicator");

window.addEventListener("scroll", function(){

    const scrollTop = window.scrollY;
    const pageHeight = document.body.scrollHeight - window.innerHeight;


    if(scrollTop > pageHeight - 200){
        scrollIndicator.classList.add("hide");
    }


    else if(scrollTop > 120){
        scrollIndicator.classList.add("hide");
    }


    else{
        scrollIndicator.classList.remove("hide");
    }

});
</script>

<script>
document.addEventListener("DOMContentLoaded", function(){

    document.querySelectorAll("img:not(.no-loader)").forEach(function(img){

        let loader = document.createElement("div");
        loader.className = "media-loading-spin";

        img.parentNode.insertBefore(loader, img);

        function selesai(){
            img.classList.add("media-loaded");
            loader.remove();
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
            loader.remove();
        }

        video.addEventListener("loadeddata", selesai);
        video.addEventListener("error", selesai);

        if(video.readyState >= 2){
            selesai();
        }

    });

});
</script>

<!-- Skrip WebMCP untuk Kesiapan Agen AI (Google Chrome & Cloudflare) -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Memastikan browser mendukung API modelContext (Secure HTTPS)
    if (window.navigator && window.navigator.modelContext && typeof window.navigator.modelContext.provideContext === 'function') {
        try {
            window.navigator.modelContext.provideContext({
                tools: [
                    {
                        name: "baca_panduan_idsrepair",
                        description: "Mengambil ringkasan materi panduan read and write ECU serta prosedur order remap di IDS Repair.",
                        inputSchema: {
                            type: "object",
                            properties: {
                                kategori: { 
                                    type: "string", 
                                    enum: ["ecu", "remap", "umum"],
                                    description: "Kategori panduan yang ingin dicari oleh agen AI." 
                                }
                            },
                            required: ["kategori"]
                        },
                        async execute({ kategori }) {
                            let rincian = "Selamat datang di IDS Repair. Hubungi kami untuk prosedur order.";
                            if (kategori === "ecu") {
                                rincian = "Panduan membaca dan menulis data ECU (Read & Write) aman menggunakan alat flasher standar industri.";
                            } else if (kategori === "remap") {
                                rincian = "Prosedur order remap file ECU wajib menyertakan data nomor software dan file backup asli bawaan mesin.";
                            }
                            return {
                                content: [{ type: "text", text: rincian }]
                            };
                        }
                    }
                ]
            });
            console.log("WebMCP Agent Capability successfully initialized.");
        } catch (e) {
            console.warn("WebMCP initialization warning:", e);
        }
    }
});
</script>
<script src="/js/theme-mode.js"></script>
</body>
</html>
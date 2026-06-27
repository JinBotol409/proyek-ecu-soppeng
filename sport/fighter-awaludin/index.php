<?php
if($_SERVER['REQUEST_URI'] === '/sport/fighter-awaludin/index.php'){
    header("Location: /sport/fighter-awaludin/", true, 301);
 exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<!-- HTML Meta Tags -->
<title>Awaludin Supernew MMA Soppeng</title>
<meta name="description" content="Profil Awaludin Supernew, fighter asal Soppeng yang tampil di NEX Road to Champion RTC Volume 2 kelas boxing 63,5 kg.">
<meta name="theme-color" content="#c00000">
<meta name="keywords" content="Profil Awaludin Supernew, fighter asal Soppeng , Champion RTC Volume 2, boxing 63,5 kg">
<meta name="robots" content="index, follow">
<meta name="author" content="Jinbotol">
<!-- Geo Location Meta Tags -->
<meta name="geo.region" content="ID-SN">
<meta name="geo.placename" content="Soppeng, Sulawesi Selatan">
<meta name="geo.position" content="-4.3519;119.8786">
<meta name="ICBM" content="-4.3519, 119.8786">
<!-- Facebook Meta Tags -->
<meta property="og:url" content="https://idsrepair.com/sport/fighter-awaludin/">
<meta property="og:type" content="website">
<meta property="og:title" content="Awaludin Supernew MMA Soppeng">
<meta property="og:description" content="Profil Awaludin Supernew, fighter asal Soppeng yang tampil di NEX Road to Champion RTC Volume 2 kelas boxing 63,5 kg.">
<meta property="og:image" content="https://cdn.idsrepair.com/fighter/awaluddin-banner.png">
<meta property="og:locale" content="id_ID">
<!-- Optimasi Tambahan Khusus WhatsApp -->
<meta property="og:image:secure_url" content="https://idsrepair.com">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<!-- Twitter Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:domain" content="idsrepair.com">
<meta property="twitter:url" content="https://idsrepair.com">
<meta name="twitter:title" content="Awaludin Supernew MMA Soppeng">
<meta name="twitter:description" content="Profil Awaludin Supernew, fighter asal Soppeng yang tampil di NEX Road to Champion RTC Volume 2 kelas boxing 63,5 kg.">
<meta name="twitter:image" content="https://cdn.idsrepair.com/fighter/awaluddin-banner.png">
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
</head>
<style>
*{margin:0;padding:0;box-sizing:border-box;}

body{
background:#0b0b0b;
color:#fff;
font-family:'Orbitron',sans-serif;
overflow-x:hidden;
}

.header{
position:sticky;
top:0;
z-index:999;
background:#111;
border-bottom:2px solid #c00000;
padding:17px 21px;
display:flex;
justify-content:space-between;
align-items:center;
border:none!important;
box-shadow:0 0 14px rgba(255,0,0,.45);
}

.logo{
font-size:16px;
font-weight:bold;
color:#ff2b2b;
letter-spacing:1px;
}

.nav a{
background:#1b1b1b;
border:1px solid #333;
color:#fff;
text-decoration:none;
padding:9px 12px;
border-radius:8px;
font-size:12px;
margin-left:6px;
}
.nav a:hover{
background:#c00000;
}

.hero{
min-height:360px;
padding:70px 20px 50px;
display:flex;
align-items:center;
justify-content:center;
text-align:center;
background:
linear-gradient(rgba(0,0,0,.65),rgba(0,0,0,.92)),
url('https://cdn.idsrepair.com/fighter/awaluddin-soppeng.png');
background-size:cover;
background-position:center;
}

.hero-content{
max-width:850px;
}

.hero-badge{
display:inline-block;
background:#c00000;
padding:7px 13px;
border-radius:30px;
font-size:12px;
margin-bottom:14px;
}

.hero h1{
font-size:34px;
color:#ff2b2b;
margin-bottom:12px;
text-shadow:0 0 15px rgba(255,0,0,.5);
}

.hero p{
font-size:14px;
line-height:1.8;
color:#ddd;
}

.container{
max-width:1000px;
margin:auto;
padding:18px;
}

.profile-card{
background:#141414;
border:1px solid #242424;
border-radius:16px;
padding:18px;
margin-top:-35px;
margin-bottom:18px;
display:flex;
gap:16px;
align-items:center;
}

.profile-card img{
width:120px;
height:120px;
border-radius:14px;
object-fit:cover;
border:2px solid #c00000;
}

.profile-text h2{
font-size:22px;
color:#ff2b2b;
margin-bottom:8px;
}

.profile-text p{
font-size:14px;
line-height:1.7;
color:#ccc;
}

.card{
background:#141414;
border:1px solid #242424;
border-radius:16px;
padding:18px;
margin-bottom:18px;
}

.card h2{
font-size:20px;
color:#ff2b2b;
margin-bottom:12px;
}

.card p{
font-size:14px;
line-height:1.8;
color:#ccc;
}

.info-list{
line-height:2;
font-size:14px;
color:#ccc;
}

.info-list b{
color:#fff;
}

.grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(210px,1fr));
gap:14px;
margin-top:12px;
}

.box{
background:#1d1d1d;
border:1px solid #2a2a2a;
border-radius:12px;
padding:14px;
}

.box span{
display:inline-block;
background:#c00000;
padding:5px 10px;
border-radius:20px;
font-size:11px;
margin-bottom:8px;
}

.box h3{
font-size:15px;
margin-bottom:7px;
}

.box p{
font-size:13px;
color:#aaa;
line-height:1.6;
}

.gallery{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:12px;
margin-top:12px;
}

.gallery img{
width:100%;
height:170px;
object-fit:cover;
border-radius:12px;
border:1px solid #333;
}

.btn{
display:inline-block;
margin-top:14px;
background:#c00000;
color:#fff;
padding:11px 15px;
border-radius:9px;
text-decoration:none;
font-size:13px;
}

.btn:hover{
background:#ff2b2b;
}

footer{
margin-top:30px;
padding:22px;
text-align:center;
background:#111;
border-top:1px solid #222;
color:#777;
font-size:13px;
}

@media(max-width:600px){
.hero h1{font-size:26px;}
.profile-card{flex-direction:column;text-align:center;}
.profile-card img{width:135px;height:135px;}
.logo{font-size:15px;}
.nav a{font-size:11px;padding:7px 9px;}
}
</style>
<body>

<div class="header">
<div class="logo">🥊 Fighter Soppeng</div>

<div class="nav">
<a href="/">Home</a>
<a href="/sport/">Sport</a>
</div>
</div>

<section class="hero">
<div class="hero-content">

<div class="hero-badge">
NEX Road to Champion Vol. 2
</div>

<h1>
Awaludin "Supernew"
</h1>

<p>
Fighter asal Soppeng, Sulawesi Selatan yang tampil dalam dunia combat sport,
boxing, MMA, dan kick boxing.
</p>

</div>
</section>

<div class="container">

<div class="profile-card">

<img src="https://cdn.idsrepair.com/fighter/awaluddin-soppeng.png" alt="Awaludin Supernew">

<div class="profile-text">

<h2>
Awaludin "Supernew"
</h2>

<p>
Awaludin dikenal sebagai fighter asal Kabupaten Soppeng,
Sulawesi Selatan. Ia membawa nama Super Camp Soppeng
dalam ajang combat sport dan boxing.
</p>

</div>
</div>

<div class="card">

<h2>
📌 Profil Singkat
</h2>

<div class="info-list">

<b>Nama:</b> Awaludin<br>

<b>Julukan:</b> Supernew<br>

<b>Asal:</b> Kabupaten Soppeng, Sulawesi Selatan<br>

<b>Sasana:</b> Super Camp Soppeng<br>

<b>Bidang:</b> Boxing, MMA, Kick Boxing<br>

<b>Kategori:</b> Combat Sport / Fighter<br>

</div>

</div>

<div class="card">

<h2>
🥊 Jadwal Pertandingan
</h2>

<p>
Awaludin <b>"Supernew"</b> dijadwalkan tampil dalam ajang
<b>NEX Road to Champion (RTC) Volume 2</b>.
Pertandingan ini menggunakan aturan boxing atau tinju
di kelas <b>63,5 kg</b>.
</p>

<br>

<div class="grid">

<div class="box">
<h2>Event</h2>
<h3>NEX RTC Vol. 2</h3>
<p>NEX Road to Champion Volume 2.</p>
</div>

<div class="box">
<h2>Kelas</h2>
<h3>63,5 KG</h3>
<p>Kategori pertandingan boxing / tinju.</p>
</div>

<div class="box">
<h2>Sasana</h2>
<h3>Super Camp</h3>
<p>Mewakili Kabupaten Soppeng, Sulawesi Selatan.</p>
</div>

<div class="box">
<h2>Siaran</h2>
<h3>Vidio PPV</h3>
<p>Pertandingan dapat disaksikan melalui Pay-Per-View di Vidio.</p>
</div>

</div>

</div>

<div class="card">

<h2>
📢 Informasi Event
</h2>

<p>
Pihak promotor merilis video profil kesiapan tanding Awaludin
pada pertengahan Mei 2026. Informasi detail fight card,
jadwal per hari, dan urutan pertandingan biasanya dirilis
secara bertahap melalui akun resmi event seperti
<b>@kartelkombat</b> dan <b>@combatsportindonesia</b>.
</p>

<a href="https://www.vidio.com/" target="_blank" class="btn">
Tonton via Vidio
</a>

</div>

<div class="card">

<h2>
💪 Mental Fighter
</h2>

<p>
Dunia fighter bukan hanya soal pukulan.
Seorang fighter membutuhkan disiplin,
mental kuat, stamina, teknik, dan konsistensi latihan.
Awaludin menjadi salah satu representasi semangat anak daerah
dalam dunia combat sport.
</p>

</div>

<!--<div class="card">

<h2>
📸 Galeri Fighter
</h2>

<p>
hhh
</p>

<div class="gallery">

<img src="/uploads/fighter/awaludin.jpg" alt="Awaludin Fighter">
<img src="/uploads/fighter/latihan1.jpg" alt="Latihan Fighter">
<img src="/uploads/fighter/latihan2.jpg" alt="Boxing Training">

</div>

</div> -->

</div>

<footer>
© 2025 IDS ECU REPAIR SOPPENG | Fighter Community
</footer>
<script src="/js/theme-mode.js"></script>
</body>
</html>

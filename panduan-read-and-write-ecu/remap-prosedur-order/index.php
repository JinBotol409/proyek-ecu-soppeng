<?php
if($_SERVER['REQUEST_URI'] === '/panduan-read-and-write-ecu/remap-prosedur-order/index.php'){
    header("Location: /panduan-read-and-write-ecu/remap-prosedur-order/", true, 301);
 exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- HTML Meta Tags -->
<title>Prosedur Remap ECU | IDS ECU REPAIR SOPPENG</title>
<meta name="description" content="Langkah-langkah dan prosedur order remap ECU IDS ECU REPAIR. Upload file ECU original, lakukan pembayaran, proses tuning, dan hasil dikirim melalui email.">
<meta name="theme-color" content="#c00000">
<meta name="keywords" content="Prosedur Remap ECU, Langkah Remap ECU, Remap ecu terdekat, Remap ecu Indonesia, Order Remap ECU, Tuning ECU, File ECU, IDS ECU REPAIR">
<meta name="robots" content="index, follow">
<meta name="author" content="Jinbotol">
<!-- GEO LOCATION -->
<meta name="geo.region" content="ID-SN">
<meta name="geo.placename" content="Soppeng, Sulawesi Selatan">
<meta name="geo.position" content="-4.3519;119.8786">
<meta name="ICBM" content="-4.3519, 119.8786">
<!-- Facebook Meta Tags -->
<meta property="og:url" content="https://idsrepair.com/panduan-read-and-write-ecu/remap-prosedur-order/">
<meta property="og:type" content="website">
<meta property="og:title" content="Prosedur Remap ECU | IDS ECU REPAIR">
<meta property="og:description" content="Panduan lengkap order remap ECU: upload file original, pembayaran, proses tuning, dan pengiriman hasil melalui email.">
<meta property="og:image" content="https://cdn.idsrepair.com/css/og-remap-prosedur-order1.png">
<meta property="og:locale" content="id_ID">
<!-- Optimasi Tambahan Khusus WhatsApp -->
<meta property="og:image:secure_url" content="https://cdn.idsrepair.com/css/og-remap-prosedur-order1.png">
<meta property="og:image:type" content="image/png">
<meta property="og:image:width" content="1300">
<meta property="og:image:height" content="630">
<!-- Twitter Meta Tags -->
<meta name="twitter:card" content="summary_large_image">
<meta property="twitter:domain" content="idsrepair.com">
<meta property="twitter:url" content="https://idsrepair.com/panduan-read-and-write-ecu/remap-prosedur-order/">
<meta name="twitter:title" content="Prosedur Remap ECU | IDS ECU REPAIR">
<meta name="twitter:description" content="Langkah-langkah order remap ECU IDSREPAIR dari upload file sampai hasil remap dikirim ke email.">
<meta name="twitter:image" content="https://cdn.idsrepair.com/css/og-remap-prosedur-order1.png">
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
<link rel="icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/png">
<link rel="shortcut icon" href="https://cdn.idsrepair.com/css/ecu-logo.png" type="image/x-icon">
<link rel="apple-touch-icon" href="https://cdn.idsrepair.com/css/ecu-logo.png">
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
</head>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
html{scroll-behavior:smooth;}
body{
background:
linear-gradient(rgba(0,0,0,.92),rgba(0,0,0,.94)),
url('https://cdn.idsrepair.com/css/bg-ecu.png');

font-family:Arial,sans-serif;
color:#fff;
overflow-x:hidden;
}
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
}

.back:hover{background:#c00000;}
.container{
width:100%;
max-width:880px;
margin:auto;
padding:16px;
}
.hero{
text-align:center;
padding:24px 0 15px;
}
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
.hero h1{
font-size:26px;
color:#ff2b2b;
line-height:1.35;
margin-bottom:12px;
}
.hero p{
font-size:14px;
line-height:1.8;
color:#ccc;
max-width:760px;
margin:auto;
}
.card{
background:#161616;
border:1px solid #242424;
border-radius:16px;
padding:18px;
margin-bottom:16px;
box-shadow:0 0 18px rgba(255,0,0,.04);
}
.card h2{
font-size:19px;
color:#ff3c3c;
margin-bottom:12px;
line-height:1.4;
}
.card p,.card li{
font-size:14px;
line-height:1.8;
color:#ccc;
}
.card ul{
padding-left:20px;
}
.step-list{
display:grid;
gap:13px;
}
.step{
background:#101010;
border:1px solid #2a2a2a;
border-radius:14px;
padding:14px;
display:flex;
gap:13px;
align-items:flex-start;
}
.step-num{
min-width:38px;
height:38px;
border-radius:50%;
background:#c00000;
color:#fff;
display:flex;
align-items:center;
justify-content:center;
font-weight:bold;
box-shadow:0 0 14px rgba(255,0,0,.25);
}
.step-body h3{
font-size:16px;
color:#fff;
margin-bottom:6px;
line-height:1.4;
}
.step-body p{
font-size:13px;
color:#bbb;
line-height:1.7;
}
.warn{
background:#120909;
border:1px solid #4a1515;
}
.info-grid{
display:grid;
grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
gap:12px;
margin-top:12px;
}
.info-box{
background:#101010;
border:1px solid #2a2a2a;
border-radius:12px;
padding:13px;
font-size:13px;
line-height:1.6;
color:#ddd;
}
.info-box b{
color:#ff3c3c;
}
.cta-wrap{
position:sticky;
bottom:0;
z-index:50;
background:linear-gradient(to top,#0d0d0d 75%,rgba(13,13,13,0));
padding:18px 0 10px;
margin-top:10px;
}
.cta-btn{
width:100%;
max-width:520px;
margin:auto;
display:block;
background:#c00000;
color:#fff;
border:none;
border-radius:14px;
padding:15px;
font-size:15px;
font-weight:bold;
text-decoration:none;
text-align:center;
box-shadow:0 0 20px rgba(255,0,0,.2);
}
.cta-btn:hover{
background:#ff2b2b;
}
.footer-note{
font-size:12px;
color:#777;
line-height:1.7;
text-align:center;
padding:10px 0 25px;
}
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
@media(max-width:600px){
.container{padding:14px;}
.banner{height:150px;}
.hero h1{font-size:21px;}
.hero p{font-size:13px;}
.card{padding:15px;}
.step{padding:13px;gap:10px;}
.step-num{min-width:34px;height:34px;font-size:13px;}
.step-body h3{font-size:15px;}
.cta-btn{font-size:14px;border-radius:12px;}
}
</style>
<body>

<div class="header">
    <div class="header-title">⚡ Prosedur Remap ECU</div>
    <a href="/" class="back">Home</a>
</div>

<div class="container">

<section class="hero">
    <img src="https://cdn.idsrepair.com/css/og-remap-prosedur-order.png" class="banner" alt="Prosedur Remap ECU IDS Repair">
    <h1>Langkah-Langkah Order Remap ECU</h1>
    <p>
        Panduan ini menjelaskan proses order remap ECU di IDS ECU REPAIR mulai dari persiapan file original,
        pengisian data kendaraan, pembayaran, proses tuning, sampai file hasil remap dikirim kembali ke email kamu.
    </p>
</section>

<div class="card">
    <h2>Sebelum Melakukan Order</h2>
    <p>
        Pastikan file ECU original sudah benar dan sesuai kendaraan. File bisa berasal dari proses read menggunakan
        alat programmer atau alat tuning yang mendukung ECU kendaraan kamu.
    </p>

    <div class="info-grid">
        <div class="info-box"><b>Format File</b><br>BIN, ORI, HEX, ZIP, atau RAR.</div>
        <div class="info-box"><b>Data Kendaraan</b><br>Isi jenis kendaraan dan tipe ECU dengan jelas.</div>
        <div class="info-box"><b>Email Aktif</b><br>Hasil remap dikirim ke email yang kamu masukkan.</div>
        <div class="info-box"><b>Catatan Request</b><br>Tulis permintaan seperti Stage 1, EGR OFF, DPF OFF, atau custom.</div>
    </div>
</div>

<div class="card">
    <h2>Prosedur Order Remap ECU</h2>

    <div class="step-list">

        <div class="step">
            <div class="step-num">1</div>
            <div class="step-body">
                <h3>Siapkan File ECU Original</h3>
                <p>Pastikan file ECU yang akan dikirim adalah file original hasil read dari kendaraan sebelum diedit.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">2</div>
            <div class="step-body">
                <h3>Isi Form Order Remap</h3>
                <p>Masukkan nama, WhatsApp, email penerima file, jenis kendaraan, tipe ECU, dan permintaan setting.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">3</div>
            <div class="step-body">
                <h3>Upload File ECU</h3>
                <p>Upload file ECU original. Jika file banyak, kompres terlebih dahulu menjadi ZIP atau RAR.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">4</div>
            <div class="step-body">
                <h3>Lakukan Pembayaran</h3>
                <p>Sistem akan mengarahkan ke halaman pembayaran Duitku. Pilih metode pembayaran yang tersedia.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">5</div>
            <div class="step-body">
                <h3>Order Masuk Proses</h3>
                <p>Setelah pembayaran berhasil, status order otomatis masuk proses. Notifikasi dikirim melalui email dan dashboard jika login.</p>
            </div>
        </div>

        <div class="step">
            <div class="step-num">6</div>
            <div class="step-body">
                <h3>File Hasil Dikirim</h3>
                <p>File hasil remap dikirim melalui email dan bisa dilihat di dashboard bagian file remap jika kamu login sebagai member.</p>
            </div>
        </div>

    </div>
</div>

<div class="card warn">
    <h2>Catatan Penting</h2>
    <ul>
        <li>Pastikan file ECU sesuai kendaraan dan tidak corrupt.</li>
        <li>Kesalahan file original bisa membuat proses tuning tertunda.</li>
        <li>Jelaskan permintaan setting dengan jelas agar hasil sesuai kebutuhan.</li>
        <li>Estimasi proses normal maksimal 1 x 24 jam setelah pembayaran berhasil.</li>
    </ul>
</div>

<div class="card">
    <h2>Contoh Permintaan Setting</h2>
    <p>
        Contoh penulisan permintaan: <b>Stage 1 harian, tenaga bawah tambah, throttle lebih responsif,
        EGR OFF, DPF OFF, tetap aman untuk harian.</b>
    </p>
</div>

<div class="cta-wrap">
    <a href="/remap/" class="cta-btn">LANJUTKAN REMAP ECU</a>
</div>

<div class="footer-note">
    IDS ECU REPAIR SOPPENG — Remap ECU, tuning file, dan penambahan tenaga mesin.
</div>

</div>

<script src="/js/theme-mode.js"></script>
</body>
</html>

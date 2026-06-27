<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
require_once("../../includes/db.php");
require_once("../../includes/functions.php");
require_once("../../vendor/autoload.php");

use Aws\S3\S3Client;

if (!isTamu()) {
    header("Location: ../");
    exit();
}

/* KONFIGURASI CLOUDFLARE R2 */
define("R2_ACCOUNT_ID", "5e6a1a66eede9317807017b1890dfeef");
define("R2_ACCESS_KEY_ID", "1c2affe26224f72a68031f51fd6faf0c");
define("R2_SECRET_ACCESS_KEY", "24f6cd698aa0c7381bae70a43cd553634f832886272256e5bf7c3041c859fa61");
define("R2_BUCKET", "idsrepair-images");
define("R2_PUBLIC_URL", "https://cdn.idsrepair.com");

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

function getMimeTypeSafe($path){
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path);
    finfo_close($finfo);
    return $mime ?: 'application/octet-stream';
}

function uploadToR2($localPath, $objectKey){
    $s3 = r2Client();
    $s3->putObject([
        'Bucket' => R2_BUCKET,
        'Key' => $objectKey,
        'SourceFile' => $localPath,
        'ContentType' => getMimeTypeSafe($localPath),
    ]);

    return rtrim(R2_PUBLIC_URL, '/').'/'.$objectKey;
}

function r2KeyFromUrl($url){
    if(empty($url)) return '';

    $base = rtrim(R2_PUBLIC_URL, '/').'/';

    if(strpos($url, $base) === 0){
        return ltrim(substr($url, strlen($base)), '/');
    }

    return '';
}

function deleteFromR2($url){
    $key = r2KeyFromUrl($url);
    if($key === '') return;

    try{
        $s3 = r2Client();
        $s3->deleteObject([
            'Bucket' => R2_BUCKET,
            'Key' => $key,
        ]);
    }catch(Exception $e){
        // Jangan hentikan update profil kalau hapus file lama gagal
    }
}

function profilePhotoUrl($foto){
    if(empty($foto)){
        return '/css/default-profile.png';
    }

    if(preg_match('/^https?:\/\//i', $foto)){
        return $foto;
    }

    return '/uploads/profile/'.ltrim($foto, '/');
}

$user_id = $_SESSION['user_id'];

$query = mysqli_query($conn,"
SELECT * FROM tamu
WHERE id='$user_id'
LIMIT 1
");

$user = mysqli_fetch_assoc($query);

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $nama = mysqli_real_escape_string($conn,$_POST['nama']);

    $foto = $user['foto_profil'];

    if(isset($_FILES['foto']) && $_FILES['foto']['error'] == 0){

        $allowedImage = ['jpg','jpeg','png','webp','gif'];
        $maxSize = 10 * 1024 * 1024;

        if($_FILES['foto']['size'] > $maxSize){
            die("Ukuran foto maksimal 10MB");
        }

        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));

        if(!in_array($ext, $allowedImage)){
            die("Format foto tidak didukung");
        }

        $newName = time().rand(100,999).".".$ext;
        $tmpPath = sys_get_temp_dir()."/ids_profile_".$newName;
        $objectKey = "profile/".$newName;

        if(!move_uploaded_file($_FILES['foto']['tmp_name'], $tmpPath)){
            die("Gagal upload foto sementara");
        }

        try{
            $newFotoUrl = uploadToR2($tmpPath, $objectKey);

            if(!empty($user['foto_profil'])){
                if(preg_match('/^https?:\/\//i', $user['foto_profil'])){
                    deleteFromR2($user['foto_profil']);
                }else{
                    $oldFile = "../../uploads/profile/".$user['foto_profil'];
                    if(file_exists($oldFile)){
                        unlink($oldFile);
                    }
                }
            }

            $foto = $newFotoUrl;

        }catch(Exception $e){
            @unlink($tmpPath);
            die("Cloudflare R2 error: ".$e->getMessage());
        }

        @unlink($tmpPath);
    }

    mysqli_query($conn,"
    UPDATE tamu
    SET
    nama='$nama',
    foto_profil='$foto'
    WHERE id='$user_id'
    ");

    $_SESSION['user_name'] = $nama;

    header("Location: /dashboard/profile/");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#c00000">

<title>Edit Profil</title>
<link rel="stylesheet" type="text/css" media="screen" href="/css/theme.css?v=<?= time() ?>"/>
</head>
<style>

body{
margin:0;
background:#0b0b0b;
font-family:Arial;
color:#fff;
}

.header{
background:#111;
padding:15px;
font-size:22px;
font-weight:bold;
border-bottom:2px solid red;
}

.container{
width:95%;
max-width:450px;
margin:30px auto;
}

.card{
background:#141414;
padding:25px;
border-radius:15px;
border:1px solid #222;
}

.profile-box{
text-align:center;
margin-bottom:25px;
position:relative;
}

.profile-box img{
width:130px;
height:130px;
border-radius:50%;
object-fit:cover;
border:3px solid red;
}

.edit-icon{
position:absolute;
bottom:5px;
right:35%;
background:red;
width:35px;
height:35px;
border-radius:50%;
display:flex;
align-items:center;
justify-content:center;
font-size:18px;
}

.form-group{
margin-bottom:18px;
}

.form-group label{
display:block;
margin-bottom:8px;
color:#aaa;
}

.form-group input{
width:100%;
padding:14px;
background:#1d1d1d;
border:1px solid #333;
border-radius:8px;
color:#fff;
box-sizing:border-box;
}

.save-btn{
width:100%;
padding:14px;
background:red;
border:none;
border-radius:8px;
color:white;
font-weight:bold;
cursor:pointer;
}

.save-btn:hover{
background:#ff2b2b;
}

.back{
display:inline-block;
margin-bottom:20px;
background:#222;
color:white;
padding:10px 15px;
border-radius:8px;
text-decoration:none;
}

</style>
<body>

<div class="header">
👤 Edit Profil
</div>

<div class="container">

<a href="javascript:void(0)" 
onclick="document.referrer ? history.back() : window.location='/'"
class="back">
 Kembali
</a>

<div class="card">

<form method="POST" enctype="multipart/form-data">

<div class="profile-box">

<img src="<?= htmlspecialchars(profilePhotoUrl($user['foto_profil'] ?? '')) ?>">

<div class="edit-icon">
✎
</div>

</div>

<div class="form-group">

<label>Foto Profil</label>

<input type="file" name="foto">

</div>

<div class="form-group">

<label>Nama Profil</label>

<input type="text"
name="nama"
value="<?= htmlspecialchars($user['nama']) ?>"
required>

</div>

<button type="submit" class="save-btn">
Simpan Perubahan
</button>

</form>

</div>

</div>
<script src="/js/theme-mode.js"></script>
</body>
</html>

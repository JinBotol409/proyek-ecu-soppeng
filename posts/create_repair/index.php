<?php
header("X-Robots-Tag: noindex, nofollow", true);
if($_SERVER['REQUEST_URI'] === '/posts/create_repair/index.php'){
    header("Location: /posts/create_repair/", true, 301);
 exit;
}
session_start();
require_once("../../includes/db.php");
require_once("../../vendor/autoload.php");

if(!isset($_SESSION['user_id'])){
    header("Location: /");
    exit;
}

use Aws\S3\S3Client;

/*
    FILE UPLOAD REPAIR ECU - VERSI CLOUDFLARE R2
    Catatan:
    - Struktur tampilan/form/JS tetap mengikuti file asli.
    - Yang diubah hanya penyimpanan media:
      IMAGE, OG_IMAGE, VIDEO, ECU_FILE -> Cloudflare R2
    - Jika proses gagal, postingan tidak dibuat.
*/

define("R2_ACCOUNT_ID", "5e6a1a66eede9317807017b1890dfeef");
define("R2_ACCESS_KEY_ID", "0b91ea800a96f1a7217bec08cc9f5b31");
define("R2_SECRET_ACCESS_KEY", "338ae723efa5c17a31e88ec8d5ab8d28bdfbbbd94a377bad52de3c0f9e9ec4fd");
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

function getMimeTypeSafe($path){
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path);
    finfo_close($finfo);
    return $mime ?: 'application/octet-stream';
}

function r2Client(){
    if(!class_exists('Aws\\S3\\S3Client')){
        throw new Exception("AWS SDK belum terpasang. Jalankan: composer require aws/aws-sdk-php");
    }

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

function deleteR2Object($objectKey){
    if(!$objectKey) return;

    try{
        $s3 = r2Client();
        $s3->deleteObject([
            'Bucket' => R2_BUCKET,
            'Key' => $objectKey,
        ]);
    }catch(Exception $e){
        // Abaikan agar proses error utama tetap tampil.
    }
}

function cleanUploadedFiles($imageName, $ogImageName, $videoName, $ecuFileName){

    $allUrls = [];

    if($imageName){
        $images = is_array($imageName) ? $imageName : explode(',', $imageName);
        foreach($images as $img){
            $img = trim($img);
            if($img){
                $allUrls[] = $img;
            }
        }
    }

    if($ogImageName){
        $ogs = is_array($ogImageName) ? $ogImageName : explode(',', $ogImageName);
        foreach($ogs as $og){
            $og = trim($og);
            if($og){
                $allUrls[] = $og;
            }
        }
    }

    if($videoName){
        $allUrls[] = $videoName;
    }

    if($ecuFileName){
        $allUrls[] = $ecuFileName;
    }

    foreach($allUrls as $url){
        $prefix = rtrim(R2_PUBLIC_URL, '/').'/';

        if(strpos($url, $prefix) === 0){
            $key = substr($url, strlen($prefix));
            deleteR2Object($key);
        }
    }
}

function failUpload($message, $imageName = '', $ogImageName = '', $videoName = '', $ecuFileName = '', $tempFiles = []){
    cleanUploadedFiles($imageName, $ogImageName, $videoName, $ecuFileName);

    foreach($tempFiles as $tmp){
        deleteIfExists($tmp);
    }

    http_response_code(400);
    echo $message;
    exit;
}

function createOgImage($sourcePath, $targetPath, $width = 800, $height = 586){

    if(!file_exists($sourcePath)){
        return false;
    }

    if(!ensureDir(dirname($targetPath))){
        return false;
    }

    /*
        Pakai ImageMagick CLI.
        Cocok dengan Termux setelah install:
        pkg install imagemagick
    */
    $cmd = "magick ".escapeshellarg($sourcePath).
           " -auto-orient".
           " -resize ".escapeshellarg($width."x".$height."^").
           " -gravity center".
           " -extent ".escapeshellarg($width."x".$height).
           " -quality 80 ".escapeshellarg($targetPath).
           " 2>&1";

    exec($cmd, $output, $result);

    if($result === 0 && file_exists($targetPath) && filesize($targetPath) > 0){
        return true;
    }

    return false;
}

if($_SERVER['REQUEST_METHOD'] == 'POST'){

    $title = mysqli_real_escape_string($conn, $_POST['title'] ?? '');
    $content = mysqli_real_escape_string($conn, $_POST['content'] ?? '');
    $youtube_url = trim($_POST['youtube_url'] ?? '');
    $youtube_url = mysqli_real_escape_string($conn, $youtube_url);

    $user_id = (int)$_SESSION['user_id'];
    $author = mysqli_real_escape_string($conn, $_SESSION['user_name'] ?? 'Member');

    $imageName = '';
    $ogImageName = '';
    $videoName = '';
    $ecuFileName = '';
    $ecuFileOriginal = '';
    $ecuFileSize = 0;

    $imageNames = [];
    $ogImageNames = [];
    $tempFiles = [];

    $maxSize = 80 * 1024 * 1024;

    $tempDir = sys_get_temp_dir()."/idsrepair_r2";
    ensureDir($tempDir);

    if(isset($_FILES['image'])){

        $allowedImage = ['jpg','jpeg','png','webp','gif'];
        $imageFiles = $_FILES['image'];

        if(is_array($imageFiles['name'])){

            $totalImages = 0;

            foreach($imageFiles['name'] as $idx => $name){
                if(($imageFiles['error'][$idx] ?? 4) === 0){
                    $totalImages++;
                }
            }

            if($totalImages > 10){
                failUpload("Maksimal upload 10 gambar dalam satu postingan.", '', '', '', '', $tempFiles);
            }

            foreach($imageFiles['name'] as $idx => $name){

                if(($imageFiles['error'][$idx] ?? 4) !== 0){
                    continue;
                }

                if($imageFiles['size'][$idx] > $maxSize){
                    failUpload("Ukuran gambar maksimal 80MB", $imageNames, $ogImageNames, $videoName, $ecuFileName, $tempFiles);
                }

                $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

                if(!in_array($ext, $allowedImage)){
                    failUpload("Format gambar tidak didukung", $imageNames, $ogImageNames, $videoName, $ecuFileName, $tempFiles);
                }

                $newImageName = time().'_'.rand(1000,9999).'_'.$idx.'.'.$ext;
                $imagePath = $tempDir."/".$newImageName;

                if(!move_uploaded_file($imageFiles['tmp_name'][$idx], $imagePath)){
                    failUpload("Gagal upload gambar", $imageNames, $ogImageNames, $videoName, $ecuFileName, $tempFiles);
                }

                $tempFiles[] = $imagePath;

                $imageUrl = uploadToR2($imagePath, "repairs/".$newImageName, getMimeTypeSafe($imagePath));

                $newOgName = 'og_'.pathinfo($newImageName, PATHINFO_FILENAME).'.jpg';
                $ogPath = $tempDir."/".$newOgName;

                if(!createOgImage($imagePath, $ogPath)){
                    failUpload("Gagal membuat OG image. Pastikan ImageMagick/magick aktif di server.", array_merge($imageNames, [$imageUrl]), $ogImageNames, $videoName, $ecuFileName, $tempFiles);
                }

                $tempFiles[] = $ogPath;

                $ogUrl = uploadToR2($ogPath, "og_images/".$newOgName, "image/jpeg");

                $imageNames[] = $imageUrl;
                $ogImageNames[] = $ogUrl;
            }

        }else{

            if($imageFiles['error'] == 0){

                if($imageFiles['size'] > $maxSize){
                    failUpload("Ukuran gambar maksimal 80MB", '', '', '', '', $tempFiles);
                }

                $ext = strtolower(pathinfo($imageFiles['name'], PATHINFO_EXTENSION));

                if(!in_array($ext, $allowedImage)){
                    failUpload("Format gambar tidak didukung", '', '', '', '', $tempFiles);
                }

                $newImageName = time().'_'.rand(1000,9999).'.'.$ext;
                $imagePath = $tempDir."/".$newImageName;

                if(!move_uploaded_file($imageFiles['tmp_name'], $imagePath)){
                    failUpload("Gagal upload gambar", '', '', '', '', $tempFiles);
                }

                $tempFiles[] = $imagePath;

                $imageUrl = uploadToR2($imagePath, "repairs/".$newImageName, getMimeTypeSafe($imagePath));

                $newOgName = 'og_'.pathinfo($newImageName, PATHINFO_FILENAME).'.jpg';
                $ogPath = $tempDir."/".$newOgName;

                if(!createOgImage($imagePath, $ogPath)){
                    failUpload("Gagal membuat OG image. Pastikan ImageMagick/magick aktif di server.", $imageUrl, '', $videoName, $ecuFileName, $tempFiles);
                }

                $tempFiles[] = $ogPath;

                $ogUrl = uploadToR2($ogPath, "og_images/".$newOgName, "image/jpeg");

                $imageNames[] = $imageUrl;
                $ogImageNames[] = $ogUrl;
            }
        }

        $imageName = implode(',', $imageNames);
        $ogImageName = $ogImageNames[0] ?? '';
    }

    if(isset($_FILES['video']) && $_FILES['video']['error'] == 0){

        if($_FILES['video']['size'] > $maxSize){
            failUpload("Ukuran video maksimal 80MB", $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
        }

        $allowedVideo = ['mp4','webm','ogg'];
        $extVideo = strtolower(pathinfo($_FILES['video']['name'], PATHINFO_EXTENSION));

        if(!in_array($extVideo, $allowedVideo)){
            failUpload("Format video tidak didukung", $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
        }

        $newVideoName = time().'_'.rand(1000,9999).'.'.$extVideo;
        $videoPath = $tempDir."/".$newVideoName;

        if(!move_uploaded_file($_FILES['video']['tmp_name'], $videoPath)){
            failUpload("Gagal upload video", $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
        }

        $tempFiles[] = $videoPath;
        $videoName = uploadToR2($videoPath, "videos/".$newVideoName, getMimeTypeSafe($videoPath));
    }

    if(isset($_FILES['ecu_file']) && $_FILES['ecu_file']['error'] == 0){

        if($_FILES['ecu_file']['size'] > $maxSize){
            failUpload("Ukuran file ECU maksimal 80MB", $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
        }

        $allowedFile = ['zip','rar'];
        $extFile = strtolower(pathinfo($_FILES['ecu_file']['name'], PATHINFO_EXTENSION));

        if(!in_array($extFile, $allowedFile)){
            failUpload("Format file ECU harus ZIP atau RAR", $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
        }

        $ecuFileOriginal = mysqli_real_escape_string($conn, $_FILES['ecu_file']['name']);
        $ecuFileSize = (int)$_FILES['ecu_file']['size'];
        $newEcuFileName = time().'_'.rand(1000,9999).'.'.$extFile;
        $ecuFilePath = $tempDir."/".$newEcuFileName;

        if(!move_uploaded_file($_FILES['ecu_file']['tmp_name'], $ecuFilePath)){
            $ecuFileOriginal = '';
            failUpload("Gagal upload file ECU", $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
        }

        $tempFiles[] = $ecuFilePath;
        $ecuFileName = uploadToR2($ecuFilePath, "ecu_files/".$newEcuFileName, getMimeTypeSafe($ecuFilePath));
    }

    $imageName = mysqli_real_escape_string($conn, $imageName);
    $ogImageName = mysqli_real_escape_string($conn, $ogImageName);
    $videoName = mysqli_real_escape_string($conn, $videoName);
    $ecuFileName = mysqli_real_escape_string($conn, $ecuFileName);

    $insert = mysqli_query($conn,"
        INSERT INTO repair_posts
        (user_id,title,content,image,og_image,video,youtube_url,ecu_file,ecu_file_original,ecu_file_size,author)
        VALUES
        ('$user_id','$title','$content','$imageName','$ogImageName','$videoName','$youtube_url','$ecuFileName','$ecuFileOriginal','$ecuFileSize','$author')
    ");

    foreach($tempFiles as $tmp){
        deleteIfExists($tmp);
    }

    if(!$insert){
        failUpload("Gagal simpan database: ".mysqli_error($conn), $imageName, $ogImageName, $videoName, $ecuFileName, $tempFiles);
    }

    echo "OK";
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Buat Posting ECU</title>

<style>
*{
margin:0;
padding:0;
box-sizing:border-box;
}

body{
margin:0;
background:#0f0f0f;
color:white;
font-family:Arial,sans-serif;
}

.header{
background:#151515;
padding:15px;
font-size:22px;
font-weight:bold;
border-bottom:2px solid red;
}

.container{
width:95%;
max-width:700px;
margin:25px auto;
}

.form-box{
background:#1a1a1a;
padding:20px;
border-radius:12px;
border:1px solid #2a2a2a;
}

input,
textarea{
width:100%;
padding:14px;
margin-bottom:15px;
border:none;
border-radius:8px;
background:#2a2a2a;
color:white;
box-sizing:border-box;
}

textarea{
min-height:180px;
resize:vertical;
}

button{
background:red;
color:white;
border:none;
padding:14px 20px;
border-radius:8px;
cursor:pointer;
width:100%;
font-size:16px;
font-weight:bold;
}

button:hover{
background:#ff2b2b;
}

.back{
display:inline-block;
margin-bottom:15px;
color:#ff3c3c;
text-decoration:none;
}

.label{
font-size:13px;
color:#aaa;
margin-bottom:6px;
display:block;
}

.note{
font-size:12px;
color:#777;
margin-top:-8px;
margin-bottom:14px;
line-height:1.5;
}

#uploadBox{
display:none;
position:fixed;
top:0;
left:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.88);
z-index:9999;
justify-content:center;
align-items:center;
flex-direction:column;
}

.upload-card{
width:85%;
max-width:340px;
background:#1b1b1b;
padding:20px;
border-radius:14px;
border:1px solid #333;
text-align:center;
}
</style>
</head>

<body>

<div class="header">
➕ Buat Posting ECU
</div>

<div class="container">

<a href="javascript:void(0)" 
onclick="document.referrer ? history.back() : window.location='/'"
class="back">
Kembali
</a>

<div class="form-box">

<form method="POST" enctype="multipart/form-data">

<input type="text" name="title" placeholder="Judul Posting ECU" required>

<textarea name="content" placeholder="Isi posting ECU..." required></textarea>

<label class="label">Upload Foto Repair</label>
<input type="file" name="image[]" accept="image/*" multiple>
<div class="note">Format gambar: JPG, PNG, WEBP, GIF. Bisa upload maksimal 10 gambar sekaligus. Urutan gambar mengikuti urutan pilihan file. OG image otomatis memakai gambar pertama.</div>

<label class="label">Upload Video Repair</label>
<input type="file" name="video" accept="video/mp4,video/webm,video/ogg">
<div class="note">Format video: MP4, WEBM, OGG. Maksimal 80MB.</div>

<label class="label">Link Video YouTube</label>
<input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=xxxxx">
<div class="note">Opsional. Tempel link YouTube agar video tampil di postingan tanpa upload file video.</div>

<label class="label">Upload File ECU (.ZIP / .RAR)</label>
<input type="file" name="ecu_file" accept=".zip,.rar">
<div class="note">File ECU wajib dikompres ZIP/RAR. Maksimal 80MB.</div>

<button type="submit">
POSTING SEKARANG
</button>

</form>

</div>

</div>

<div id="uploadBox">

<div class="upload-card">

<div style="font-size:17px;margin-bottom:15px;font-weight:bold;color:#fff;">
⬆ Uploading...
</div>

<div style="width:100%;height:18px;background:#2a2a2a;border-radius:30px;overflow:hidden;">

<div id="progressBar" style="width:0%;height:100%;background:#c00000;transition:.2s;">
</div>

</div>

<div id="progressText" style="margin-top:12px;font-size:15px;color:#aaa;">
0%
</div>

</div>

</div>

<script>
const form = document.querySelector("form");

form.addEventListener("submit", function(e){

    e.preventDefault();

    const imageInput = document.querySelector('input[name="image[]"]');

    if(imageInput && imageInput.files.length > 10){
        alert("Maksimal upload 10 gambar dalam satu postingan.");
        return;
    }

    const uploadBox = document.getElementById("uploadBox");
    const progressBar = document.getElementById("progressBar");
    const progressText = document.getElementById("progressText");

    uploadBox.style.display = "flex";

    const xhr = new XMLHttpRequest();

    xhr.upload.addEventListener("progress", function(e){

        if(e.lengthComputable){

            let percent = Math.round((e.loaded / e.total) * 100);

            progressBar.style.width = percent + "%";
            progressText.innerText = percent + "%";

        }

    });

    xhr.addEventListener("load", function(){

        const response = xhr.responseText.trim();

        if(xhr.status >= 200 && xhr.status < 400 && response === "OK"){

            progressBar.style.width = "100%";
            progressText.innerText = "Upload selesai";

            window.location.href = "/posts/repair/";

        }else{

            progressText.innerText = response || "Upload gagal. Cek server.";

        }

    });

    xhr.addEventListener("error", function(){
        progressText.innerText = "Upload gagal. Koneksi/server bermasalah.";
    });

    xhr.open("POST", "");
    xhr.send(new FormData(form));

});
</script>

</body>
</html>

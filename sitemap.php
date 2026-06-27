<?php

include 'includes/db.php';

header("Content-Type: application/xml; charset=UTF-8");

echo '<?xml version="1.0" encoding="UTF-8"?>';

?>

<urlset xmlns="https://www.sitemaps.org/schemas/sitemap/0.9">

<url>
<loc>https://idsrepair.com/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>daily</changefreq>
<priority>1.0</priority>
</url>

<?php
$query = mysqli_query($conn, "SELECT id, created_at FROM repair_posts ORDER BY id DESC");

while($row = mysqli_fetch_assoc($query)){
?>
<url>
<loc>https://idsrepair.com/posts/view_repair/?id=<?= $row['id'] ?></loc>
<lastmod><?= date('Y-m-d', strtotime($row['created_at'])) ?></lastmod>
<changefreq>daily</changefreq>
<priority>0.9</priority>
</url>
<?php } ?>

<url>
<loc>https://idsrepair.com/ai-ecu/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>daily</changefreq>
<priority>0.8</priority>
</url>

<url>
<loc>https://idsrepair.com/posts/repair/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>daily</changefreq>
<priority>0.8</priority>
</url>

<url>
<loc>https://idsrepair.com/remap/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>

<url>
<loc>https://idsrepair.com/panduan-read-and-write-ecu/remap-prosedur-order/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>

<url>
<loc>https://idsrepair.com/panduan-read-and-write-ecu/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>

<url>
<loc>https://idsrepair.com/sport/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>

<url>
<loc>https://idsrepair.com/sport/fighter-awaludin/</loc>
<lastmod><?= date('Y-m-d') ?></lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>

</urlset>
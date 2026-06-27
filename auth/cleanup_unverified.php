<?php
header("X-Robots-Tag: noindex, nofollow", true);
// cleanup_unverified.php

date_default_timezone_set('Asia/Jakarta');

require_once '/data/data/com.termux/files/usr/share/apache2/default-site/htdocs/website_u/idsrepair/includes/db.php';

$logFile = '/data/data/com.termux/files/usr/share/apache2/default-site/htdocs/website_u/idsrepair/auth/cleanup_log.txt';

$currentTime = date('Y-m-d H:i:s');

file_put_contents(
    $logFile,
    "Cron job started at " . $currentTime . "\n",
    FILE_APPEND
);

/*
    PENTING:
    Jangan pakai NOW() dari MySQL.
    Kita pakai waktu PHP Asia/Jakarta supaya tidak beda timezone.
*/

$query = "
SELECT id, nama, email, otp_expired
FROM tamu
WHERE is_verified = 0
AND otp_expired IS NOT NULL
AND otp_expired < ?
";

$stmt = $conn->prepare($query);

if (!$stmt) {
    file_put_contents(
        $logFile,
        "Error prepare SELECT: " . $conn->error . "\n",
        FILE_APPEND
    );
    exit;
}

$stmt->bind_param("s", $currentTime);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    while ($row = $result->fetch_assoc()) {

        $userId = (int)$row['id'];
        $userName = $row['nama'];
        $userEmail = $row['email'];
        $expiredTime = $row['otp_expired'];

        $deleteQuery = "
        DELETE FROM tamu
        WHERE id = ?
        AND is_verified = 0
        ";

        $deleteStmt = $conn->prepare($deleteQuery);

        if (!$deleteStmt) {
            file_put_contents(
                $logFile,
                "Error prepare DELETE ID $userId: " . $conn->error . "\n",
                FILE_APPEND
            );
            continue;
        }

        $deleteStmt->bind_param("i", $userId);
        $deleteStmt->execute();

        if ($deleteStmt->affected_rows > 0) {
            file_put_contents(
                $logFile,
                "Dihapus: ID $userId | $userEmail | $userName | expired: $expiredTime | current: $currentTime\n",
                FILE_APPEND
            );
        } else {
            file_put_contents(
                $logFile,
                "Tidak jadi hapus: ID $userId | $userEmail\n",
                FILE_APPEND
            );
        }

        $deleteStmt->close();
    }

} else {

    file_put_contents(
        $logFile,
        "Tidak ada akun expired untuk dihapus. current: $currentTime\n",
        FILE_APPEND
    );
}

$stmt->close();
$conn->close();

file_put_contents(
    $logFile,
    "Cron job selesai at " . date('Y-m-d H:i:s') . "\n\n",
    FILE_APPEND
);

echo "Cleanup selesai.";
?>
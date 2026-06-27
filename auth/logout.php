<?php
header("X-Robots-Tag: noindex, nofollow", true);
session_start();
session_unset();
session_destroy();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Logging out...</title>
    <script>
        // Redirect parent window (bukan iframe) ke home
        if (window.top !== window.self) {
            // Jika di iframe, redirect parent
            window.top.location.href = '/';
        } else {
            // Jika bukan iframe, redirect current page
            window.location.href = '/';
        }
    </script>
</head>
<body>
    <p>Logging out... Please wait.</p>
</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <title>Test CSS Reload</title>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= str_replace(".", "", microtime(true)) ?>">
</head>
<body>
    <h1>Test CSS Reload</h1>
    <p>Jika CSS berubah warna background, maka cache-busting bekerja!</p>
    <p>Timestamp: <?= str_replace(".", "", microtime(true)) ?></p>
</body>
</html></content>
<parameter name="filePath">c:\xampp\htdocs\f1q\test_css.php
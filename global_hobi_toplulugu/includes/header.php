<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Global Hobi Topluluğu</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Global Hobi Topluluğu Yönetim Sistemi</h1>
        </header>
        <nav>
            <ul>
                <li><a href="index.php">Ana Sayfa</a></li>
                <li><a href="uyeler.php">Üyeler</a></li>
                <li><a href="gruplar.php">Hobi Grupları</a></li>
                <li><a href="etkinlikler.php">Etkinlikler</a></li>
                <li><a href="ekipmanlar.php">Ekipmanlar</a></li>
                <li><a href="kaynaklar.php">Bilgi Kaynakları</a></li>
            </ul>
        </nav>
        <main>
            <?php
            // Başarı veya hata mesajlarını burada gösterebiliriz
            if (isset($_GET['message'])) {
                echo '<div class="message success">' . htmlspecialchars($_GET['message']) . '</div>';
            }
            if (isset($_GET['error'])) {
                echo '<div class="message error">' . htmlspecialchars($_GET['error']) . '</div>';
            }
            ?>
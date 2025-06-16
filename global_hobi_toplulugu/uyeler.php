<?php
include_once 'config.php'; // Veritabanı bağlantısı
include_once 'includes/header.php'; // HTML başlığı ve navigasyon

// Mesaj değişkenini tanımla
$message = '';
$error = '';

// Yeni Üye Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['uye_ekle'])) {
    $ad = $_POST['ad'];
    $soyad = $_POST['soyad'];
    $kullanici_adi = $_POST['kullanici_adi'];
    $eposta = $_POST['eposta'];
    $sifre = $_POST['sifre']; // Şifre hash'lenmeden önce
    $ulke = $_POST['ulke'];
    $profil_fotografi_url = $_POST['profil_fotografi_url'];

    // Şifreyi hash'le (güvenlik için)
    $sifre_hash = password_hash($sifre, PASSWORD_DEFAULT);

    // Saklı yordamı çağır
    $stmt = mysqli_prepare($conn, "CALL sp_uye_ekle(?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssssss", $ad, $soyad, $kullanici_adi, $eposta, $sifre_hash, $ulke, $profil_fotografi_url);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Üye başarıyla eklendi.";
    } else {
        $error = "Hata: Üye eklenirken bir sorun oluştu. " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Üyeleri Listeleme İşlemi (Saklı yordam ile)
$uyeler = [];
$stmt = mysqli_prepare($conn, "CALL sp_uyeleri_listele()");
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $uyeler[] = $row;
    }
} else {
    $error .= " Hiç üye bulunamadı.";
}
mysqli_stmt_close($stmt);
?>

<h2>Üyeler</h2>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<h3>Yeni Üye Ekle</h3>
<form action="uyeler.php" method="POST">
    <div class="form-group">
        <label for="ad">Ad:</label>
        <input type="text" id="ad" name="ad" required>
    </div>
    <div class="form-group">
        <label for="soyad">Soyad:</label>
        <input type="text" id="soyad" name="soyad" required>
    </div>
    <div class="form-group">
        <label for="kullanici_adi">Kullanıcı Adı:</label>
        <input type="text" id="kullanici_adi" name="kullanici_adi" required>
    </div>
    <div class="form-group">
        <label for="eposta">E-posta:</label>
        <input type="email" id="eposta" name="eposta" required>
    </div>
    <div class="form-group">
        <label for="sifre">Şifre:</label>
        <input type="password" id="sifre" name="sifre" required>
    </div>
    <div class="form-group">
        <label for="ulke">Ülke:</label>
        <input type="text" id="ulke" name="ulke">
    </div>
    <div class="form-group">
        <label for="profil_fotografi_url">Profil Fotoğrafı URL:</label>
        <input type="text" id="profil_fotografi_url" name="profil_fotografi_url">
    </div>
    <button type="submit" name="uye_ekle" class="btn">Üye Ekle</button>
</form>

<h3>Mevcut Üyeler</h3>
<?php if (!empty($uyeler)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ad Soyad</th>
                <th>Kullanıcı Adı</th>
                <th>E-posta</th>
                <th>Ülke</th>
                <th>Kayıt Tarihi</th>
                <th>Eylemler</th> </tr>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($uyeler as $uye): ?>
                <tr>
                    <td><?php echo htmlspecialchars($uye['uye_id']); ?></td>
                    <td><?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad']); ?></td>
                    <td><?php echo htmlspecialchars($uye['kullanici_adi']); ?></td>
                    <td><?php echo htmlspecialchars($uye['eposta']); ?></td>
                    <td><?php echo htmlspecialchars($uye['ulke']); ?></td>
                    <td><?php echo htmlspecialchars($uye['kayit_tarihi']); ?></td>
                    <td>
                        <a href="uye_grup_yonet.php?uye_id=<?php echo htmlspecialchars($uye['uye_id']); ?>" class="btn">Grupları Yönet</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Henüz kayıtlı üye bulunmamaktadır.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php'; // HTML altbilgisi
?>
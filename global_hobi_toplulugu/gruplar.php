<?php
include_once 'config.php'; // Veritabanı bağlantısı
include_once 'includes/header.php'; // HTML başlığı ve navigasyon

$message = '';
$error = '';

// Yeni Hobi Grubu Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['grup_ekle'])) {
    $grup_adi = $_POST['grup_adi'];
    $aciklama = $_POST['aciklama'];
    $kurulus_tarihi = $_POST['kurulus_tarihi'];
    $grup_yoneticisi_uye_id = $_POST['grup_yoneticisi_uye_id']; // Seçilen üyenin ID'si

    // Saklı yordamı çağır
    $stmt = mysqli_prepare($conn, "CALL sp_hobi_grubu_ekle(?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssi", $grup_adi, $aciklama, $kurulus_tarihi, $grup_yoneticisi_uye_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Hobi grubu başarıyla eklendi.";
    } else {
        $error = "Hata: Hobi grubu eklenirken bir sorun oluştu. " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Tüm Üyeleri Çekme (Hobi Grubu Yöneticisi Seçimi İçin)
$uyeler_for_select = [];
$stmt_uyeler = mysqli_prepare($conn, "CALL sp_uyeleri_listele()");
mysqli_stmt_execute($stmt_uyeler);
$result_uyeler = mysqli_stmt_get_result($stmt_uyeler);
if (mysqli_num_rows($result_uyeler) > 0) {
    while ($row_uye = mysqli_fetch_assoc($result_uyeler)) {
        $uyeler_for_select[] = $row_uye;
    }
}
mysqli_stmt_close($stmt_uyeler);


// Hobi Gruplarını Listeleme İşlemi (Saklı yordam ile)
$gruplar = [];
$stmt_gruplar = mysqli_prepare($conn, "CALL sp_hobi_gruplarini_listele()");
mysqli_stmt_execute($stmt_gruplar);
$result_gruplar = mysqli_stmt_get_result($stmt_gruplar);

if (mysqli_num_rows($result_gruplar) > 0) {
    while ($row = mysqli_fetch_assoc($result_gruplar)) {
        $gruplar[] = $row;
    }
} else {
    $error .= " Henüz hobi grubu bulunamadı.";
}
mysqli_stmt_close($stmt_gruplar);
?>

<h2>Hobi Grupları</h2>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<h3>Yeni Hobi Grubu Ekle</h3>
<form action="gruplar.php" method="POST">
    <div class="form-group">
        <label for="grup_adi">Grup Adı:</label>
        <input type="text" id="grup_adi" name="grup_adi" required>
    </div>
    <div class="form-group">
        <label for="aciklama">Açıklama:</label>
        <textarea id="aciklama" name="aciklama" rows="4"></textarea>
    </div>
    <div class="form-group">
        <label for="kurulus_tarihi">Kuruluş Tarihi:</label>
        <input type="date" id="kurulus_tarihi" name="kurulus_tarihi" required>
    </div>
    <div class="form-group">
        <label for="grup_yoneticisi_uye_id">Grup Yöneticisi:</label>
        <select id="grup_yoneticisi_uye_id" name="grup_yoneticisi_uye_id" required>
            <option value="">Yönetici Seçin</option>
            <?php foreach ($uyeler_for_select as $uye): ?>
                <option value="<?php echo htmlspecialchars($uye['uye_id']); ?>">
                    <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad'] . ' (' . $uye['kullanici_adi'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" name="grup_ekle" class="btn">Grup Ekle</button>
</form>

<h3>Mevcut Hobi Grupları</h3>
<?php if (!empty($gruplar)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Grup Adı</th>
                <th>Açıklama</th>
                <th>Kuruluş Tarihi</th>
                <th>Grup Yöneticisi</th>
                <th>Üye Sayısı</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gruplar as $grup): ?>
                <tr>
                    <td><?php echo htmlspecialchars($grup['grup_id']); ?></td>
                    <td><?php echo htmlspecialchars($grup['grup_adi']); ?></td>
                    <td><?php echo htmlspecialchars($grup['aciklama']); ?></td>
                    <td><?php echo htmlspecialchars($grup['kurulus_tarihi']); ?></td>
                    <td><?php echo htmlspecialchars($grup['yonetici_ad'] . ' ' . $grup['yonetici_soyad']); ?></td>
                    <td>
                        <?php
                        // Fonksiyonumuzu burada çağıracağız: fn_gruptaki_uye_sayisi
                        $stmt_uye_sayisi = mysqli_prepare($conn, "SELECT fn_gruptaki_uye_sayisi(?) AS uye_sayisi");
                        mysqli_stmt_bind_param($stmt_uye_sayisi, "i", $grup['grup_id']);
                        mysqli_stmt_execute($stmt_uye_sayisi);
                        $result_uye_sayisi = mysqli_stmt_get_result($stmt_uye_sayisi);
                        $uye_sayisi_row = mysqli_fetch_assoc($result_uye_sayisi);
                        echo htmlspecialchars($uye_sayisi_row['uye_sayisi']);
                        mysqli_stmt_close($stmt_uye_sayisi);
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Henüz hobi grubu bulunmamaktadır.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php'; // HTML altbilgisi
?>
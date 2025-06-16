<?php
include_once 'config.php'; // Veritabanı bağlantısı
include_once 'includes/header.php'; // HTML başlığı ve navigasyon

$message = '';
$error = '';

// Yeni Bilgi Kaynağı Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['kaynak_ekle'])) {
    $kaynak_adi = $_POST['kaynak_adi'];
    $aciklama = $_POST['aciklama'];
    $dosya_url = $_POST['dosya_url'];
    $tip = $_POST['tip'];
    $yukleyen_uye_id = $_POST['yukleyen_uye_id']; // Kaynağı yükleyen üyenin ID'si

    // Saklı yordamı çağır
    $stmt = mysqli_prepare($conn, "CALL sp_bilgi_kaynagi_ekle(?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "ssssi", $kaynak_adi, $aciklama, $dosya_url, $tip, $yukleyen_uye_id);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Bilgi kaynağı başarıyla eklendi.";
    } else {
        $error = "Hata: Bilgi kaynağı eklenirken bir sorun oluştu. " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Tüm Üyeleri Çekme (Kaynağı Yükleyen Üye Seçimi İçin)
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

// Bilgi Kaynaklarını Listeleme İşlemi
$kaynaklar = [];
// Bilgi kaynaklarını listelemek için henüz prosedür oluşturmadık, basit SELECT ile listeliyoruz.
$sql_kaynaklar = "SELECT BK.*, U.ad AS yukleyen_ad, U.soyad AS yukleyen_soyad FROM BilgiKaynaklari BK JOIN Uyeler U ON BK.yukleyen_uye_id = U.uye_id ORDER BY BK.yuklenme_tarihi DESC";
$result_kaynaklar = mysqli_query($conn, $sql_kaynaklar);

if (mysqli_num_rows($result_kaynaklar) > 0) {
    while ($row = mysqli_fetch_assoc($result_kaynaklar)) {
        $kaynaklar[] = $row;
    }
} else {
    $error .= " Henüz bilgi kaynağı bulunmamaktadır.";
}
?>

<h2>Bilgi Kaynakları</h2>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<h3>Yeni Bilgi Kaynağı Ekle</h3>
<form action="kaynaklar.php" method="POST">
    <div class="form-group">
        <label for="kaynak_adi">Kaynak Adı/Başlığı:</label>
        <input type="text" id="kaynak_adi" name="kaynak_adi" required>
    </div>
    <div class="form-group">
        <label for="aciklama">Açıklama:</label>
        <textarea id="aciklama" name="aciklama" rows="4"></textarea>
    </div>
    <div class="form-group">
        <label for="dosya_url">Dosya URL (Link):</label>
        <input type="text" id="dosya_url" name="dosya_url" required placeholder="örn: https://example.com/rehber.pdf">
    </div>
    <div class="form-group">
        <label for="tip">Tip:</label>
        <select id="tip" name="tip" required>
            <option value="">Seçiniz</option>
            <option value="Makale">Makale</option>
            <option value="Video">Video</option>
            <option value="Rehber">Rehber</option>
            <option value="Link">Link</option>
            <option value="Belge">Belge</option>
        </select>
    </div>
    <div class="form-group">
        <label for="yukleyen_uye_id">Yükleyen Üye:</label>
        <select id="yukleyen_uye_id" name="yukleyen_uye_id" required>
            <option value="">Üye Seçin</option>
            <?php foreach ($uyeler_for_select as $uye): ?>
                <option value="<?php echo htmlspecialchars($uye['uye_id']); ?>">
                    <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad'] . ' (' . $uye['kullanici_adi'] . ')'); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <button type="submit" name="kaynak_ekle" class="btn">Kaynağı Ekle</button>
</form>

<h3>Mevcut Bilgi Kaynakları</h3>
<?php if (!empty($kaynaklar)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Kaynak Adı</th>
                <th>Açıklama</th>
                <th>URL</th>
                <th>Tip</th>
                <th>Yükleyen</th>
                <th>Yüklenme Tarihi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kaynaklar as $kaynak): ?>
                <tr>
                    <td><?php echo htmlspecialchars($kaynak['kaynak_id']); ?></td>
                    <td><?php echo htmlspecialchars($kaynak['kaynak_adi']); ?></td>
                    <td><?php echo htmlspecialchars($kaynak['aciklama']); ?></td>
                    <td><a href="<?php echo htmlspecialchars($kaynak['dosya_url']); ?>" target="_blank">Link</a></td>
                    <td><?php echo htmlspecialchars($kaynak['tip']); ?></td>
                    <td><?php echo htmlspecialchars($kaynak['yukleyen_ad'] . ' ' . $kaynak['yukleyen_soyad']); ?></td>
                    <td><?php echo htmlspecialchars($kaynak['yuklenme_tarihi']); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Henüz bilgi kaynağı bulunmamaktadır.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php'; // HTML altbilgisi
?>
<?php
include_once 'config.php'; // Veritabanı bağlantısı
include_once 'includes/header.php'; // HTML başlığı ve navigasyon

$message = '';
$error = '';

// Yeni Ekipman Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['ekipman_ekle'])) {
    $ekipman_adi = $_POST['ekipman_adi'];
    $aciklama = $_POST['aciklama'];
    $seri_no = $_POST['seri_no'];
    $edinme_tarihi = $_POST['edinme_tarihi'];
    $durum = $_POST['durum'];

    // Saklı yordamı çağır
    $stmt = mysqli_prepare($conn, "CALL sp_ekipman_ekle(?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "sssss", $ekipman_adi, $aciklama, $seri_no, $edinme_tarihi, $durum);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Ekipman başarıyla eklendi.";
    } else {
        $error = "Hata: Ekipman eklenirken bir sorun oluştu. " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Ekipmanları Listeleme İşlemi
$ekipmanlar = [];
// Ekipmanları listelemek için henüz prosedür oluşturmadık, basit SELECT ile listeliyoruz.
$sql_ekipmanlar = "SELECT * FROM Ekipmanlar ORDER BY ekipman_id DESC";
$result_ekipmanlar = mysqli_query($conn, $sql_ekipmanlar);

if (mysqli_num_rows($result_ekipmanlar) > 0) {
    while ($row = mysqli_fetch_assoc($result_ekipmanlar)) {
        $ekipmanlar[] = $row;
    }
} else {
    $error .= " Henüz ekipman bulunmamaktadır.";
}
?>

<h2>Ekipmanlar</h2>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<h3>Yeni Ekipman Ekle</h3>
<form action="ekipmanlar.php" method="POST">
    <div class="form-group">
        <label for="ekipman_adi">Ekipman Adı:</label>
        <input type="text" id="ekipman_adi" name="ekipman_adi" required>
    </div>
    <div class="form-group">
        <label for="aciklama">Açıklama:</label>
        <textarea id="aciklama" name="aciklama" rows="4"></textarea>
    </div>
    <div class="form-group">
        <label for="seri_no">Seri No:</label>
        <input type="text" id="seri_no" name="seri_no">
    </div>
    <div class="form-group">
        <label for="edinme_tarihi">Edinme Tarihi:</label>
        <input type="date" id="edinme_tarihi" name="edinme_tarihi">
    </div>
    <div class="form-group">
        <label for="durum">Durum:</label>
        <select id="durum" name="durum" required>
            <option value="Kullanılabilir">Kullanılabilir</option>
            <option value="Bakımda">Bakımda</option>
            <option value="Rezervde">Rezervde</option>
            <option value="Arızalı">Arızalı</option>
        </select>
    </div>
    <button type="submit" name="ekipman_ekle" class="btn">Ekipman Ekle</button>
</form>

<h3>Mevcut Ekipmanlar</h3>
<?php if (!empty($ekipmanlar)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Ekipman Adı</th>
                <th>Açıklama</th>
                <th>Seri No</th>
                <th>Edinme Tarihi</th>
                <th>Durum</th>
                <th>Eylemler</th> </tr>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ekipmanlar as $ekipman): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ekipman['ekipman_id']); ?></td>
                    <td><?php echo htmlspecialchars($ekipman['ekipman_adi']); ?></td>
                    <td><?php echo htmlspecialchars($ekipman['aciklama']); ?></td>
                    <td><?php echo htmlspecialchars($ekipman['seri_no']); ?></td>
                    <td><?php echo htmlspecialchars($ekipman['edinme_tarihi']); ?></td>
                    <td><?php echo htmlspecialchars($ekipman['durum']); ?></td>
                    <td>
                        <a href="ekipman_rezervasyon_yonet.php?ekipman_id=<?php echo htmlspecialchars($ekipman['ekipman_id']); ?>" class="btn">Rezervasyonları Yönet</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Henüz ekipman bulunmamaktadır.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php'; // HTML altbilgisi
?>
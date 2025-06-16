<?php
include_once 'config.php'; // Veritabanı bağlantısı
include_once 'includes/header.php'; // HTML başlığı ve navigasyon

$message = '';
$error = '';

// Yeni Etkinlik Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['etkinlik_ekle'])) {
    $grup_id = $_POST['grup_id'];
    $etkinlik_adi = $_POST['etkinlik_adi'];
    $aciklama = $_POST['aciklama'];
    $tarih = $_POST['tarih'];
    $saat = $_POST['saat'];
    $lokasyon_tipi = $_POST['lokasyon_tipi'];
    $lokasyon_detayi = $_POST['lokasyon_detayi'];
    $kapasite = $_POST['kapasite'];

    // Saklı yordamı çağır
    $stmt = mysqli_prepare($conn, "CALL sp_etkinlik_ekle(?, ?, ?, ?, ?, ?, ?, ?)");
    mysqli_stmt_bind_param($stmt, "issssssi", $grup_id, $etkinlik_adi, $aciklama, $tarih, $saat, $lokasyon_tipi, $lokasyon_detayi, $kapasite);

    if (mysqli_stmt_execute($stmt)) {
        $message = "Etkinlik başarıyla eklendi.";
    } else {
        // Hata mesajını daha detaylı almak için:
        $error = "Hata: Etkinlik eklenirken bir sorun oluştu. " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Tüm Hobi Gruplarını Çekme (Etkinlik Grubu Seçimi İçin)
$gruplar_for_select = [];
$stmt_gruplar = mysqli_prepare($conn, "CALL sp_hobi_gruplarini_listele()"); // sp_hobi_gruplarini_listele zaten mevcut
mysqli_stmt_execute($stmt_gruplar);
$result_gruplar = mysqli_stmt_get_result($stmt_gruplar);
if (mysqli_num_rows($result_gruplar) > 0) {
    while ($row_grup = mysqli_fetch_assoc($result_gruplar)) {
        $gruplar_for_select[] = $row_grup;
    }
}
mysqli_stmt_close($stmt_gruplar);

// Etkinlikleri Listeleme İşlemi
$etkinlikler = [];
// Etkinlikleri listelemek için yeni bir prosedür yazmamız gerekebilir,
// veya basit bir SELECT sorgusu kullanabiliriz. Prosedür kuralına uymak adına yeni prosedür yazalım.
// Geçici olarak direkt SELECT kullanalım, sonra prosedürünü ekleriz.

$sql_etkinlikler = "SELECT E.*, HG.grup_adi FROM Etkinlikler E JOIN HobiGruplari HG ON E.grup_id = HG.grup_id ORDER BY E.tarih DESC, E.saat DESC";
$result_etkinlikler = mysqli_query($conn, $sql_etkinlikler);

if (mysqli_num_rows($result_etkinlikler) > 0) {
    while ($row = mysqli_fetch_assoc($result_etkinlikler)) {
        $etkinlikler[] = $row;
    }
} else {
    $error .= " Henüz etkinlik bulunamadı.";
}
//mysqli_close($conn); // Henüz kapatmıyoruz, footer'da kapanacak
?>

<h2>Etkinlikler</h2>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<h3>Yeni Etkinlik Ekle</h3>
<form action="etkinlikler.php" method="POST">
    <div class="form-group">
        <label for="grup_id">Hobi Grubu:</label>
        <select id="grup_id" name="grup_id" required>
            <option value="">Grup Seçin</option>
            <?php foreach ($gruplar_for_select as $grup): ?>
                <option value="<?php echo htmlspecialchars($grup['grup_id']); ?>">
                    <?php echo htmlspecialchars($grup['grup_adi']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="etkinlik_adi">Etkinlik Adı:</label>
        <input type="text" id="etkinlik_adi" name="etkinlik_adi" required>
    </div>
    <div class="form-group">
        <label for="aciklama">Açıklama:</label>
        <textarea id="aciklama" name="aciklama" rows="4"></textarea>
    </div>
    <div class="form-group">
        <label for="tarih">Tarih:</label>
        <input type="date" id="tarih" name="tarih" required>
    </div>
    <div class="form-group">
        <label for="saat">Saat:</label>
        <input type="time" id="saat" name="saat" required>
    </div>
    <div class="form-group">
        <label for="lokasyon_tipi">Lokasyon Tipi:</label>
        <select id="lokasyon_tipi" name="lokasyon_tipi" required>
            <option value="">Seçiniz</option>
            <option value="Online">Online</option>
            <option value="Fiziksel">Fiziksel</option>
        </select>
    </div>
    <div class="form-group">
        <label for="lokasyon_detayi">Lokasyon Detayı (Adres / Link):</label>
        <input type="text" id="lokasyon_detayi" name="lokasyon_detayi">
    </div>
    <div class="form-group">
        <label for="kapasite">Kapasite:</label>
        <input type="number" id="kapasite" name="kapasite" min="1" required>
    </div>
    <button type="submit" name="etkinlik_ekle" class="btn">Etkinlik Ekle</button>
</form>

<h3>Mevcut Etkinlikler</h3>
<?php if (!empty($etkinlikler)): ?>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Etkinlik Adı</th>
                <th>Grup Adı</th>
                <th>Tarih</th>
                <th>Saat</th>
                <th>Lokasyon</th>
                <th>Kapasite</th>
                <th>Katılımcı Sayısı</th>
                <th>Oluşturulma Tarihi</th>
                <th>Eylemler</th> </tr>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($etkinlikler as $etkinlik): ?>
                <tr>
                    <td><?php echo htmlspecialchars($etkinlik['etkinlik_id']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['etkinlik_adi']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['grup_adi']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['tarih']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['saat']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['lokasyon_tipi'] . ': ' . $etkinlik['lokasyon_detayi']); ?></td>
                    <td><?php echo htmlspecialchars($etkinlik['kapasite']); ?></td>
                    <td>
                        <?php
                        // Bu etkinliğe katılan üye sayısını almak için:
                        $stmt_katilimci_sayisi = mysqli_prepare($conn, "SELECT COUNT(*) AS katilimci_sayisi FROM EtkinlikKatilim WHERE etkinlik_id = ?");
                        mysqli_stmt_bind_param($stmt_katilimci_sayisi, "i", $etkinlik['etkinlik_id']);
                        mysqli_stmt_execute($stmt_katilimci_sayisi);
                        $result_katilimci_sayisi = mysqli_stmt_get_result($stmt_katilimci_sayisi);
                        $katilimci_sayisi_row = mysqli_fetch_assoc($result_katilimci_sayisi);
                        echo htmlspecialchars($katilimci_sayisi_row['katilimci_sayisi']);
                        mysqli_stmt_close($stmt_katilimci_sayisi);
                        ?>
                    </td>
                    <td><?php echo htmlspecialchars($etkinlik['olusturma_tarihi']); ?></td>
                    <td>
                        <a href="etkinlik_katilim_yonet.php?etkinlik_id=<?php echo htmlspecialchars($etkinlik['etkinlik_id']); ?>" class="btn">Katılımcıları Yönet</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>Henüz etkinlik bulunmamaktadır.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php'; // HTML altbilgisi
?>
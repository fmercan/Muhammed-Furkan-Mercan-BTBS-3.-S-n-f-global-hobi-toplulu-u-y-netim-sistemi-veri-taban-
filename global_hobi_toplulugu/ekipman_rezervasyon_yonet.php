<?php
include_once 'config.php';
include_once 'includes/header.php';

$message = '';
$error = '';
$ekipman_id = isset($_GET['ekipman_id']) ? intval($_GET['ekipman_id']) : 0; // URL'den ekipman ID'sini al

if ($ekipman_id == 0) {
    $error = "Ekipman ID belirtilmedi.";
}

// Ekipman bilgilerini çek
$ekipman_bilgisi = null;
if ($ekipman_id > 0) {
    $sql_ekipman_bilgisi = "SELECT ekipman_adi, durum FROM Ekipmanlar WHERE ekipman_id = ?";
    $stmt_ekipman = mysqli_prepare($conn, $sql_ekipman_bilgisi);
    mysqli_stmt_bind_param($stmt_ekipman, "i", $ekipman_id);
    mysqli_stmt_execute($stmt_ekipman);
    $result_ekipman = mysqli_stmt_get_result($stmt_ekipman);
    if (mysqli_num_rows($result_ekipman) > 0) {
        $ekipman_bilgisi = mysqli_fetch_assoc($result_ekipman);
    } else {
        $error = "Belirtilen Ekipman bulunamadı.";
    }
    mysqli_stmt_close($stmt_ekipman);
}

// Ekipman Rezervasyonu Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['rezervasyon_ekle'])) {
    $uye_id = $_POST['uye_id'];
    $baslangic_tarihi = $_POST['baslangic_tarihi'] . ' ' . $_POST['baslangic_saati'] . ':00'; // datetime formatı için
    $bitis_tarihi = $_POST['bitis_tarihi'] . ' ' . $_POST['bitis_saati'] . ':00'; // datetime formatı için

    if ($ekipman_id > 0 && $uye_id > 0 && !empty($baslangic_tarihi) && !empty($bitis_tarihi)) {
        // Saklı yordamı çağır
        $stmt = mysqli_prepare($conn, "CALL sp_ekipman_rezervasyon_ekle(?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, "iiss", $uye_id, $ekipman_id, $baslangic_tarihi, $bitis_tarihi);

        try {
            if (mysqli_stmt_execute($stmt)) {
                $message = "Ekipman rezervasyon talebiniz alındı.";
            } else {
                $error = "Hata: Rezervasyon yapılırken bir sorun oluştu. " . mysqli_error($conn);
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Hata: " . $e->getMessage();
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Geçersiz üye, ekipman veya tarih/saat seçimi.";
    }
}

// Tüm Üyeleri Çekme (Rezervasyon Yapan Üye Seçimi İçin)
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

// Mevcut Rezervasyonları Çek
$rezervasyonlar = [];
if ($ekipman_id > 0) {
    $sql_rezervasyonlar = "SELECT ER.*, U.ad, U.soyad, U.kullanici_adi
                           FROM EkipmanRezervasyon ER
                           JOIN Uyeler U ON ER.uye_id = U.uye_id
                           WHERE ER.ekipman_id = ?
                           ORDER BY ER.baslangic_tarihi DESC";
    $stmt_rezervasyonlar = mysqli_prepare($conn, $sql_rezervasyonlar);
    mysqli_stmt_bind_param($stmt_rezervasyonlar, "i", $ekipman_id);
    mysqli_stmt_execute($stmt_rezervasyonlar);
    $result_rezervasyonlar = mysqli_stmt_get_result($stmt_rezervasyonlar);
    if (mysqli_num_rows($result_rezervasyonlar) > 0) {
        while ($row = mysqli_fetch_assoc($result_rezervasyonlar)) {
            $rezervasyonlar[] = $row;
        }
    }
    mysqli_stmt_close($stmt_rezervasyonlar);
}
?>

<h2><?php echo htmlspecialchars($ekipman_bilgisi['ekipman_adi']); ?> Rezervasyonlarını Yönet</h2>
<?php if ($ekipman_bilgisi): ?>
    <p>Mevcut Durum: <?php echo htmlspecialchars($ekipman_bilgisi['durum']); ?></p>
<?php endif; ?>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<?php if ($ekipman_id > 0 && $ekipman_bilgisi): ?>

    <h3>Rezervasyon Yap</h3>
    <form action="ekipman_rezervasyon_yonet.php?ekipman_id=<?php echo $ekipman_id; ?>" method="POST">
        <div class="form-group">
            <label for="uye_id">Rezervasyon Yapan Üye:</label>
            <select id="uye_id" name="uye_id" required>
                <option value="">Üye Seçin</option>
                <?php foreach ($uyeler_for_select as $uye): ?>
                    <option value="<?php echo htmlspecialchars($uye['uye_id']); ?>">
                        <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad'] . ' (' . $uye['kullanici_adi'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="baslangic_tarihi">Başlangıç Tarihi:</label>
            <input type="date" id="baslangic_tarihi" name="baslangic_tarihi" required>
        </div>
        <div class="form-group">
            <label for="baslangic_saati">Başlangıç Saati:</label>
            <input type="time" id="baslangic_saati" name="baslangic_saati" required>
        </div>
        <div class="form-group">
            <label for="bitis_tarihi">Bitiş Tarihi:</label>
            <input type="date" id="bitis_tarihi" name="bitis_tarihi" required>
        </div>
        <div class="form-group">
            <label for="bitis_saati">Bitiş Saati:</label>
            <input type="time" id="bitis_saati" name="bitis_saati" required>
        </div>
        <button type="submit" name="rezervasyon_ekle" class="btn">Rezervasyon Yap</button>
    </form>

    <h3>Mevcut Rezervasyonlar</h3>
    <?php if (!empty($rezervasyonlar)): ?>
        <table>
            <thead>
                <tr>
                    <th>Rezervasyon ID</th>
                    <th>Yapan Üye</th>
                    <th>Başlangıç</th>
                    <th>Bitiş</th>
                    <th>Durum</th>
                    <th>Talep Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rezervasyonlar as $rezervasyon): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($rezervasyon['rezervasyon_id']); ?></td>
                        <td><?php echo htmlspecialchars($rezervasyon['ad'] . ' ' . $rezervasyon['soyad']); ?></td>
                        <td><?php echo htmlspecialchars($rezervasyon['baslangic_tarihi']); ?></td>
                        <td><?php echo htmlspecialchars($rezervasyon['bitis_tarihi']); ?></td>
                        <td><?php echo htmlspecialchars($rezervasyon['durum']); ?></td>
                        <td><?php echo htmlspecialchars($rezervasyon['rezervasyon_tarihi']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Bu ekipman için henüz rezervasyon bulunmamaktadır.</p>
    <?php endif; ?>

<?php else: ?>
    <p>Ekipman bilgileri alınamadı. Lütfen <a href="ekipmanlar.php">Ekipmanlar</a> sayfasından geçerli bir ekipman seçin.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php';
?>
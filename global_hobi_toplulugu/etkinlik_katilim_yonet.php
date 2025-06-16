<?php
include_once 'config.php';
include_once 'includes/header.php';

$message = '';
$error = '';
$etkinlik_id = isset($_GET['etkinlik_id']) ? intval($_GET['etkinlik_id']) : 0; // URL'den etkinlik ID'sini al

if ($etkinlik_id == 0) {
    $error = "Etkinlik ID belirtilmedi.";
}

// Etkinlik bilgilerini çek
$etkinlik_bilgisi = null;
if ($etkinlik_id > 0) {
    $sql_etkinlik_bilgisi = "SELECT E.etkinlik_adi, E.kapasite, HG.grup_adi
                            FROM Etkinlikler E
                            JOIN HobiGruplari HG ON E.grup_id = HG.grup_id
                            WHERE E.etkinlik_id = ?";
    $stmt_etkinlik = mysqli_prepare($conn, $sql_etkinlik_bilgisi);
    mysqli_stmt_bind_param($stmt_etkinlik, "i", $etkinlik_id);
    mysqli_stmt_execute($stmt_etkinlik);
    $result_etkinlik = mysqli_stmt_get_result($stmt_etkinlik);
    if (mysqli_num_rows($result_etkinlik) > 0) {
        $etkinlik_bilgisi = mysqli_fetch_assoc($result_etkinlik);
    } else {
        $error = "Belirtilen Etkinlik bulunamadı.";
    }
    mysqli_stmt_close($stmt_etkinlik);
}

// Etkinliğe Katılımcı Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['katilimci_ekle'])) {
    $uye_id = $_POST['uye_id'];

    if ($etkinlik_id > 0 && $uye_id > 0) {
        // Saklı yordamı çağır
        $stmt = mysqli_prepare($conn, "CALL sp_etkinlige_katilimci_ekle(?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $uye_id, $etkinlik_id);

        // Hata durumunda try-catch benzeri bir yaklaşım (PHP'de exceptions ile)
        // mysqli_sql_exception için hata yakalama
        try {
            if (mysqli_stmt_execute($stmt)) {
                $message = "Üye başarıyla etkinliğe kaydedildi.";
            } else {
                // Eğer saklı yordam SIGNAL ile hata döndürürse burada yakalanır.
                $error = "Hata: Etkinliğe katılım sırasında bir sorun oluştu. " . mysqli_error($conn);
            }
        } catch (mysqli_sql_exception $e) {
            $error = "Hata: " . $e->getMessage();
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Geçersiz üye veya etkinlik seçimi.";
    }
}

// Etkinliğe Katılan Üyeleri Çek
$katilimcilar = [];
$current_katilimci_sayisi = 0; // Mevcut katılımcı sayısını tutmak için
if ($etkinlik_id > 0) {
    $sql_katilimcilar = "SELECT U.uye_id, U.ad, U.soyad, U.kullanici_adi, EK.katilim_tarihi
                         FROM EtkinlikKatilim EK
                         JOIN Uyeler U ON EK.uye_id = U.uye_id
                         WHERE EK.etkinlik_id = ?";
    $stmt_katilimcilar = mysqli_prepare($conn, $sql_katilimcilar);
    mysqli_stmt_bind_param($stmt_katilimcilar, "i", $etkinlik_id);
    mysqli_stmt_execute($stmt_katilimcilar);
    $result_katilimcilar = mysqli_stmt_get_result($stmt_katilimcilar);
    if (mysqli_num_rows($result_katilimcilar) > 0) {
        while ($row = mysqli_fetch_assoc($result_katilimcilar)) {
            $katilimcilar[] = $row;
        }
    }
    $current_katilimci_sayisi = count($katilimcilar); // Katılımcı sayısını güncelle
    mysqli_stmt_close($stmt_katilimcilar);
}


// Etkinliğe Katılmayan Üyeleri Çek (Seçenek olarak sunmak için)
$katilmayan_uyeler = [];
if ($etkinlik_id > 0) {
    $sql_tum_uyeler = "SELECT uye_id, ad, soyad, kullanici_adi FROM Uyeler WHERE uye_id NOT IN (SELECT uye_id FROM EtkinlikKatilim WHERE etkinlik_id = ?) ORDER BY ad";
    $stmt_tum_uyeler = mysqli_prepare($conn, $sql_tum_uyeler);
    mysqli_stmt_bind_param($stmt_tum_uyeler, "i", $etkinlik_id);
    mysqli_stmt_execute($stmt_tum_uyeler);
    $result_tum_uyeler = mysqli_stmt_get_result($stmt_tum_uyeler);
    if (mysqli_num_rows($result_tum_uyeler) > 0) {
        while ($row = mysqli_fetch_assoc($result_tum_uyeler)) {
            $katilmayan_uyeler[] = $row;
        }
    }
    mysqli_stmt_close($stmt_tum_uyeler);
}
?>

<h2><?php echo htmlspecialchars($etkinlik_bilgisi['etkinlik_adi']); ?> Katılımcılarını Yönet</h2>
<?php if ($etkinlik_bilgisi): ?>
    <p>Grup: <?php echo htmlspecialchars($etkinlik_bilgisi['grup_adi']); ?></p>
    <p>Kapasite: <?php echo htmlspecialchars($current_katilimci_sayisi) . ' / ' . htmlspecialchars($etkinlik_bilgisi['kapasite']); ?></p>
<?php endif; ?>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<?php if ($etkinlik_id > 0 && $etkinlik_bilgisi): ?>

    <h3>Katılımcı Ekle</h3>
    <?php if ($current_katilimci_sayisi < $etkinlik_bilgisi['kapasite']): ?>
        <form action="etkinlik_katilim_yonet.php?etkinlik_id=<?php echo $etkinlik_id; ?>" method="POST">
            <div class="form-group">
                <label for="uye_id">Katılımcı Üye:</label>
                <select id="uye_id" name="uye_id" required>
                    <option value="">Üye Seçin</option>
                    <?php if (!empty($katilmayan_uyeler)): ?>
                        <?php foreach ($katilmayan_uyeler as $uye): ?>
                            <option value="<?php echo htmlspecialchars($uye['uye_id']); ?>">
                                <?php echo htmlspecialchars($uye['ad'] . ' ' . $uye['soyad'] . ' (' . $uye['kullanici_adi'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <option value="" disabled>Tüm uygun üyeler zaten katıldı.</option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="submit" name="katilimci_ekle" class="btn">Katılımcı Ekle</button>
        </form>
    <?php else: ?>
        <p class="message error">Etkinlik kapasitesi dolu, yeni katılımcı eklenemez.</p>
    <?php endif; ?>


    <h3>Mevcut Katılımcılar</h3>
    <?php if (!empty($katilimcilar)): ?>
        <table>
            <thead>
                <tr>
                    <th>Üye ID</th>
                    <th>Ad Soyad</th>
                    <th>Kullanıcı Adı</th>
                    <th>Katılım Tarihi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($katilimcilar as $katilimci): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($katilimci['uye_id']); ?></td>
                        <td><?php echo htmlspecialchars($katilimci['ad'] . ' ' . $katilimci['soyad']); ?></td>
                        <td><?php echo htmlspecialchars($katilimci['kullanici_adi']); ?></td>
                        <td><?php echo htmlspecialchars($katilimci['katilim_tarihi']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Bu etkinliğe henüz katılımcı yok.</p>
    <?php endif; ?>

<?php else: ?>
    <p>Etkinlik bilgileri alınamadı. Lütfen <a href="etkinlikler.php">Etkinlikler</a> sayfasından geçerli bir etkinlik seçin.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php';
?>
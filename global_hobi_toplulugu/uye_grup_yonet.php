<?php
include_once 'config.php';
include_once 'includes/header.php';

$message = '';
$error = '';
$uye_id = isset($_GET['uye_id']) ? intval($_GET['uye_id']) : 0; // URL'den üye ID'sini al

if ($uye_id == 0) {
    $error = "Üye ID belirtilmedi.";
}

// Üye bilgilerini çek
$uye_bilgisi = null;
if ($uye_id > 0) {
    $stmt_uye = mysqli_prepare($conn, "SELECT ad, soyad FROM Uyeler WHERE uye_id = ?");
    mysqli_stmt_bind_param($stmt_uye, "i", $uye_id);
    mysqli_stmt_execute($stmt_uye);
    $result_uye = mysqli_stmt_get_result($stmt_uye);
    if (mysqli_num_rows($result_uye) > 0) {
        $uye_bilgisi = mysqli_fetch_assoc($result_uye);
    } else {
        $error = "Belirtilen Üye bulunamadı.";
    }
    mysqli_stmt_close($stmt_uye);
}

// Üyeyi Gruba Ekleme İşlemi
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['gruba_ekle'])) {
    $grup_id = $_POST['grup_id'];

    if ($uye_id > 0 && $grup_id > 0) {
        $stmt = mysqli_prepare($conn, "CALL sp_uye_gruba_ekle(?, ?)");
        mysqli_stmt_bind_param($stmt, "ii", $uye_id, $grup_id);
        if (mysqli_stmt_execute($stmt)) {
            $message = "Üye başarıyla gruba eklendi.";
        } else {
            $error = "Hata: Üye gruba eklenirken bir sorun oluştu veya zaten bu grupta. " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $error = "Geçersiz üye veya grup seçimi.";
    }
}

// Üyenin Zaten Üye Olduğu Grupları Çek
$uye_gruplari = [];
if ($uye_id > 0) {
    $sql_uye_gruplari = "SELECT HG.grup_id, HG.grup_adi, UG.katilma_tarihi
                         FROM UyeGrup UG
                         JOIN HobiGruplari HG ON UG.grup_id = HG.grup_id
                         WHERE UG.uye_id = ?";
    $stmt_uye_gruplari = mysqli_prepare($conn, $sql_uye_gruplari);
    mysqli_stmt_bind_param($stmt_uye_gruplari, "i", $uye_id);
    mysqli_stmt_execute($stmt_uye_gruplari);
    $result_uye_gruplari = mysqli_stmt_get_result($stmt_uye_gruplari);
    if (mysqli_num_rows($result_uye_gruplari) > 0) {
        while ($row = mysqli_fetch_assoc($result_uye_gruplari)) {
            $uye_gruplari[] = $row;
        }
    }
    mysqli_stmt_close($stmt_uye_gruplari);
}

// Üyenin Katılmadığı Grupları Çek (Seçenek olarak sunmak için)
$katilmadigi_gruplar = [];
if ($uye_id > 0) {
    $sql_tum_gruplar = "SELECT grup_id, grup_adi FROM HobiGruplari WHERE grup_id NOT IN (SELECT grup_id FROM UyeGrup WHERE uye_id = ?) ORDER BY grup_adi";
    $stmt_tum_gruplar = mysqli_prepare($conn, $sql_tum_gruplar);
    mysqli_stmt_bind_param($stmt_tum_gruplar, "i", $uye_id);
    mysqli_stmt_execute($stmt_tum_gruplar);
    $result_tum_gruplar = mysqli_stmt_get_result($stmt_tum_gruplar);
    if (mysqli_num_rows($result_tum_gruplar) > 0) {
        while ($row = mysqli_fetch_assoc($result_tum_gruplar)) {
            $katilmadigi_gruplar[] = $row;
        }
    }
    mysqli_stmt_close($stmt_tum_gruplar);
}
?>

<h2><?php echo htmlspecialchars($uye_bilgisi['ad'] . ' ' . $uye_bilgisi['soyad']); ?> - Grup Üyeliklerini Yönet</h2>

<?php
if ($message) {
    echo '<div class="message success">' . $message . '</div>';
}
if ($error) {
    echo '<div class="message error">' . $error . '</div>';
}
?>

<?php if ($uye_id > 0 && $uye_bilgisi): ?>

    <h3>Gruba Ekle</h3>
    <form action="uye_grup_yonet.php?uye_id=<?php echo $uye_id; ?>" method="POST">
        <div class="form-group">
            <label for="grup_id">Katılınacak Hobi Grubu:</label>
            <select id="grup_id" name="grup_id" required>
                <option value="">Grup Seçin</option>
                <?php if (!empty($katilmadigi_gruplar)): ?>
                    <?php foreach ($katilmadigi_gruplar as $grup): ?>
                        <option value="<?php echo htmlspecialchars($grup['grup_id']); ?>">
                            <?php echo htmlspecialchars($grup['grup_adi']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="" disabled>Tüm gruplara üye.</option>
                <?php endif; ?>
            </select>
        </div>
        <button type="submit" name="gruba_ekle" class="btn">Gruba Ekle</button>
    </form>

    <h3>Üye Olduğu Gruplar</h3>
    <?php if (!empty($uye_gruplari)): ?>
        <table>
            <thead>
                <tr>
                    <th>Grup ID</th>
                    <th>Grup Adı</th>
                    <th>Katılım Tarihi</th>
                    </tr>
            </thead>
            <tbody>
                <?php foreach ($uye_gruplari as $grup): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($grup['grup_id']); ?></td>
                        <td><?php echo htmlspecialchars($grup['grup_adi']); ?></td>
                        <td><?php echo htmlspecialchars($grup['katilma_tarihi']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Bu üye henüz hiçbir gruba üye değil.</p>
    <?php endif; ?>

<?php else: ?>
    <p>Üye bilgileri alınamadı. Lütfen <a href="uyeler.php">Üyeler</a> sayfasından geçerli bir üye seçin.</p>
<?php endif; ?>

<?php
include_once 'includes/footer.php';
?>
<?php
include_once 'config.php'; // Veritabanı bağlantısı
include_once 'includes/header.php'; // HTML başlığı ve navigasyon

// Toplam Üye Sayısı
$toplam_uye_sayisi = 0;
$result_uye_sayisi = mysqli_query($conn, "SELECT COUNT(uye_id) AS toplam FROM Uyeler");
if ($result_uye_sayisi) {
    $row = mysqli_fetch_assoc($result_uye_sayisi);
    $toplam_uye_sayisi = $row['toplam'];
}

// Aktif Hobi Grubu Sayısı (Basitçe toplam grup sayısı)
$aktif_hobi_grubu_sayisi = 0;
$result_grup_sayisi = mysqli_query($conn, "SELECT COUNT(grup_id) AS toplam FROM HobiGruplari");
if ($result_grup_sayisi) {
    $row = mysqli_fetch_assoc($result_grup_sayisi);
    $aktif_hobi_grubu_sayisi = $row['toplam'];
}

// Yaklaşan Etkinlik Sayısı (Bugünden sonraki etkinlikler)
$yaklasan_etkinlik_sayisi = 0;
$current_date = date('Y-m-d'); // Mevcut tarih
$result_etkinlik_sayisi = mysqli_query($conn, "SELECT COUNT(etkinlik_id) AS toplam FROM Etkinlikler WHERE tarih >= '" . $current_date . "'");
if ($result_etkinlik_sayisi) {
    $row = mysqli_fetch_assoc($result_etkinlik_sayisi);
    $yaklasan_etkinlik_sayisi = $row['toplam'];
}

?>

<h2>Ana Sayfa</h2>
<p>Global Hobi Topluluğu Yönetim Sistemine hoş geldiniz. Yukarıdaki menüden ilgili bölümlere ulaşabilirsiniz.</p>

<h3>İstatistikler</h3>
<ul>
    <li>Toplam Üye Sayısı: **<?php echo htmlspecialchars($toplam_uye_sayisi); ?>**</li>
    <li>Aktif Hobi Grubu Sayısı: **<?php echo htmlspecialchars($aktif_hobi_grubu_sayisi); ?>**</li>
    <li>Yaklaşan Etkinlik Sayısı: **<?php echo htmlspecialchars($yaklasan_etkinlik_sayisi); ?>**</li>
</ul>

<?php
include_once 'includes/footer.php'; // HTML altbilgisi
?>
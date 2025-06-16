<?php
// Veritabanı bağlantı bilgileri
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root'); // XAMPP varsayılan kullanıcı adı
define('DB_PASSWORD', '');     // XAMPP varsayılan şifre (boş)
define('DB_NAME', 'global_hobi_toplulugu');

// Veritabanına bağlanma
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Bağlantıyı kontrol etme
if ($conn === false) {
    die("HATA: Veritabanına bağlanılamadı. " . mysqli_connect_error());
}

// Türkçe karakter desteği için
mysqli_set_charset($conn, "utf8");

// DEBUG: Bağlantı başarılı mesajı (isteğe bağlı, sonra kaldırılabilir)
// echo "Veritabanı bağlantısı başarılı!";
?>
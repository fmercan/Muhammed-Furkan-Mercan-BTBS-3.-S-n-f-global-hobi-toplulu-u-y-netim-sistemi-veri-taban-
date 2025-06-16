-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Anamakine: 127.0.0.1
-- Üretim Zamanı: 16 Haz 2025, 21:54:58
-- Sunucu sürümü: 10.4.32-MariaDB
-- PHP Sürümü: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Veritabanı: `global_hobi_toplulugu`
--

DELIMITER $$
--
-- Yordamlar
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_bilgi_kaynagi_ekle` (IN `p_kaynak_adi` VARCHAR(200), IN `p_aciklama` TEXT, IN `p_dosya_url` VARCHAR(255), IN `p_tip` ENUM('Makale','Video','Rehber','Link','Belge'), IN `p_yukleyen_uye_id` INT)   BEGIN
    INSERT INTO BilgiKaynaklari (kaynak_adi, aciklama, dosya_url, tip, yukleyen_uye_id)
    VALUES (p_kaynak_adi, p_aciklama, p_dosya_url, p_tip, p_yukleyen_uye_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ekipman_ekle` (IN `p_ekipman_adi` VARCHAR(100), IN `p_aciklama` TEXT, IN `p_seri_no` VARCHAR(100), IN `p_edinme_tarihi` DATE, IN `p_durum` ENUM('Kullanılabilir','Bakımda','Rezervde','Arızalı'))   BEGIN
    INSERT INTO Ekipmanlar (ekipman_adi, aciklama, seri_no, edinme_tarihi, durum)
    VALUES (p_ekipman_adi, p_aciklama, p_seri_no, p_edinme_tarihi, p_durum);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_ekipman_rezervasyon_ekle` (IN `p_uye_id` INT, IN `p_ekipman_id` INT, IN `p_baslangic_tarihi` DATETIME, IN `p_bitis_tarihi` DATETIME)   BEGIN
    -- Çakışan rezervasyon olup olmadığını kontrol et (ekipman ve tarih aralığına göre)
    IF NOT EXISTS (
        SELECT 1
        FROM EkipmanRezervasyon
        WHERE ekipman_id = p_ekipman_id
          AND (
                (p_baslangic_tarihi < bitis_tarihi AND p_bitis_tarihi > baslangic_tarihi)
              )
          AND durum IN ('Onaylandı', 'Beklemede')
    ) THEN
        INSERT INTO EkipmanRezervasyon (uye_id, ekipman_id, baslangic_tarihi, bitis_tarihi, durum)
        VALUES (p_uye_id, p_ekipman_id, p_baslangic_tarihi, p_bitis_tarihi, 'Beklemede');
        SELECT 'Rezervasyon talebiniz alındı.' AS message;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Bu ekipman belirtilen tarihler arasında zaten rezerve edilmiş veya talep bekliyor.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_etkinlige_katilimci_ekle` (IN `p_uye_id` INT, IN `p_etkinlik_id` INT)   BEGIN
    -- Etkinlik kapasitesini kontrol et
    DECLARE mevcut_katilimci_sayisi INT;
    DECLARE etkinlik_kapasitesi INT;

    SELECT COUNT(*) INTO mevcut_katilimci_sayisi FROM EtkinlikKatilim WHERE etkinlik_id = p_etkinlik_id;
    SELECT kapasite INTO etkinlik_kapasitesi FROM Etkinlikler WHERE etkinlik_id = p_etkinlik_id;

    IF mevcut_katilimci_sayisi < etkinlik_kapasitesi THEN
        INSERT INTO EtkinlikKatilim (uye_id, etkinlik_id)
        VALUES (p_uye_id, p_etkinlik_id);
        SELECT 'Başarıyla katıldınız.' AS message;
    ELSE
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etkinlik kapasitesi dolu, katılım mümkün değil.';
    END IF;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_etkinlik_ekle` (IN `p_grup_id` INT, IN `p_etkinlik_adi` VARCHAR(150), IN `p_aciklama` TEXT, IN `p_tarih` DATE, IN `p_saat` TIME, IN `p_lokasyon_tipi` ENUM('Online','Fiziksel'), IN `p_lokasyon_detayi` VARCHAR(255), IN `p_kapasite` INT)   BEGIN
    INSERT INTO Etkinlikler (grup_id, etkinlik_adi, aciklama, tarih, saat, lokasyon_tipi, lokasyon_detayi, kapasite)
    VALUES (p_grup_id, p_etkinlik_adi, p_aciklama, p_tarih, p_saat, p_lokasyon_tipi, p_lokasyon_detayi, p_kapasite);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_grup_kaynak_ekle` (IN `p_grup_id` INT, IN `p_kaynak_id` INT)   BEGIN
    INSERT INTO GrupKaynak (grup_id, kaynak_id)
    VALUES (p_grup_id, p_kaynak_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_hobi_grubu_ekle` (IN `p_grup_adi` VARCHAR(100), IN `p_aciklama` TEXT, IN `p_kurulus_tarihi` DATE, IN `p_grup_yoneticisi_uye_id` INT)   BEGIN
    INSERT INTO HobiGruplari (grup_adi, aciklama, kurulus_tarihi, grup_yoneticisi_uye_id)
    VALUES (p_grup_adi, p_aciklama, p_kurulus_tarihi, p_grup_yoneticisi_uye_id);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_hobi_gruplarini_listele` ()   BEGIN
    SELECT
        HG.grup_id,
        HG.grup_adi,
        HG.aciklama,
        HG.kurulus_tarihi,
        U.ad AS yonetici_ad,
        U.soyad AS yonetici_soyad
    FROM HobiGruplari HG
    LEFT JOIN Uyeler U ON HG.grup_yoneticisi_uye_id = U.uye_id
    ORDER BY HG.grup_adi;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_uyeleri_listele` ()   BEGIN
    SELECT uye_id, ad, soyad, kullanici_adi, eposta, kayit_tarihi, ulke, profil_fotografi_url
    FROM Uyeler ORDER BY kayit_tarihi DESC;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_uye_ekle` (IN `p_ad` VARCHAR(50), IN `p_soyad` VARCHAR(50), IN `p_kullanici_adi` VARCHAR(50), IN `p_eposta` VARCHAR(100), IN `p_sifre_hash` VARCHAR(255), IN `p_ulke` VARCHAR(50), IN `p_profil_fotografi_url` VARCHAR(255))   BEGIN
    INSERT INTO Uyeler (ad, soyad, kullanici_adi, eposta, sifre_hash, ulke, profil_fotografi_url)
    VALUES (p_ad, p_soyad, p_kullanici_adi, p_eposta, p_sifre_hash, p_ulke, p_profil_fotografi_url);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `sp_uye_gruba_ekle` (IN `p_uye_id` INT, IN `p_grup_id` INT)   BEGIN
    INSERT INTO UyeGrup (uye_id, grup_id)
    VALUES (p_uye_id, p_grup_id);
END$$

--
-- İşlevler
--
CREATE DEFINER=`root`@`localhost` FUNCTION `fn_gruptaki_uye_sayisi` (`p_grup_id` INT) RETURNS INT(11) READS SQL DATA BEGIN
    DECLARE uye_sayisi INT;
    SELECT COUNT(uye_id) INTO uye_sayisi
    FROM UyeGrup
    WHERE grup_id = p_grup_id;
    RETURN uye_sayisi;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `bilgikaynaklari`
--

CREATE TABLE `bilgikaynaklari` (
  `kaynak_id` int(11) NOT NULL,
  `kaynak_adi` varchar(200) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `dosya_url` varchar(255) NOT NULL,
  `tip` enum('Makale','Video','Rehber','Link','Belge') NOT NULL,
  `yuklenme_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `yukleyen_uye_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `bilgikaynaklari`
--

INSERT INTO `bilgikaynaklari` (`kaynak_id`, `kaynak_adi`, `aciklama`, `dosya_url`, `tip`, `yuklenme_tarihi`, `yukleyen_uye_id`) VALUES
(1, 'VR Setini Bilgisayara Bağlama', 'VR Setini Bilgisayara Bağlamayı Öğreniyoruz...', 'https://www.youtube.com/watch?v=tUShFumfORA&list=PL4fGSI1pDJn6rnJKpaAkK1XK8QUfa9KqP&index=48', 'Video', '2025-06-14 23:25:01', 1),
(2, 'SQL Eğitimi', 'SQL Eğitim videosu', 'https://www.youtube.com/watch?v=L86eqtdC2as&pp=ygUldmVyaXRhYmFuxLEgecO2bmV0aW0gc2lzdGVtbGVyaSBkZXJzaQ%3D%3D', 'Video', '2025-06-15 13:52:41', 1);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ekipmanlar`
--

CREATE TABLE `ekipmanlar` (
  `ekipman_id` int(11) NOT NULL,
  `ekipman_adi` varchar(100) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `seri_no` varchar(100) DEFAULT NULL,
  `edinme_tarihi` date DEFAULT NULL,
  `durum` enum('Kullanılabilir','Bakımda','Rezervde','Arızalı') NOT NULL DEFAULT 'Kullanılabilir'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ekipmanlar`
--

INSERT INTO `ekipmanlar` (`ekipman_id`, `ekipman_adi`, `aciklama`, `seri_no`, `edinme_tarihi`, `durum`) VALUES
(1, 'VR Gözlük', 'Sanal Gerçeklik deneyimi için gözlük', '', '2025-05-04', 'Kullanılabilir'),
(3, 'Bisiklet ', '', '1', '2025-06-08', 'Bakımda');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `ekipmanrezervasyon`
--

CREATE TABLE `ekipmanrezervasyon` (
  `rezervasyon_id` int(11) NOT NULL,
  `uye_id` int(11) NOT NULL,
  `ekipman_id` int(11) NOT NULL,
  `baslangic_tarihi` datetime NOT NULL,
  `bitis_tarihi` datetime NOT NULL,
  `durum` enum('Onaylandı','Beklemede','Reddedildi','Tamamlandı','İptal Edildi') NOT NULL DEFAULT 'Beklemede',
  `rezervasyon_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `ekipmanrezervasyon`
--

INSERT INTO `ekipmanrezervasyon` (`rezervasyon_id`, `uye_id`, `ekipman_id`, `baslangic_tarihi`, `bitis_tarihi`, `durum`, `rezervasyon_tarihi`) VALUES
(1, 1, 1, '2025-06-17 10:00:00', '2025-06-17 12:00:00', 'Beklemede', '2025-06-15 13:35:42');

--
-- Tetikleyiciler `ekipmanrezervasyon`
--
DELIMITER $$
CREATE TRIGGER `trg_ekipman_rezervasyon_onay` AFTER UPDATE ON `ekipmanrezervasyon` FOR EACH ROW BEGIN
    IF NEW.durum = 'Onaylandı' AND OLD.durum != 'Onaylandı' THEN
        UPDATE Ekipmanlar
        SET durum = 'Rezervde'
        WHERE ekipman_id = NEW.ekipman_id;
    END IF;
    IF OLD.durum = 'Onaylandı' AND NEW.durum != 'Onaylandı' THEN
        -- Rezervasyon iptal edilirse veya tamamlanırsa, ekipmanı tekrar kullanılabilir yapabiliriz.
        -- Ancak dikkatli olunmalı, başka aktif rezervasyon var mı kontrolü gerekebilir.
        -- Basitçe, iptal veya tamamlanmada serbest bırakalım:
        IF NEW.durum IN ('İptal Edildi', 'Tamamlandı') THEN
            UPDATE Ekipmanlar
            SET durum = 'Kullanılabilir'
            WHERE ekipman_id = NEW.ekipman_id
            AND NOT EXISTS (
                SELECT 1 FROM EkipmanRezervasyon
                WHERE ekipman_id = NEW.ekipman_id
                AND durum IN ('Onaylandı', 'Beklemede')
                AND rezervasyon_id != NEW.rezervasyon_id -- Güncellenen rezervasyon haricindeki aktif rezervasyonları kontrol et
            );
        END IF;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `etkinlikkatilim`
--

CREATE TABLE `etkinlikkatilim` (
  `uye_id` int(11) NOT NULL,
  `etkinlik_id` int(11) NOT NULL,
  `katilim_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `etkinlikkatilim`
--

INSERT INTO `etkinlikkatilim` (`uye_id`, `etkinlik_id`, `katilim_tarihi`) VALUES
(1, 1, '2025-06-14 23:18:32'),
(1, 2, '2025-06-15 13:33:46'),
(1, 3, '2025-06-16 19:14:49'),
(2, 2, '2025-06-15 13:33:43');

--
-- Tetikleyiciler `etkinlikkatilim`
--
DELIMITER $$
CREATE TRIGGER `trg_etkinlik_katilim_kontrol` BEFORE INSERT ON `etkinlikkatilim` FOR EACH ROW BEGIN
    DECLARE mevcut_katilimci_sayisi INT;
    DECLARE etkinlik_kapasitesi INT;

    SELECT COUNT(*) INTO mevcut_katilimci_sayisi FROM EtkinlikKatilim WHERE etkinlik_id = NEW.etkinlik_id;
    SELECT kapasite INTO etkinlik_kapasitesi FROM Etkinlikler WHERE etkinlik_id = NEW.etkinlik_id;

    IF mevcut_katilimci_sayisi >= etkinlik_kapasitesi THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Etkinlik kapasitesi dolu! Yeni katılım yapılamaz.';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `etkinlikler`
--

CREATE TABLE `etkinlikler` (
  `etkinlik_id` int(11) NOT NULL,
  `grup_id` int(11) NOT NULL,
  `etkinlik_adi` varchar(150) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `tarih` date NOT NULL,
  `saat` time NOT NULL,
  `lokasyon_tipi` enum('Online','Fiziksel') NOT NULL,
  `lokasyon_detayi` varchar(255) DEFAULT NULL,
  `kapasite` int(11) NOT NULL DEFAULT 0,
  `olusturma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `etkinlikler`
--

INSERT INTO `etkinlikler` (`etkinlik_id`, `grup_id`, `etkinlik_adi`, `aciklama`, `tarih`, `saat`, `lokasyon_tipi`, `lokasyon_detayi`, `kapasite`, `olusturma_tarihi`) VALUES
(1, 1, 'Vr Deneyimleme', 'VR deniyoruz', '2025-06-14', '11:00:00', 'Fiziksel', 'Ankara Gölbaşı', 3, '2025-06-14 23:17:58'),
(2, 2, 'MotoGP Türkiye yarışı', 'MotoGP Türkiye yarışını canlı izliyoruz...', '2025-06-16', '20:00:00', 'Online', 'zoom/link037558erfhls.com', 15, '2025-06-15 13:33:22'),
(3, 3, 'Veritabanı giriş dersi', 'Veritabanı giriş dersi ', '2025-06-19', '22:16:00', 'Online', 'zoom/link037558erfhls.com', 1, '2025-06-16 19:14:35');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `grupkaynak`
--

CREATE TABLE `grupkaynak` (
  `grup_id` int(11) NOT NULL,
  `kaynak_id` int(11) NOT NULL,
  `eklenme_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `hobigruplari`
--

CREATE TABLE `hobigruplari` (
  `grup_id` int(11) NOT NULL,
  `grup_adi` varchar(100) NOT NULL,
  `aciklama` text DEFAULT NULL,
  `kurulus_tarihi` date NOT NULL,
  `grup_yoneticisi_uye_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `hobigruplari`
--

INSERT INTO `hobigruplari` (`grup_id`, `grup_adi`, `aciklama`, `kurulus_tarihi`, `grup_yoneticisi_uye_id`) VALUES
(1, 'VR', 'VR Severler', '2025-05-24', 1),
(2, 'MotoGP', 'MotoGp severler hobi grubu', '2025-03-17', 2),
(3, 'veri', 'veritabanı severler', '2025-06-07', 3);

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uyegrup`
--

CREATE TABLE `uyegrup` (
  `uye_id` int(11) NOT NULL,
  `grup_id` int(11) NOT NULL,
  `katilma_tarihi` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `uyegrup`
--

INSERT INTO `uyegrup` (`uye_id`, `grup_id`, `katilma_tarihi`) VALUES
(1, 1, '2025-06-14 23:18:11'),
(2, 2, '2025-06-15 13:55:51'),
(2, 3, '2025-06-16 19:13:17'),
(3, 2, '2025-06-16 19:12:23');

-- --------------------------------------------------------

--
-- Tablo için tablo yapısı `uyeler`
--

CREATE TABLE `uyeler` (
  `uye_id` int(11) NOT NULL,
  `ad` varchar(50) NOT NULL,
  `soyad` varchar(50) NOT NULL,
  `kullanici_adi` varchar(50) NOT NULL,
  `eposta` varchar(100) NOT NULL,
  `sifre_hash` varchar(255) NOT NULL,
  `kayit_tarihi` timestamp NOT NULL DEFAULT current_timestamp(),
  `ulke` varchar(50) DEFAULT NULL,
  `profil_fotografi_url` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Tablo döküm verisi `uyeler`
--

INSERT INTO `uyeler` (`uye_id`, `ad`, `soyad`, `kullanici_adi`, `eposta`, `sifre_hash`, `kayit_tarihi`, `ulke`, `profil_fotografi_url`) VALUES
(1, 'Muhammed Furkan ', 'Mercan', 'Mercan06', 'fmercan0671@gmail.com', '$2y$10$qztCVNZl8VoknKKcTCaJ8Ot3fEmKKODeKpVYyjAIwX8pZ2eMlVAhu', '2025-06-14 22:34:29', 'Türkiye', ''),
(2, 'İnanç ', 'Güler', 'inancguler', 'inancguler@gmail.com', '$2y$10$6K8.UphfToRZT9h5c5srHOllA.yT07MrhV5Hvh8lvg3udAlRSwHf.', '2025-06-15 13:29:58', 'Türkiye', ''),
(3, 'Halil', 'Al', 'halilal', 'halilal@gmail.com', '$2y$10$.B9IGzUGRVTJj6yRm0XGJOc6ZZpU15NS0pE5y8eSl.u2rXe2swvs6', '2025-06-16 19:12:06', 'Türkiye', '');

--
-- Tetikleyiciler `uyeler`
--
DELIMITER $$
CREATE TRIGGER `trg_uye_silme_sonrasi` AFTER DELETE ON `uyeler` FOR EACH ROW BEGIN
    -- Eğer silinen üye bir grubun yöneticisi ise, o grubun yöneticisini NULL yap
    UPDATE HobiGruplari
    SET grup_yoneticisi_uye_id = NULL
    WHERE grup_yoneticisi_uye_id = OLD.uye_id;

    -- Alternatif olarak, silinen üyelerin kaydını başka bir tabloya loglayabiliriz (Örn: UyeSilmeLog tablosu)
    -- Bu senaryoda bu log tablosunu oluşturmuyoruz, ancak bir fikir vermek adına belirtildi.
END
$$
DELIMITER ;

--
-- Dökümü yapılmış tablolar için indeksler
--

--
-- Tablo için indeksler `bilgikaynaklari`
--
ALTER TABLE `bilgikaynaklari`
  ADD PRIMARY KEY (`kaynak_id`),
  ADD KEY `yukleyen_uye_id` (`yukleyen_uye_id`);

--
-- Tablo için indeksler `ekipmanlar`
--
ALTER TABLE `ekipmanlar`
  ADD PRIMARY KEY (`ekipman_id`),
  ADD UNIQUE KEY `seri_no` (`seri_no`);

--
-- Tablo için indeksler `ekipmanrezervasyon`
--
ALTER TABLE `ekipmanrezervasyon`
  ADD PRIMARY KEY (`rezervasyon_id`),
  ADD KEY `uye_id` (`uye_id`),
  ADD KEY `ekipman_id` (`ekipman_id`);

--
-- Tablo için indeksler `etkinlikkatilim`
--
ALTER TABLE `etkinlikkatilim`
  ADD PRIMARY KEY (`uye_id`,`etkinlik_id`),
  ADD KEY `etkinlik_id` (`etkinlik_id`);

--
-- Tablo için indeksler `etkinlikler`
--
ALTER TABLE `etkinlikler`
  ADD PRIMARY KEY (`etkinlik_id`),
  ADD KEY `grup_id` (`grup_id`);

--
-- Tablo için indeksler `grupkaynak`
--
ALTER TABLE `grupkaynak`
  ADD PRIMARY KEY (`grup_id`,`kaynak_id`),
  ADD KEY `kaynak_id` (`kaynak_id`);

--
-- Tablo için indeksler `hobigruplari`
--
ALTER TABLE `hobigruplari`
  ADD PRIMARY KEY (`grup_id`),
  ADD UNIQUE KEY `grup_adi` (`grup_adi`),
  ADD KEY `grup_yoneticisi_uye_id` (`grup_yoneticisi_uye_id`);

--
-- Tablo için indeksler `uyegrup`
--
ALTER TABLE `uyegrup`
  ADD PRIMARY KEY (`uye_id`,`grup_id`),
  ADD KEY `grup_id` (`grup_id`);

--
-- Tablo için indeksler `uyeler`
--
ALTER TABLE `uyeler`
  ADD PRIMARY KEY (`uye_id`),
  ADD UNIQUE KEY `kullanici_adi` (`kullanici_adi`),
  ADD UNIQUE KEY `eposta` (`eposta`);

--
-- Dökümü yapılmış tablolar için AUTO_INCREMENT değeri
--

--
-- Tablo için AUTO_INCREMENT değeri `bilgikaynaklari`
--
ALTER TABLE `bilgikaynaklari`
  MODIFY `kaynak_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Tablo için AUTO_INCREMENT değeri `ekipmanlar`
--
ALTER TABLE `ekipmanlar`
  MODIFY `ekipman_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `ekipmanrezervasyon`
--
ALTER TABLE `ekipmanrezervasyon`
  MODIFY `rezervasyon_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Tablo için AUTO_INCREMENT değeri `etkinlikler`
--
ALTER TABLE `etkinlikler`
  MODIFY `etkinlik_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `hobigruplari`
--
ALTER TABLE `hobigruplari`
  MODIFY `grup_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Tablo için AUTO_INCREMENT değeri `uyeler`
--
ALTER TABLE `uyeler`
  MODIFY `uye_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Dökümü yapılmış tablolar için kısıtlamalar
--

--
-- Tablo kısıtlamaları `bilgikaynaklari`
--
ALTER TABLE `bilgikaynaklari`
  ADD CONSTRAINT `bilgikaynaklari_ibfk_1` FOREIGN KEY (`yukleyen_uye_id`) REFERENCES `uyeler` (`uye_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `ekipmanrezervasyon`
--
ALTER TABLE `ekipmanrezervasyon`
  ADD CONSTRAINT `ekipmanrezervasyon_ibfk_1` FOREIGN KEY (`uye_id`) REFERENCES `uyeler` (`uye_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ekipmanrezervasyon_ibfk_2` FOREIGN KEY (`ekipman_id`) REFERENCES `ekipmanlar` (`ekipman_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `etkinlikkatilim`
--
ALTER TABLE `etkinlikkatilim`
  ADD CONSTRAINT `etkinlikkatilim_ibfk_1` FOREIGN KEY (`uye_id`) REFERENCES `uyeler` (`uye_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `etkinlikkatilim_ibfk_2` FOREIGN KEY (`etkinlik_id`) REFERENCES `etkinlikler` (`etkinlik_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `etkinlikler`
--
ALTER TABLE `etkinlikler`
  ADD CONSTRAINT `etkinlikler_ibfk_1` FOREIGN KEY (`grup_id`) REFERENCES `hobigruplari` (`grup_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `grupkaynak`
--
ALTER TABLE `grupkaynak`
  ADD CONSTRAINT `grupkaynak_ibfk_1` FOREIGN KEY (`grup_id`) REFERENCES `hobigruplari` (`grup_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `grupkaynak_ibfk_2` FOREIGN KEY (`kaynak_id`) REFERENCES `bilgikaynaklari` (`kaynak_id`) ON DELETE CASCADE;

--
-- Tablo kısıtlamaları `hobigruplari`
--
ALTER TABLE `hobigruplari`
  ADD CONSTRAINT `hobigruplari_ibfk_1` FOREIGN KEY (`grup_yoneticisi_uye_id`) REFERENCES `uyeler` (`uye_id`) ON DELETE SET NULL;

--
-- Tablo kısıtlamaları `uyegrup`
--
ALTER TABLE `uyegrup`
  ADD CONSTRAINT `uyegrup_ibfk_1` FOREIGN KEY (`uye_id`) REFERENCES `uyeler` (`uye_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `uyegrup_ibfk_2` FOREIGN KEY (`grup_id`) REFERENCES `hobigruplari` (`grup_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

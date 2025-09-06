# 🔍 Domain Hunter - Kullanım Kılavuzu

## 🚀 Hızlı Başlangıç

### 1. Sistem Gereksinimlerini Kontrol Et
```bash
php cron_setup.php check
```

### 2. İlk Domain Taramasını Başlat
```bash
php domain_hunter.php check
```

### 3. Web Arayüzünü Aç
Tarayıcınızda `http://localhost/` adresine gidin.

## 📋 Komut Satırı Kullanımı

### Otomatik Tarama
```bash
# Rastgele 100 domain kontrol et
php domain_hunter.php check
```

### Müsait Domainleri Listele
```bash
# İlk 50 müsait domain
php domain_hunter.php available

# İlk 100 müsait domain, minimum $50 değerinde
php domain_hunter.php available 100 50
```

### Süresi Dolacak Domainleri Listele
```bash
# Sonraki 30 gün içinde süresi dolacaklar
php domain_hunter.php expiring

# Sonraki 7 gün içinde süresi dolacaklar
php domain_hunter.php expiring 7
```

### İstatistikleri Görüntüle
```bash
php domain_hunter.php stats
```

## ⚙️ Cron Job Yönetimi

### Cron Job Kur
```bash
# Her 60 dakikada bir tarama
php cron_setup.php setup 60

# Her 30 dakikada bir tarama
php cron_setup.php setup 30
```

### Cron Job'ları Listele
```bash
php cron_setup.php list
```

### Cron Job'ı Kaldır
```bash
php cron_setup.php remove
```

### Test Çalıştırması
```bash
php cron_setup.php test
```

## 🌐 Web Arayüzü Kullanımı

### Dashboard
- Toplam istatistikleri görüntüleyin
- TLD dağılımını inceleyin
- En değerli domainleri görün

### Müsait Domainler
- Bulunan müsait domainleri listeleyin
- Limit ve minimum değer filtresi uygulayın
- Tahmini değerleri görün

### Süresi Dolacaklar
- Yakında süresi dolacak domainleri takip edin
- Gün sayısı filtresi uygulayın
- Registrar bilgilerini görün

### Manuel Arama
- Belirli bir domaini kontrol edin
- Farklı TLD'leri test edin
- Anında sonuç alın

### Otomatik Tarayıcı
- Web arayüzünden tarama başlatın
- Gerçek zamanlı sonuçları görün
- Yeni bulunan domainleri listeleyin

## 🛠️ Yapılandırma

### TLD Ayarları (config.php)
```php
'tlds' => [
    'com' => [
        'whois_server' => 'whois.verisign-grs.com',
        'price' => 12,
        'priority' => 10
    ],
    // Yeni TLD ekleyebilirsiniz
]
```

### Premium Kelimeler
```php
'premium_keywords' => [
    'high_value' => ['ai', 'blockchain', 'crypto'],
    'turkish' => ['para', 'teknoloji', 'dijital'],
    // Kendi kelimelerinizi ekleyebilirsiniz
]
```

### Tarama Ayarları
```php
'scanning' => [
    'max_domains_per_run' => 100,
    'timeout' => 30,
    'delay_between_queries' => 0.5,
]
```

## 📈 Değer Hesaplama

Domain değerleri şu faktörlere göre hesaplanır:

### 1. Domain Uzunluğu
- **1-2 karakter**: 25x-50x çarpan
- **3-4 karakter**: 8x-15x çarpan  
- **5-6 karakter**: 2x-4x çarpan
- **7+ karakter**: 1x-1.5x çarpan

### 2. Premium Kelime Kontrolü
- **Yüksek değerli**: ai, blockchain, crypto → 10x
- **Orta değerli**: business, money, shop → 5x
- **Düşük değerli**: app, web, site → 2x

### 3. Pattern Bonusu
- Sadece harf: +2x
- Harf + sayı: +1.5x
- Tekrar eden karakter yok: +1.2x

### Örnek Hesaplama
```
Domain: ai.com
- Taban fiyat: $12 (.com)
- Uzunluk çarpanı: 25x (2 karakter)
- Premium çarpanı: 10x ('ai')
- Tahmini değer: $12 × 25 × 10 = $3,000
```

## 📊 Veritabanı Yönetimi

### SQLite Dosyası
- **Konum**: `domain_hunter.db`
- **Yedekleme**: Düzenli olarak yedekleyin
- **Boyut**: Otomatik temizlik aktif

### Tablolar
- `domains`: Domain bilgileri
- `alerts`: Bildirimler
- `settings`: Ayarlar

### Temizlik
```bash
# Eski kayıtları temizle (30 günden eski)
# Otomatik olarak yapılır, manuel gerekmiyor
```

## 🔔 Bildirimler

### E-posta Bildirimi (config.php)
```php
'notifications' => [
    'email' => [
        'enabled' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_user' => 'your-email@gmail.com',
        'to_email' => 'your-email@gmail.com'
    ]
]
```

### Webhook Bildirimi
```php
'webhook' => [
    'enabled' => true,
    'url' => 'https://hooks.slack.com/services/YOUR/WEBHOOK'
]
```

## 🔍 İpuçları ve En İyi Uygulamalar

### 1. Tarama Sıklığı
- **Yoğun tarama**: Her 30 dakika
- **Normal tarama**: Her 1 saat
- **Hafif tarama**: Her 2-4 saat

### 2. Rate Limiting
- WHOIS sunucularını aşırı yüklemeyin
- Sorgu gecikmesini artırın (0.5-2 saniye)
- Timeout değerini ayarlayın

### 3. Değerli Domainler
- 1-4 karakter domainleri öncelik
- Premium kelimeli kombinasyonlar
- Popüler TLD'ler (.com, .io, .net)

### 4. Filtreleme
- Minimum değer filtresi kullanın
- TLD önceliği ayarlayın
- Özel kelime listeleri oluşturun

## 🚨 Sorun Giderme

### PHP Hataları
```bash
# PHP log dosyasını kontrol et
tail -f /var/log/php_errors.log
```

### WHOIS Hataları
```bash
# Timeout süresini artır
'timeout' => 60

# Manuel test
php domain_hunter.php check
```

### Veritabanı Hataları
```bash
# İzinleri kontrol et
chmod 666 domain_hunter.db
chmod 777 .
```

### Cron Job Hataları
```bash
# Cron log dosyasını kontrol et
tail -f cron.log

# Cron servisini kontrol et
sudo service cron status
```

## 📞 Destek ve Yardım

### Log Dosyaları
- `domain_hunter.log` - Ana loglar
- `cron.log` - Cron job logları
- `error.log` - Hata logları

### Komut Yardımı
```bash
# Genel yardım
php domain_hunter.php

# Cron yardımı
php cron_setup.php
```

### Sistem Durumu
```bash
# Sistem kontrolü
php cron_setup.php check

# İstatistikler
php domain_hunter.php stats
```

---

**Domain Hunter v1.0** - Premium domainleri keşfetmenin en kolay yolu! 🚀

> **Not**: Bu script eğitim ve araştırma amaçlıdır. WHOIS sunucularını aşırı yüklememeye dikkat edin ve her zaman ilgili ToS'lara uyun.
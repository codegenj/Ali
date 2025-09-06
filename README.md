# 🚀 Premium Domain Checker & Drop Catcher

Domain alım-satım işleri için geliştirilmiş kapsamlı bir PHP uygulaması. Düşen domainleri, premium domainleri ve yakında düşecek domainleri takip eder.

## ✨ Özellikler

- **Çoklu Uzantı Desteği**: com, net, org, com.tr, net.tr, org.tr ve daha fazlası
- **Premium Domain Tespiti**: Kısa domainler, anahtar kelimeler ve sayısal domainler
- **Otomatik Takip**: Cron job ile düzenli kontrol
- **Web Arayüzü**: Modern ve kullanıcı dostu arayüz
- **Bildirim Sistemi**: E-posta ile anlık bildirimler
- **Veritabanı Entegrasyonu**: MySQL ile güvenli veri saklama
- **WHOIS Entegrasyonu**: Detaylı domain bilgileri

## 🛠️ Kurulum

### 1. Gereksinimler

- PHP 7.4 veya üzeri
- MySQL 5.7 veya üzeri
- Web sunucusu (Apache/Nginx)
- cURL extension
- PDO MySQL extension

### 2. Dosyaları Yükle

```bash
# Projeyi klonlayın veya dosyaları sunucunuza yükleyin
git clone https://github.com/your-repo/domain-checker.git
cd domain-checker
```

### 3. Veritabanı Ayarları

`config.php` dosyasını düzenleyin:

```php
'db' => [
    'host' => 'localhost',
    'name' => 'domain_checker',
    'user' => 'your_username',
    'pass' => 'your_password'
],
```

### 4. Kurulum Scripti

```bash
php install.php
```

### 5. Web Sunucusu Ayarları

Apache için `.htaccess` dosyası:

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ domain_checker.php [QSA,L]
```

## 📖 Kullanım

### Web Arayüzü

1. Tarayıcınızda `http://your-domain/domain_checker.php` adresine gidin
2. Domain adını girin ve "Kontrol Et" butonuna tıklayın
3. Sonuçları görüntüleyin:
   - **Sonuçlar**: Arama sonuçları
   - **Düşen Domainler**: Müsait olan premium domainler
   - **Yakında Düşecekler**: Süresi yakında bitecek domainler

### Komut Satırı

```bash
# Tek domain kontrolü
php domain_checker.php example

# Cron job çalıştırma
php cron_job.php
```

### Cron Job Ayarlama

Otomatik kontrol için crontab'a ekleyin:

```bash
# Her saat başı çalıştır
0 * * * * /usr/bin/php /path/to/cron_job.php

# Her 30 dakikada bir çalıştır
*/30 * * * * /usr/bin/php /path/to/cron_job.php
```

## ⚙️ Konfigürasyon

### Domain Uzantıları

`config.php` dosyasında `extensions` dizisini düzenleyin:

```php
'extensions' => [
    'com', 'net', 'org', 'com.tr', 'net.tr', 'org.tr',
    'info', 'biz', 'co', 'io', 'me', 'tv', 'cc'
],
```

### Premium Domain Kriterleri

`premium_keywords` dizisini özelleştirin:

```php
'premium_keywords' => [
    'business', 'money', 'finance', 'tech', 'online',
    'is', 'ticaret', 'saglik', 'egitim' // Türkçe kelimeler
],
```

### Bildirim Ayarları

E-posta bildirimleri için:

```php
'notifications' => [
    'email' => [
        'enabled' => true,
        'smtp_host' => 'smtp.gmail.com',
        'smtp_port' => 587,
        'smtp_user' => 'your-email@gmail.com',
        'smtp_pass' => 'your-app-password',
        'from_email' => 'your-email@gmail.com',
        'to_email' => 'notifications@yourdomain.com'
    ]
]
```

## 📊 Veritabanı Yapısı

### Ana Tablolar

- `domain_checks`: Domain kontrol sonuçları
- `premium_domains`: Premium domain listesi
- `watchlist`: Takip edilen domainler
- `notifications`: Bildirim geçmişi
- `cron_logs`: Cron job logları

### Önemli View'lar

- `expiring_premium_domains`: Yakında düşecek premium domainler
- `dropped_premium_domains`: Düşen premium domainler

## 🔧 API Endpoints

### GET Parametreleri

- `?action=check&domain=example`: Domain kontrol et
- `?action=dropped`: Düşen domainleri getir
- `?action=expiring&days=30`: Yakında düşecek domainleri getir
- `?action=search&keyword=test`: Domain arama

### POST Parametreleri

Domain kontrolü için:

```bash
curl -X POST -d "domain=example" http://your-domain/domain_checker.php?action=check
```

## 📈 Performans Optimizasyonu

### Veritabanı İndeksleri

```sql
-- Önemli indeksler otomatik oluşturulur
CREATE INDEX idx_domain_status_premium ON domain_checks(domain, status, is_premium);
CREATE INDEX idx_drop_date_status ON domain_checks(drop_date, status);
```

### Cache Ayarları

```php
'cache' => [
    'enabled' => true,
    'ttl' => 3600, // 1 saat
    'path' => '/tmp/domain_cache'
]
```

## 🚨 Güvenlik

### Veritabanı Güvenliği

1. Güçlü şifreler kullanın
2. Veritabanı kullanıcısı için sınırlı yetkiler verin
3. Düzenli yedekleme yapın

### Dosya İzinleri

```bash
chmod 755 /path/to/domain-checker/
chmod 644 /path/to/domain-checker/*.php
chmod 600 /path/to/domain-checker/config.php
```

## 🐛 Sorun Giderme

### Yaygın Hatalar

1. **Veritabanı Bağlantı Hatası**
   - `config.php` ayarlarını kontrol edin
   - MySQL servisinin çalıştığından emin olun

2. **WHOIS Hatası**
   - Sunucunuzun internet bağlantısını kontrol edin
   - Firewall ayarlarını kontrol edin

3. **Cron Job Çalışmıyor**
   - PHP path'ini kontrol edin: `which php`
   - Dosya izinlerini kontrol edin

### Log Dosyaları

```bash
# Cron job logları
tail -f /var/log/domain_checker_cron.log

# Genel loglar
tail -f /var/log/domain_checker.log
```

## 📝 Lisans

Bu proje MIT lisansı altında lisanslanmıştır.

## 🤝 Katkıda Bulunma

1. Fork yapın
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Commit yapın (`git commit -m 'Add amazing feature'`)
4. Push yapın (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📞 Destek

Sorularınız için:
- GitHub Issues
- E-posta: support@yourdomain.com

## 🔄 Güncellemeler

### v1.0.0
- İlk sürüm
- Temel domain kontrolü
- Web arayüzü
- Cron job desteği

---

**Not**: Bu uygulama eğitim ve kişisel kullanım amaçlıdır. Ticari kullanım için gerekli lisansları kontrol edin.
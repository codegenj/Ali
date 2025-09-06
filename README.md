# 🔍 Domain Hunter - Premium Domain Takipçisi

Premium jenerik domainleri bulan ve takip eden profesyonel PHP scripti. Boş domainleri otomatik olarak tarar, değerlendirir ve size bildirir.

## ✨ Özellikler

### 🎯 Ana Özellikler
- **Otomatik Domain Tarama**: Premium jenerik domainleri otomatik olarak tarar
- **Multi-TLD Desteği**: .com, .net, .org, .com.tr, .io, .tech ve daha fazlası
- **WHOIS Sorgulama**: Gerçek zamanlı domain durumu kontrolü
- **Değer Hesaplama**: Domainlerin tahmini piyasa değerini hesaplar
- **Expiry Takibi**: Süresi dolacak domainleri takip eder
- **Web Arayüzü**: Modern ve kullanıcı dostu web paneli

### 🚀 Gelişmiş Özellikler
- **Cron Job Desteği**: Otomatik periyodik tarama
- **SQLite Veritabanı**: Hafif ve hızlı veri saklama
- **Çoklu Dil Desteği**: Türkçe ve İngilizce premium kelimeler
- **Rate Limiting**: Güvenli ve stabil WHOIS sorguları
- **Alert Sistemi**: Yeni müsait domainler için bildirimler
- **İstatistikler**: Detaylı raporlama ve analiz

## 📋 Gereksinimler

- **PHP 7.4+** (PHP 8.x önerilir)
- **SQLite3 Extension**
- **PDO Extension**
- **cURL Extension** (isteğe bağlı)
- **Web Sunucusu** (Apache/Nginx)
- **Crontab** (otomatik tarama için)

## 🛠️ Kurulum

### 1. Dosyaları İndirin
```bash
git clone https://github.com/your-repo/domain-hunter.git
cd domain-hunter
```

### 2. İzinleri Ayarlayın
```bash
chmod 755 *.php
chmod 777 . # Veritabanı dosyası için yazma izni
```

### 3. Sistem Gereksinimlerini Kontrol Edin
```bash
php cron_setup.php check
```

### 4. Web Sunucusunu Yapılandırın
Apache için `.htaccess` dosyası:
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Güvenlik
<Files "*.db">
    Order allow,deny
    Deny from all
</Files>

<Files "*.log">
    Order allow,deny
    Deny from all
</Files>
```

## 🚀 Kullanım

### Web Arayüzü
1. Tarayıcınızda `http://yoursite.com/domain-hunter` adresine gidin
2. Dashboard'dan genel durumu görüntüleyin
3. "Otomatik Tarama" sekmesinden yeni tarama başlatın
4. "Müsait Domainler" sekmesinden bulunan domainleri görün

### Komut Satırı Kullanımı

#### Otomatik Tarama
```bash
php domain_hunter.php check
```

#### Müsait Domainleri Listele
```bash
# İlk 50 müsait domain
php domain_hunter.php available

# İlk 100 müsait domain, minimum $50 değerinde
php domain_hunter.php available 100 50
```

#### Süresi Dolacak Domainleri Listele
```bash
# Sonraki 30 gün içinde süresi dolacaklar
php domain_hunter.php expiring

# Sonraki 7 gün içinde süresi dolacaklar
php domain_hunter.php expiring 7
```

#### İstatistikleri Görüntüle
```bash
php domain_hunter.php stats
```

### Cron Job Kurulumu

#### Otomatik Kurulum
```bash
# Her 60 dakikada bir tarama
php cron_setup.php setup 60

# Her 30 dakikada bir tarama
php cron_setup.php setup 30
```

#### Manuel Kurulum
```bash
crontab -e
```
Aşağıdaki satırı ekleyin:
```bash
# Her saat başı domain taraması
0 * * * * /usr/bin/php /path/to/domain_hunter.php check >> /path/to/cron.log 2>&1
```

#### Cron Job Yönetimi
```bash
# Mevcut cron job'ları listele
php cron_setup.php list

# Cron job'ı kaldır
php cron_setup.php remove

# Test çalıştırması
php cron_setup.php test
```

## ⚙️ Konfigürasyon

`config.php` dosyasını düzenleyerek ayarları özelleştirebilirsiniz:

### TLD Ayarları
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
    'high_value' => ['ai', 'blockchain', 'crypto', 'nft'],
    'turkish' => ['para', 'is', 'teknoloji', 'dijital'],
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

## 📊 Değer Hesaplama Algoritması

Domain değerleri şu faktörlere göre hesaplanır:

1. **Domain Uzunluğu**
   - 1-2 karakter: Çok yüksek değer (25x-50x)
   - 3-4 karakter: Yüksek değer (8x-15x)
   - 5-6 karakter: Orta değer (2x-4x)
   - 7+ karakter: Normal değer (1x-1.5x)

2. **Premium Kelime Kontrolü**
   - Yüksek değerli terimler: 10x çarpan
   - Orta değerli terimler: 5x çarpan
   - Düşük değerli terimler: 2x çarpan

3. **Pattern Analizi**
   - Sadece harf: 2x çarpan
   - Harf + sayı: 1.5x çarpan
   - Tekrar eden karakter yok: 1.2x çarpan

4. **TLD Fiyatı**
   - Her TLD'nin kendi taban fiyatı vardır

**Örnek Hesaplama:**
- Domain: `ai.com`
- Taban fiyat: $12 (.com)
- Uzunluk çarpanı: 25x (2 karakter)
- Premium kelime çarpanı: 10x ('ai' yüksek değerli)
- **Tahmini değer:** $12 × 25 × 10 = $3,000

## 📈 Raporlama ve İstatistikler

### Dashboard Metrikleri
- Toplam kontrol edilen domain sayısı
- Müsait domain sayısı
- Kayıtlı domain sayısı
- TLD dağılımı
- En değerli müsait domainler

### Export Özellikleri
- JSON formatında veri export
- CSV formatında rapor oluşturma
- Özel filtreleme seçenekleri

## 🔒 Güvenlik

### Önerilen Güvenlik Ayarları
```php
'security' => [
    'api_key' => 'your-secret-key',
    'allowed_ips' => ['192.168.1.100', '10.0.0.50'],
    'rate_limit' => [
        'enabled' => true,
        'max_requests' => 100
    ]
]
```

### Veritabanı Güvenliği
- SQLite dosyasını web erişiminden koruyun
- Düzenli yedek alın
- Log dosyalarını güvenli tutun

## 🐛 Sorun Giderme

### Yaygın Sorunlar

**1. WHOIS Sorgu Hataları**
```bash
# Timeout süresini artırın
'timeout' => 60

# Sorgu gecikmesini artırın
'delay_between_queries' => 1.0
```

**2. Veritabanı İzin Hataları**
```bash
chmod 666 domain_hunter.db
chmod 777 . # Dizin yazma izni
```

**3. Cron Job Çalışmıyor**
```bash
# Cron servisini kontrol edin
service cron status

# Log dosyasını kontrol edin
tail -f cron.log
```

**4. PHP Uzantı Hataları**
```bash
# Ubuntu/Debian
sudo apt-get install php-sqlite3 php-pdo

# CentOS/RHEL
sudo yum install php-pdo php-sqlite3
```

### Debug Modu
```php
// domain_hunter.php içinde
define('DEBUG', true);
```

## 📝 Log Dosyaları

### Log Türleri
- `domain_hunter.log` - Ana uygulama logları
- `cron.log` - Cron job logları
- `error.log` - Hata logları

### Log Formatı
```
[2024-01-15 14:30:25] Domain Hunter başlatıldı
[2024-01-15 14:30:26] Kontrol ediliyor: example.com
[2024-01-15 14:30:27] MÜSAİT DOMAIN BULUNDU: example.com (Tahmini değer: 150 USD)
```

## 🔄 Güncelleme ve Bakım

### Otomatik Veritabanı Temizliği
```php
'advanced' => [
    'database_cleanup' => true,
    'cleanup_older_than' => 2592000, // 30 gün
]
```

### Yedekleme
```bash
# Manuel yedek
cp domain_hunter.db backup_$(date +%Y%m%d).db

# Otomatik yedekleme ayarı
'backup_enabled' => true,
'backup_interval' => 86400, // 24 saat
```

## 🤝 Katkıda Bulunma

1. Bu repository'yi fork edin
2. Feature branch oluşturun (`git checkout -b feature/amazing-feature`)
3. Değişikliklerinizi commit edin (`git commit -m 'Add amazing feature'`)
4. Branch'inizi push edin (`git push origin feature/amazing-feature`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasına bakın.

## ⚠️ Yasal Uyarı

Bu script sadece eğitim ve araştırma amaçlıdır. WHOIS sunucularını aşırı yüklememeye dikkat edin ve her zaman ilgili ToS'lara uyun.

## 📞 Destek

- **GitHub Issues**: Hata raporları ve özellik istekleri
- **Email**: support@domain-hunter.com
- **Dokümantasyon**: https://domain-hunter.readthedocs.io

## 🙏 Teşekkürler

- WHOIS sunucu sağlayıcıları
- PHP topluluğu
- Açık kaynak katkıda bulunanlar

---

**Domain Hunter v1.0** - Premium domainleri keşfetmenin en kolay yolu! 🚀
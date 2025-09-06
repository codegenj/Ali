### Domain Checker (PHP CLI)

Basit bağımlılıksız bir PHP CLI aracı. WHOIS üzerinden domain müsaitliği, durumları (Status), bitiş tarihi ve yaklaşık düşüş zamanını (drop) gösterir. TLD listesi yapılandırılabilir.

### Gereksinimler
- PHP 7.4+ (CLI)
- İnternet erişimi (port 43 WHOIS)

### Kurulum
```bash
cd /workspace/domain-tool
chmod +x bin/domain-checker.php
```

### Örnek Kullanım
```bash
# Örnek domain kökleri ve TLD dosyaları ile tablo çıktısı
php bin/domain-checker.php --domains samples/domains.txt --tlds-file samples/tlds.txt

# Sadece boşta olanları JSON çıktı ile
php bin/domain-checker.php --domains samples/domains.txt --tlds com,net,org --available-only --output json

# WHOIS yönlendirmesini takip ederek (Registrar WHOIS)
php bin/domain-checker.php --domains samples/domains.txt --tlds com,net --follow-referral
```

- `--domains`: Satır başına `mybrand`, `ultra` gibi kök isimler yazılır. Araç bunları `.<tld>` ile birleştirir.
- `--tlds` veya `--tlds-file`: TLD listesi.
- `--output`: `table` (varsayılan), `json`, `csv`.
- `--available-only`: Sadece boşta (available) olanları gösterir.
- `--follow-referral`: Registrar WHOIS yönlendirmesini takip eder (daha doğru status/expiry için önerilir).
- `--config`: `config/tlds.json` yerine özel bir yapılandırma dosyası verebilirsiniz.

### Düşüş Zamanı (Drop) Tahmini
- `config/tlds.json` içinde `drop_windows` ve `lifecycle_days` tanımlıdır.
- Varsayılan tahmin: `drop ≈ expiry + auto_renew_grace + redemption + pending_delete` ve günün belirli saat penceresi.
- `.com/.net/.org` için düşüş penceresi yaklaşık 14:00-15:00 UTC olarak varsayılmıştır. Gerçek pencereler kayıt operatörüne göre değişebilir.

### Yapılandırma (config/tlds.json)
- `tlds.*.whois`: WHOIS sunucusu.
- `available_phrases` / `available_regexes`: Boşta olduğunu tespit eden ipuçları.
- `expiry_regexes`, `created_regexes`, `status_regexes`: WHOIS çıktılarına göre tarih ve durum yakalama kalıpları.
- Gerektikçe yeni TLD’ler ekleyip regex/mesajları güncelleyebilirsiniz.

### Örnek Dosyalar (samples/)
- `domains.txt`: Kök liste
- `tlds.txt`: TLD listesi

### Notlar ve Sınırlamalar
- WHOIS çıktıları operatör bazlı değişir, regex’leri TLD’ye göre güncellemek gerekebilir.
- Bazı TLD’ler port 43 WHOIS desteğini kapatmış olabilir veya oran sınırlaması (rate-limit) uygulayabilir.
- `.com.tr` için TRABIS geçişi sonrası değişiklikler olabilir; whois sunucusu/formatı zamanla değişebilir.
- "Drop" tahmini hukuki/operasyonel garanti değildir; yaklaşık bilgi sunar.

### Lisans
MIT


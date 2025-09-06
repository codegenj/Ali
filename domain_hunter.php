<?php
/**
 * Premium Domain Hunter
 * Boştaki premium jenerik domainleri bulur ve takip eder
 * 
 * @author Domain Hunter Script
 * @version 1.0
 */

class DomainHunter {
    
    private $config;
    private $db;
    private $logFile = 'domain_hunter.log';
    
    public function __construct() {
        $this->loadConfig();
        $this->initDatabase();
        $this->log("Domain Hunter başlatıldı");
    }
    
    /**
     * Konfigürasyon dosyasını yükler
     */
    private function loadConfig() {
        $this->config = [
            'tlds' => [
                'com' => ['whois_server' => 'whois.verisign-grs.com', 'price' => 12],
                'net' => ['whois_server' => 'whois.verisign-grs.com', 'price' => 12],
                'org' => ['whois_server' => 'whois.pir.org', 'price' => 15],
                'info' => ['whois_server' => 'whois.afilias.net', 'price' => 10],
                'biz' => ['whois_server' => 'whois.neulevel.biz', 'price' => 10],
                'com.tr' => ['whois_server' => 'whois.nic.tr', 'price' => 25],
                'net.tr' => ['whois_server' => 'whois.nic.tr', 'price' => 25],
                'org.tr' => ['whois_server' => 'whois.nic.tr', 'price' => 25],
                'io' => ['whois_server' => 'whois.nic.io', 'price' => 60],
                'co' => ['whois_server' => 'whois.nic.co', 'price' => 30]
            ],
            'premium_keywords' => [
                // Teknoloji
                'ai', 'tech', 'app', 'web', 'cloud', 'data', 'crypto', 'nft', 'blockchain',
                'mobile', 'digital', 'online', 'software', 'code', 'dev', 'api', 'saas',
                
                // İş & Finans
                'money', 'bank', 'pay', 'loan', 'invest', 'trade', 'shop', 'store', 'market',
                'business', 'company', 'corp', 'finance', 'cash', 'buy', 'sell', 'deal',
                
                // Sağlık & Yaşam
                'health', 'medical', 'doctor', 'fitness', 'beauty', 'life', 'care', 'wellness',
                'diet', 'nutrition', 'hospital', 'clinic', 'pharmacy', 'medicine',
                
                // Eğitim & Kişisel Gelişim
                'education', 'learn', 'course', 'training', 'school', 'university', 'study',
                'book', 'guide', 'tutorial', 'skill', 'expert', 'master', 'pro',
                
                // Eğlence & Sosyal
                'game', 'play', 'fun', 'music', 'video', 'photo', 'social', 'chat',
                'dating', 'love', 'friend', 'community', 'forum', 'blog', 'news',
                
                // Seyahat & Yaşam Tarzı
                'travel', 'hotel', 'flight', 'car', 'home', 'real', 'estate', 'rent',
                'food', 'restaurant', 'recipe', 'fashion', 'style', 'luxury',
                
                // Türkçe Kelimeler
                'para', 'is', 'ev', 'araba', 'saglik', 'egitim', 'teknoloji', 'dijital',
                'mobil', 'uygulama', 'web', 'site', 'blog', 'haber', 'oyun', 'muzik'
            ],
            'check_interval' => 3600, // 1 saat
            'max_threads' => 10,
            'timeout' => 30
        ];
    }
    
    /**
     * SQLite veritabanını başlatır
     */
    private function initDatabase() {
        try {
            $this->db = new PDO('sqlite:domain_hunter.db');
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Tabloları oluştur
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS domains (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    domain_name TEXT UNIQUE,
                    tld TEXT,
                    status TEXT,
                    expiry_date DATETIME,
                    registrar TEXT,
                    estimated_value INTEGER,
                    check_count INTEGER DEFAULT 0,
                    first_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_checked DATETIME DEFAULT CURRENT_TIMESTAMP,
                    notes TEXT
                )
            ");
            
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS alerts (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    domain_id INTEGER,
                    alert_type TEXT,
                    alert_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    message TEXT,
                    FOREIGN KEY (domain_id) REFERENCES domains (id)
                )
            ");
            
            $this->db->exec("
                CREATE TABLE IF NOT EXISTS settings (
                    key TEXT PRIMARY KEY,
                    value TEXT
                )
            ");
            
        } catch (PDOException $e) {
            die("Veritabanı hatası: " . $e->getMessage());
        }
    }
    
    /**
     * WHOIS sorgusu yapar
     */
    public function whoisQuery($domain, $tld) {
        if (!isset($this->config['tlds'][$tld])) {
            return false;
        }
        
        $whoisServer = $this->config['tlds'][$tld]['whois_server'];
        $fullDomain = $domain . '.' . $tld;
        
        try {
            $sock = fsockopen($whoisServer, 43, $errno, $errstr, $this->config['timeout']);
            if (!$sock) {
                $this->log("WHOIS bağlantı hatası: $errstr ($errno) - $fullDomain");
                return false;
            }
            
            fwrite($sock, $fullDomain . "\r\n");
            $response = '';
            while (!feof($sock)) {
                $response .= fgets($sock, 128);
            }
            fclose($sock);
            
            return $this->parseWhoisResponse($response, $fullDomain);
            
        } catch (Exception $e) {
            $this->log("WHOIS sorgu hatası: " . $e->getMessage() . " - $fullDomain");
            return false;
        }
    }
    
    /**
     * WHOIS yanıtını parse eder
     */
    private function parseWhoisResponse($response, $domain) {
        $result = [
            'domain' => $domain,
            'available' => false,
            'expiry_date' => null,
            'registrar' => null,
            'status' => 'unknown'
        ];
        
        $response = strtolower($response);
        
        // Domain müsait mi kontrol et
        $unavailablePatterns = [
            'no match', 'not found', 'no entries found', 'no data found',
            'domain status: available', 'not exist', 'no matching record',
            'bulunamadı', 'kayıt bulunamadı'
        ];
        
        $availablePatterns = [
            'available', 'not registered', 'no match found', 'free'
        ];
        
        foreach ($unavailablePatterns as $pattern) {
            if (strpos($response, $pattern) !== false) {
                $result['available'] = true;
                $result['status'] = 'available';
                break;
            }
        }
        
        if (!$result['available']) {
            // Expiry date bul
            $expiryPatterns = [
                '/expiry date:\s*(.+)/i',
                '/expires on:\s*(.+)/i',
                '/expiration date:\s*(.+)/i',
                '/expires:\s*(.+)/i',
                '/son kullanma tarihi:\s*(.+)/i'
            ];
            
            foreach ($expiryPatterns as $pattern) {
                if (preg_match($pattern, $response, $matches)) {
                    $result['expiry_date'] = trim($matches[1]);
                    break;
                }
            }
            
            // Registrar bul
            $registrarPatterns = [
                '/registrar:\s*(.+)/i',
                '/kayıt kuruluşu:\s*(.+)/i'
            ];
            
            foreach ($registrarPatterns as $pattern) {
                if (preg_match($pattern, $response, $matches)) {
                    $result['registrar'] = trim($matches[1]);
                    break;
                }
            }
            
            $result['status'] = 'registered';
        }
        
        return $result;
    }
    
    /**
     * Premium domain değerini hesaplar
     */
    private function calculateDomainValue($domain, $tld) {
        $baseValue = $this->config['tlds'][$tld]['price'] ?? 10;
        $multiplier = 1;
        
        // Domain uzunluğu
        $length = strlen($domain);
        if ($length <= 3) $multiplier *= 10;
        elseif ($length <= 4) $multiplier *= 5;
        elseif ($length <= 5) $multiplier *= 3;
        elseif ($length <= 6) $multiplier *= 2;
        
        // Premium kelime kontrolü
        foreach ($this->config['premium_keywords'] as $keyword) {
            if (strpos($domain, $keyword) !== false) {
                $multiplier *= 2;
                break;
            }
        }
        
        // Sadece harf ve sayı içeriyor mu
        if (preg_match('/^[a-z0-9]+$/', $domain)) {
            $multiplier *= 1.5;
        }
        
        // Tekrar eden karakterler var mı
        if (strlen($domain) == count(array_unique(str_split($domain)))) {
            $multiplier *= 1.2;
        }
        
        return intval($baseValue * $multiplier);
    }
    
    /**
     * Domain listesini kontrol eder
     */
    public function checkDomains($domains = null) {
        if ($domains === null) {
            $domains = $this->generateDomainList();
        }
        
        $results = [];
        $checked = 0;
        $available = 0;
        
        foreach ($domains as $domainData) {
            $domain = $domainData['domain'];
            $tld = $domainData['tld'];
            
            $this->log("Kontrol ediliyor: $domain.$tld");
            
            $whoisResult = $this->whoisQuery($domain, $tld);
            
            if ($whoisResult !== false) {
                $checked++;
                
                if ($whoisResult['available']) {
                    $available++;
                    $estimatedValue = $this->calculateDomainValue($domainData['domain'], $domainData['tld']);
                    
                    // Veritabanına kaydet
                    $this->saveDomain($domainData['domain'], $domainData['tld'], $whoisResult, $estimatedValue);
                    
                    $results[] = [
                        'domain' => "{$domainData['domain']}.{$domainData['tld']}",
                        'status' => 'available',
                        'estimated_value' => $estimatedValue,
                        'tld_price' => $this->config['tlds'][$domainData['tld']]['price']
                    ];
                    
                    $this->log("MÜSAİT DOMAIN BULUNDU: {$domainData['domain']}.{$domainData['tld']} (Tahmini değer: $estimatedValue USD)");
                } else {
                    // Kayıtlı domain bilgilerini güncelle
                    $this->updateDomainInfo($domainData['domain'], $domainData['tld'], $whoisResult);
                }
            }
            
            // Rate limiting
            usleep(500000); // 0.5 saniye bekle
        }
        
        $this->log("Kontrol tamamlandı. $checked domain kontrol edildi, $available müsait domain bulundu.");
        
        return $results;
    }
    
    /**
     * Domain bilgilerini veritabanına kaydeder
     */
    private function saveDomain($domain, $tld, $whoisData, $estimatedValue) {
        try {
            $stmt = $this->db->prepare("
                INSERT OR REPLACE INTO domains 
                (domain_name, tld, status, expiry_date, registrar, estimated_value, check_count, last_checked) 
                VALUES (?, ?, ?, ?, ?, ?, 
                    COALESCE((SELECT check_count FROM domains WHERE domain_name = ? AND tld = ?), 0) + 1, 
                    CURRENT_TIMESTAMP)
            ");
            
            $stmt->execute([
                $domain, $tld, $whoisData['status'], $whoisData['expiry_date'],
                $whoisData['registrar'], $estimatedValue, $domain, $tld
            ]);
            
            // Alert ekle
            if ($whoisData['available']) {
                $this->addAlert($domain . '.' . $tld, 'available', "Domain müsait! Tahmini değer: $estimatedValue USD");
            }
            
        } catch (PDOException $e) {
            $this->log("Veritabanı kayıt hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Domain bilgilerini günceller
     */
    private function updateDomainInfo($domain, $tld, $whoisData) {
        try {
            $stmt = $this->db->prepare("
                UPDATE domains SET 
                status = ?, expiry_date = ?, registrar = ?, 
                check_count = check_count + 1, last_checked = CURRENT_TIMESTAMP
                WHERE domain_name = ? AND tld = ?
            ");
            
            $stmt->execute([
                $whoisData['status'], $whoisData['expiry_date'], 
                $whoisData['registrar'], $domain, $tld
            ]);
            
        } catch (PDOException $e) {
            $this->log("Domain güncelleme hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Alert ekler
     */
    private function addAlert($domain, $type, $message) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO alerts (domain_id, alert_type, message) 
                SELECT id, ?, ? FROM domains WHERE domain_name || '.' || tld = ?
            ");
            $stmt->execute([$type, $message, $domain]);
        } catch (PDOException $e) {
            $this->log("Alert ekleme hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Domain listesi oluşturur
     */
    private function generateDomainList() {
        $domains = [];
        $keywords = $this->config['premium_keywords'];
        $tlds = array_keys($this->config['tlds']);
        
        // Tek kelimeli domainler
        foreach ($keywords as $keyword) {
            foreach ($tlds as $tld) {
                $domains[] = ['domain' => $keyword, 'tld' => $tld];
            }
        }
        
        // İki kelimeli kombinasyonlar (popüler olanlar)
        $popularCombinations = [
            'my', 'get', 'buy', 'sell', 'best', 'top', 'new', 'old', 'big', 'small',
            'quick', 'fast', 'easy', 'simple', 'smart', 'super', 'ultra', 'mega',
            'pro', 'plus', 'max', 'mini', 'micro', 'nano'
        ];
        
        foreach ($keywords as $keyword) {
            foreach ($popularCombinations as $prefix) {
                foreach ($tlds as $tld) {
                    if (strlen($prefix . $keyword) <= 15) { // Çok uzun domainleri filtrele
                        $domains[] = ['domain' => $prefix . $keyword, 'tld' => $tld];
                        $domains[] = ['domain' => $keyword . $prefix, 'tld' => $tld];
                    }
                }
            }
        }
        
        // Sayılı domainler
        for ($i = 1; $i <= 999; $i++) {
            foreach (['app', 'web', 'shop', 'site', 'tech'] as $suffix) {
                foreach ($tlds as $tld) {
                    $domains[] = ['domain' => $suffix . $i, 'tld' => $tld];
                    $domains[] = ['domain' => $i . $suffix, 'tld' => $tld];
                }
            }
        }
        
        return array_unique($domains, SORT_REGULAR);
    }
    
    /**
     * Müsait domainleri getirir
     */
    public function getAvailableDomains($limit = 100, $minValue = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT domain_name, tld, estimated_value, last_checked, check_count
                FROM domains 
                WHERE status = 'available' AND estimated_value >= ? 
                ORDER BY estimated_value DESC, last_checked DESC 
                LIMIT ?
            ");
            $stmt->execute([$minValue, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Müsait domain listesi hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Expiry tarihi yaklaşan domainleri getirir
     */
    public function getExpiringDomains($days = 30) {
        try {
            $stmt = $this->db->prepare("
                SELECT domain_name, tld, expiry_date, registrar, estimated_value
                FROM domains 
                WHERE status = 'registered' 
                AND expiry_date IS NOT NULL 
                AND datetime(expiry_date) BETWEEN datetime('now') AND datetime('now', '+{$days} days')
                ORDER BY datetime(expiry_date) ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->log("Expiry domain listesi hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * İstatistikleri getirir
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Toplam domain sayısı
            $stmt = $this->db->query("SELECT COUNT(*) as total FROM domains");
            $stats['total_domains'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Müsait domain sayısı
            $stmt = $this->db->query("SELECT COUNT(*) as available FROM domains WHERE status = 'available'");
            $stats['available_domains'] = $stmt->fetch(PDO::FETCH_ASSOC)['available'];
            
            // Kayıtlı domain sayısı
            $stmt = $this->db->query("SELECT COUNT(*) as registered FROM domains WHERE status = 'registered'");
            $stats['registered_domains'] = $stmt->fetch(PDO::FETCH_ASSOC)['registered'];
            
            // TLD dağılımı
            $stmt = $this->db->query("
                SELECT tld, COUNT(*) as count, 
                       SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_count
                FROM domains 
                GROUP BY tld 
                ORDER BY count DESC
            ");
            $stats['tld_distribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // En değerli müsait domainler
            $stmt = $this->db->query("
                SELECT domain_name, tld, estimated_value 
                FROM domains 
                WHERE status = 'available' 
                ORDER BY estimated_value DESC 
                LIMIT 10
            ");
            $stats['most_valuable'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $stats;
            
        } catch (PDOException $e) {
            $this->log("İstatistik hatası: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Log kaydı tutar
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Console'a da yazdır
        echo $logMessage;
    }
    
    /**
     * Cron job için otomatik kontrol
     */
    public function runAutoCheck() {
        $this->log("Otomatik kontrol başlatılıyor...");
        
        // Rastgele bir domain listesi seç
        $allDomains = $this->generateDomainList();
        if (empty($allDomains)) {
            $this->log("Hiç domain listesi oluşturulamadı!");
            return [];
        }
        
        $randomCount = min(100, count($allDomains));
        $randomKeys = array_rand($allDomains, $randomCount);
        if (!is_array($randomKeys)) {
            $randomKeys = [$randomKeys];
        }
        
        $randomDomains = [];
        foreach ($randomKeys as $key) {
            $randomDomains[] = $allDomains[$key];
        }
        
        $results = $this->checkDomains($randomDomains);
        
        if (!empty($results)) {
            $this->log(count($results) . " yeni müsait domain bulundu!");
            
            // E-posta bildirimi gönder (isteğe bağlı)
            // $this->sendEmailNotification($results);
        }
        
        return $results;
    }
}

// CLI kullanımı
if (php_sapi_name() === 'cli') {
    $hunter = new DomainHunter();
    
    if ($argc > 1) {
        switch ($argv[1]) {
            case 'check':
                $hunter->runAutoCheck();
                break;
                
            case 'available':
                $limit = isset($argv[2]) ? intval($argv[2]) : 50;
                $minValue = isset($argv[3]) ? intval($argv[3]) : 0;
                $domains = $hunter->getAvailableDomains($limit, $minValue);
                
                echo "\n=== MÜSAİT DOMAİNLER ===\n";
                foreach ($domains as $domain) {
                    echo sprintf("%-20s %s USD (Kontrol: %dx)\n", 
                        $domain['domain_name'] . '.' . $domain['tld'],
                        $domain['estimated_value'],
                        $domain['check_count']
                    );
                }
                break;
                
            case 'expiring':
                $days = isset($argv[2]) ? intval($argv[2]) : 30;
                $domains = $hunter->getExpiringDomains($days);
                
                echo "\n=== SÜRESİ DOLACAK DOMAİNLER ($days gün içinde) ===\n";
                foreach ($domains as $domain) {
                    echo sprintf("%-20s %s (%s)\n", 
                        $domain['domain_name'] . '.' . $domain['tld'],
                        $domain['expiry_date'],
                        $domain['registrar']
                    );
                }
                break;
                
            case 'stats':
                $stats = $hunter->getStats();
                echo "\n=== İSTATİSTİKLER ===\n";
                echo "Toplam Domain: " . $stats['total_domains'] . "\n";
                echo "Müsait Domain: " . $stats['available_domains'] . "\n";
                echo "Kayıtlı Domain: " . $stats['registered_domains'] . "\n";
                
                echo "\n=== TLD DAĞILIMI ===\n";
                foreach ($stats['tld_distribution'] as $tld) {
                    echo sprintf("%-10s: %d total, %d available\n", 
                        $tld['tld'], $tld['count'], $tld['available_count']);
                }
                
                if (!empty($stats['most_valuable'])) {
                    echo "\n=== EN DEĞERLİ MÜSAİT DOMAİNLER ===\n";
                    foreach ($stats['most_valuable'] as $domain) {
                        echo sprintf("%-20s %d USD\n", 
                            $domain['domain_name'] . '.' . $domain['tld'],
                            $domain['estimated_value']
                        );
                    }
                }
                break;
                
            default:
                echo "Kullanım:\n";
                echo "php domain_hunter.php check                    # Otomatik kontrol\n";
                echo "php domain_hunter.php available [limit] [min]  # Müsait domainleri listele\n";
                echo "php domain_hunter.php expiring [days]          # Süresi dolacak domainleri listele\n";
                echo "php domain_hunter.php stats                    # İstatistikleri göster\n";
        }
    } else {
        echo "Domain Hunter v1.0\n";
        echo "Kullanım: php domain_hunter.php [command]\n";
        echo "Komutlar: check, available, expiring, stats\n";
    }
}
?>
<?php
/**
 * Domain Checker Kurulum Scripti
 * Bu dosyayı çalıştırarak sistemi kurabilirsiniz
 */

// Hata raporlamayı aç
error_reporting(E_ALL);
ini_set('display_errors', 1);

class Installer {
    private $config;
    private $db;
    
    public function __construct() {
        $this->config = include 'config.php';
    }
    
    public function install() {
        echo "🚀 Domain Checker Kurulum Başlatılıyor...\n\n";
        
        try {
            // 1. Veritabanı bağlantısını test et
            $this->testDatabaseConnection();
            echo "✅ Veritabanı bağlantısı başarılı\n";
            
            // 2. Veritabanını oluştur
            $this->createDatabase();
            echo "✅ Veritabanı oluşturuldu\n";
            
            // 3. Tabloları oluştur
            $this->createTables();
            echo "✅ Tablolar oluşturuldu\n";
            
            // 4. Örnek verileri ekle
            $this->insertSampleData();
            echo "✅ Örnek veriler eklendi\n";
            
            // 5. Dizinleri oluştur
            $this->createDirectories();
            echo "✅ Dizinler oluşturuldu\n";
            
            // 6. Cron job ayarlarını göster
            $this->showCronInstructions();
            
            // 7. Test çalıştır
            $this->runTest();
            
            echo "\n🎉 Kurulum başarıyla tamamlandı!\n";
            echo "Web arayüzüne erişmek için: http://your-domain/domain_checker.php\n";
            
        } catch (Exception $e) {
            echo "❌ Kurulum hatası: " . $e->getMessage() . "\n";
            exit(1);
        }
    }
    
    private function testDatabaseConnection() {
        try {
            $this->db = new PDO(
                "mysql:host={$this->config['db']['host']};charset=utf8",
                $this->config['db']['user'],
                $this->config['db']['pass']
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            throw new Exception("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    private function createDatabase() {
        $sql = "CREATE DATABASE IF NOT EXISTS `{$this->config['db']['name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $this->db->exec($sql);
        $this->db->exec("USE `{$this->config['db']['name']}`");
    }
    
    private function createTables() {
        $sqlFile = 'database.sql';
        if (!file_exists($sqlFile)) {
            throw new Exception("database.sql dosyası bulunamadı");
        }
        
        $sql = file_get_contents($sqlFile);
        $statements = explode(';', $sql);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                try {
                    $this->db->exec($statement);
                } catch(PDOException $e) {
                    // Bazı hataları görmezden gel (tablo zaten var gibi)
                    if (strpos($e->getMessage(), 'already exists') === false) {
                        echo "⚠️  SQL Uyarısı: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
    private function insertSampleData() {
        // Premium domain örnekleri
        $premiumDomains = [
            ['business.com', 'keyword', 'business', 50000.00, 'Yüksek değerli iş kelimesi'],
            ['money.net', 'keyword', 'money', 25000.00, 'Finans kelimesi'],
            ['tech.org', 'keyword', 'tech', 30000.00, 'Teknoloji kelimesi'],
            ['abc.com', 'short', null, 100000.00, '3 harfli kısa domain'],
            ['123.com', 'numeric', null, 75000.00, 'Sayısal domain'],
            ['crypto.io', 'keyword', 'crypto', 40000.00, 'Kripto para kelimesi'],
            ['is.com.tr', 'short', null, 50000.00, 'Türkçe kısa domain'],
            ['ticaret.com.tr', 'keyword', 'ticaret', 30000.00, 'Türkçe ticaret kelimesi']
        ];
        
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO premium_domains (domain, reason, keyword, value_estimate, notes) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        foreach ($premiumDomains as $domain) {
            $stmt->execute($domain);
        }
    }
    
    private function createDirectories() {
        $directories = [
            dirname($this->config['logging']['path']),
            $this->config['cache']['path']
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
    }
    
    private function showCronInstructions() {
        echo "\n📅 Cron Job Ayarları:\n";
        echo "Otomatik domain kontrolü için aşağıdaki satırı crontab'ınıza ekleyin:\n\n";
        echo "0 * * * * /usr/bin/php " . realpath('cron_job.php') . "\n\n";
        echo "Crontab düzenlemek için: crontab -e\n";
        echo "Mevcut crontab'ı görmek için: crontab -l\n\n";
    }
    
    private function runTest() {
        echo "\n🧪 Test çalıştırılıyor...\n";
        
        try {
            require_once 'domain_checker.php';
            $checker = new DomainChecker();
            
            // Test domain kontrolü
            $testDomain = 'example';
            $results = $checker->checkDomain($testDomain);
            
            if (!empty($results)) {
                echo "✅ Domain kontrol testi başarılı\n";
                echo "Test sonucu: " . count($results) . " uzantı kontrol edildi\n";
            } else {
                echo "⚠️  Domain kontrol testi uyarı: Sonuç bulunamadı\n";
            }
            
        } catch (Exception $e) {
            echo "⚠️  Test uyarısı: " . $e->getMessage() . "\n";
        }
    }
}

// Kurulum scripti çalıştır
if (php_sapi_name() === 'cli') {
    $installer = new Installer();
    $installer->install();
} else {
    echo "Bu script sadece komut satırından çalıştırılabilir.\n";
    echo "Kullanım: php install.php\n";
}
?>
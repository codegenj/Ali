<?php
/**
 * Cron Job Kurulum ve Yönetim Scripti
 * Domain Hunter için otomatik tarama ayarları
 */

require_once 'domain_hunter.php';

class CronManager {
    
    private $cronFile = '/tmp/domain_hunter_cron.txt';
    private $logFile = 'cron.log';
    
    /**
     * Cron job'ı kurar
     */
    public function setupCron($interval = 60) { // dakika cinsinden
        $scriptPath = __DIR__ . '/domain_hunter.php';
        $logPath = __DIR__ . '/cron.log';
        
        // Cron entry oluştur
        $cronEntry = "*/{$interval} * * * * /usr/bin/php {$scriptPath} check >> {$logPath} 2>&1\n";
        
        // Mevcut cron jobs'ları al
        $currentCron = shell_exec('crontab -l 2>/dev/null') ?: '';
        
        // Domain Hunter cron'u zaten var mı kontrol et
        if (strpos($currentCron, 'domain_hunter.php') !== false) {
            echo "Domain Hunter cron job'ı zaten kurulu.\n";
            return false;
        }
        
        // Yeni cron entry'yi ekle
        $newCron = $currentCron . $cronEntry;
        
        // Geçici dosyaya yaz
        file_put_contents($this->cronFile, $newCron);
        
        // Crontab'ı güncelle
        $result = shell_exec("crontab {$this->cronFile} 2>&1");
        
        // Geçici dosyayı sil
        unlink($this->cronFile);
        
        if ($result === null) {
            echo "✅ Cron job başarıyla kuruldu! Her {$interval} dakikada bir domain taraması yapılacak.\n";
            $this->log("Cron job kuruldu - interval: {$interval} dakika");
            return true;
        } else {
            echo "❌ Cron job kurulum hatası: {$result}\n";
            return false;
        }
    }
    
    /**
     * Cron job'ı kaldırır
     */
    public function removeCron() {
        $currentCron = shell_exec('crontab -l 2>/dev/null') ?: '';
        
        if (strpos($currentCron, 'domain_hunter.php') === false) {
            echo "Domain Hunter cron job'ı bulunamadı.\n";
            return false;
        }
        
        // Domain Hunter cron'unu kaldır
        $lines = explode("\n", $currentCron);
        $newLines = array_filter($lines, function($line) {
            return strpos($line, 'domain_hunter.php') === false;
        });
        
        $newCron = implode("\n", $newLines);
        
        // Geçici dosyaya yaz
        file_put_contents($this->cronFile, $newCron);
        
        // Crontab'ı güncelle
        $result = shell_exec("crontab {$this->cronFile} 2>&1");
        
        // Geçici dosyayı sil
        unlink($this->cronFile);
        
        if ($result === null) {
            echo "✅ Cron job başarıyla kaldırıldı.\n";
            $this->log("Cron job kaldırıldı");
            return true;
        } else {
            echo "❌ Cron job kaldırma hatası: {$result}\n";
            return false;
        }
    }
    
    /**
     * Mevcut cron job'ları listeler
     */
    public function listCron() {
        $currentCron = shell_exec('crontab -l 2>/dev/null') ?: '';
        
        if (empty(trim($currentCron))) {
            echo "Hiç cron job bulunamadı.\n";
            return;
        }
        
        echo "=== MEVCUT CRON JOBS ===\n";
        echo $currentCron . "\n";
        
        // Domain Hunter cron'u var mı kontrol et
        if (strpos($currentCron, 'domain_hunter.php') !== false) {
            echo "✅ Domain Hunter cron job'ı aktif.\n";
        } else {
            echo "❌ Domain Hunter cron job'ı bulunamadı.\n";
        }
    }
    
    /**
     * Test çalıştırması yapar
     */
    public function testRun() {
        echo "Domain Hunter test çalıştırması başlatılıyor...\n";
        
        $hunter = new DomainHunter();
        $results = $hunter->runAutoCheck();
        
        echo "✅ Test tamamlandı. " . count($results) . " müsait domain bulundu.\n";
        
        if (!empty($results)) {
            echo "\n=== BULUNAN MÜSAİT DOMAİNLER ===\n";
            foreach ($results as $result) {
                echo sprintf("%-20s $%d\n", $result['domain'], $result['estimated_value']);
            }
        }
    }
    
    /**
     * Log kaydı tutar
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Sistem gereksinimlerini kontrol eder
     */
    public function checkRequirements() {
        echo "=== SİSTEM GEREKSİNİMLERİ KONTROLÜ ===\n";
        
        // PHP version
        $phpVersion = PHP_VERSION;
        echo "PHP Version: {$phpVersion}";
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo " ✅\n";
        } else {
            echo " ❌ (Minimum PHP 7.4 gerekli)\n";
        }
        
        // SQLite extension
        echo "SQLite Extension: ";
        if (extension_loaded('sqlite3')) {
            echo "✅\n";
        } else {
            echo "❌ (SQLite3 extension gerekli)\n";
        }
        
        // PDO extension
        echo "PDO Extension: ";
        if (extension_loaded('pdo')) {
            echo "✅\n";
        } else {
            echo "❌ (PDO extension gerekli)\n";
        }
        
        // Crontab komutu
        echo "Crontab Command: ";
        $crontabCheck = shell_exec('which crontab 2>/dev/null');
        if (!empty($crontabCheck)) {
            echo "✅\n";
        } else {
            echo "❌ (crontab komutu bulunamadı)\n";
        }
        
        // Yazma izinleri
        echo "Write Permissions: ";
        if (is_writable(__DIR__)) {
            echo "✅\n";
        } else {
            echo "❌ (Dizin yazma izni yok)\n";
        }
        
        // Network bağlantısı
        echo "Network Connection: ";
        $connection = @fsockopen('google.com', 80, $errno, $errstr, 5);
        if ($connection) {
            fclose($connection);
            echo "✅\n";
        } else {
            echo "❌ (İnternet bağlantısı yok)\n";
        }
        
        echo "\n";
    }
}

// CLI kullanımı
if (php_sapi_name() === 'cli') {
    $manager = new CronManager();
    
    if ($argc > 1) {
        switch ($argv[1]) {
            case 'setup':
                $interval = isset($argv[2]) ? intval($argv[2]) : 60;
                $manager->setupCron($interval);
                break;
                
            case 'remove':
                $manager->removeCron();
                break;
                
            case 'list':
                $manager->listCron();
                break;
                
            case 'test':
                $manager->testRun();
                break;
                
            case 'check':
                $manager->checkRequirements();
                break;
                
            default:
                echo "Kullanım:\n";
                echo "php cron_setup.php setup [dakika]  # Cron job'ı kur (varsayılan: 60 dakika)\n";
                echo "php cron_setup.php remove          # Cron job'ı kaldır\n";
                echo "php cron_setup.php list            # Mevcut cron job'ları listele\n";
                echo "php cron_setup.php test            # Test çalıştırması yap\n";
                echo "php cron_setup.php check           # Sistem gereksinimlerini kontrol et\n";
        }
    } else {
        echo "Domain Hunter Cron Manager v1.0\n";
        echo "Kullanım: php cron_setup.php [command]\n";
        echo "Komutlar: setup, remove, list, test, check\n\n";
        
        // Otomatik gereksinim kontrolü
        $manager->checkRequirements();
    }
}
?>
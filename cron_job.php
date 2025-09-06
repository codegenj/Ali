<?php
/**
 * Domain Checker Cron Job
 * Bu dosya belirli aralıklarla çalıştırılarak domainleri otomatik kontrol eder
 * 
 * Kullanım:
 * php cron_job.php
 * 
 * Crontab örneği (her saat başı çalıştır):
 * 0 * * * * /usr/bin/php /path/to/cron_job.php
 */

require_once 'domain_checker.php';

class CronJob {
    private $checker;
    private $config;
    private $logFile;
    
    public function __construct() {
        $this->checker = new DomainChecker();
        $this->config = include 'config.php';
        $this->logFile = $this->config['logging']['path'] ?? '/var/log/domain_checker_cron.log';
        
        // Log dizinini oluştur
        $logDir = dirname($this->logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }
    
    /**
     * Ana cron job fonksiyonu
     */
    public function run() {
        $startTime = microtime(true);
        $this->log("Cron job başlatıldı: " . date('Y-m-d H:i:s'));
        
        try {
            // Cron log kaydı oluştur
            $logId = $this->createCronLog('domain_checker', 'started');
            
            // Takip edilen domainleri al
            $watchedDomains = $this->getWatchedDomains();
            
            // Premium domainleri al
            $premiumDomains = $this->getPremiumDomains();
            
            // Tüm domainleri birleştir
            $allDomains = array_unique(array_merge($watchedDomains, $premiumDomains));
            
            $checkedCount = 0;
            $errorCount = 0;
            
            foreach ($allDomains as $domain) {
                try {
                    $this->checkAndUpdateDomain($domain);
                    $checkedCount++;
                    
                    // Rate limiting için kısa bekleme
                    usleep(100000); // 0.1 saniye
                    
                } catch (Exception $e) {
                    $errorCount++;
                    $this->log("Domain kontrol hatası ({$domain}): " . $e->getMessage(), 'error');
                }
                
                // Maksimum domain sayısı kontrolü
                if ($checkedCount >= $this->config['cron']['max_domains_per_run']) {
                    $this->log("Maksimum domain sayısına ulaşıldı: " . $checkedCount);
                    break;
                }
            }
            
            // Cron log güncelle
            $executionTime = microtime(true) - $startTime;
            $this->updateCronLog($logId, 'completed', $checkedCount, $errorCount, $executionTime);
            
            $this->log("Cron job tamamlandı. Kontrol edilen: {$checkedCount}, Hata: {$errorCount}, Süre: " . round($executionTime, 2) . "s");
            
            // Bildirim gönder
            $this->sendNotifications();
            
        } catch (Exception $e) {
            $this->log("Cron job hatası: " . $e->getMessage(), 'error');
            
            // Hata log kaydı
            if (isset($logId)) {
                $this->updateCronLog($logId, 'failed', 0, 1, microtime(true) - $startTime, $e->getMessage());
            }
        }
    }
    
    /**
     * Takip edilen domainleri al
     */
    private function getWatchedDomains() {
        $stmt = $this->checker->db->prepare("
            SELECT DISTINCT domain FROM watchlist 
            WHERE notification_enabled = 1
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Premium domainleri al
     */
    private function getPremiumDomains() {
        $stmt = $this->checker->db->prepare("
            SELECT domain FROM premium_domains 
            WHERE is_active = 1
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Domain kontrol et ve güncelle
     */
    private function checkAndUpdateDomain($domain) {
        $domainName = explode('.', $domain)[0];
        $results = $this->checker->checkDomain($domainName);
        
        foreach ($results as $result) {
            $this->checker->saveResults([$result]);
            
            // Status değişikliği kontrolü
            $this->checkStatusChange($result);
        }
    }
    
    /**
     * Status değişikliği kontrolü
     */
    private function checkStatusChange($result) {
        $stmt = $this->checker->db->prepare("
            SELECT status FROM domain_checks 
            WHERE domain = ? AND last_checked < ?
        ");
        $stmt->execute([$result['domain'], $result['last_checked']]);
        $oldStatus = $stmt->fetchColumn();
        
        if ($oldStatus && $oldStatus !== $result['status']) {
            $this->log("Domain status değişti: {$result['domain']} ({$oldStatus} -> {$result['status']})");
            
            // Bildirim oluştur
            $this->createNotification(
                $result['domain'],
                'status_change',
                "Domain status değişti: {$oldStatus} -> {$result['status']}"
            );
        }
    }
    
    /**
     * Bildirim gönder
     */
    private function sendNotifications() {
        // Düşen domainler için bildirim
        $droppedDomains = $this->checker->getDroppedDomains();
        foreach ($droppedDomains as $domain) {
            $this->createNotification(
                $domain['domain'],
                'dropped',
                "Premium domain düştü: {$domain['domain']}"
            );
        }
        
        // Yakında düşecek domainler için bildirim
        $expiringDomains = $this->checker->getExpiringDomains(7); // 7 gün içinde
        foreach ($expiringDomains as $domain) {
            $this->createNotification(
                $domain['domain'],
                'expiring',
                "Domain yakında düşecek: {$domain['domain']} ({$domain['drop_date']})"
            );
        }
        
        // E-posta bildirimleri gönder
        $this->sendEmailNotifications();
    }
    
    /**
     * Bildirim oluştur
     */
    private function createNotification($domain, $type, $message) {
        $stmt = $this->checker->db->prepare("
            INSERT INTO notifications (domain, notification_type, message, status) 
            VALUES (?, ?, ?, 'pending')
        ");
        $stmt->execute([$domain, $type, $message]);
    }
    
    /**
     * E-posta bildirimleri gönder
     */
    private function sendEmailNotifications() {
        if (!$this->config['notifications']['email']['enabled']) {
            return;
        }
        
        $stmt = $this->checker->db->prepare("
            SELECT * FROM notifications 
            WHERE status = 'pending' 
            ORDER BY sent_at ASC 
            LIMIT 10
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notifications)) {
            return;
        }
        
        $subject = "Domain Checker Bildirimleri - " . date('Y-m-d H:i:s');
        $body = "Aşağıdaki domainlerle ilgili güncellemeler:\n\n";
        
        foreach ($notifications as $notification) {
            $body .= "- {$notification['domain']}: {$notification['message']}\n";
        }
        
        $this->sendEmail($subject, $body);
        
        // Bildirimleri işaretle
        $ids = array_column($notifications, 'id');
        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $stmt = $this->checker->db->prepare("
            UPDATE notifications 
            SET status = 'sent', sent_at = NOW() 
            WHERE id IN ($placeholders)
        ");
        $stmt->execute($ids);
    }
    
    /**
     * E-posta gönder
     */
    private function sendEmail($subject, $body) {
        $config = $this->config['notifications']['email'];
        
        $headers = [
            'From: ' . $config['from_email'],
            'Reply-To: ' . $config['from_email'],
            'Content-Type: text/plain; charset=UTF-8'
        ];
        
        $success = mail(
            $config['to_email'],
            $subject,
            $body,
            implode("\r\n", $headers)
        );
        
        if (!$success) {
            $this->log("E-posta gönderilemedi: {$subject}", 'error');
        }
    }
    
    /**
     * Cron log oluştur
     */
    private function createCronLog($jobName, $status) {
        $stmt = $this->checker->db->prepare("
            INSERT INTO cron_logs (job_name, status, started_at) 
            VALUES (?, ?, NOW())
        ");
        $stmt->execute([$jobName, $status]);
        return $this->checker->db->lastInsertId();
    }
    
    /**
     * Cron log güncelle
     */
    private function updateCronLog($logId, $status, $domainsChecked, $errorsCount, $executionTime, $errorMessage = null) {
        $stmt = $this->checker->db->prepare("
            UPDATE cron_logs 
            SET status = ?, domains_checked = ?, errors_count = ?, 
                execution_time = ?, error_message = ?, completed_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$status, $domainsChecked, $errorsCount, $executionTime, $errorMessage, $logId]);
    }
    
    /**
     * Log yaz
     */
    private function log($message, $level = 'info') {
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
        
        file_put_contents($this->logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Console'a da yazdır
        echo $logMessage;
    }
}

// Script doğrudan çalıştırılıyorsa cron job'ı başlat
if (php_sapi_name() === 'cli') {
    $cron = new CronJob();
    $cron->run();
}
?>
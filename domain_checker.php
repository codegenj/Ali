<?php
/**
 * Premium Domain Checker & Drop Catcher
 * Domain durumunu kontrol eder ve düşen domainleri takip eder
 */

class DomainChecker {
    private $config;
    private $db;
    
    public function __construct() {
        $this->loadConfig();
        $this->initDatabase();
    }
    
    private function loadConfig() {
        $this->config = include 'config.php';
    }
    
    private function initDatabase() {
        try {
            $this->db = new PDO(
                "mysql:host={$this->config['db']['host']};dbname={$this->config['db']['name']};charset=utf8",
                $this->config['db']['user'],
                $this->config['db']['pass']
            );
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Veritabanı bağlantı hatası: " . $e->getMessage());
        }
    }
    
    /**
     * Domain durumunu kontrol et
     */
    public function checkDomain($domain) {
        $results = [];
        
        foreach ($this->config['extensions'] as $ext) {
            $fullDomain = $domain . '.' . $ext;
            $status = $this->getDomainStatus($fullDomain);
            
            $results[] = [
                'domain' => $fullDomain,
                'status' => $status['status'],
                'expiry_date' => $status['expiry_date'],
                'registrar' => $status['registrar'],
                'created_date' => $status['created_date'],
                'last_checked' => date('Y-m-d H:i:s'),
                'is_premium' => $this->isPremiumDomain($fullDomain),
                'drop_date' => $this->calculateDropDate($status['expiry_date'])
            ];
        }
        
        return $results;
    }
    
    /**
     * Domain durumunu WHOIS ile kontrol et
     */
    private function getDomainStatus($domain) {
        $whoisData = $this->getWhoisData($domain);
        
        if (empty($whoisData)) {
            return [
                'status' => 'available',
                'expiry_date' => null,
                'registrar' => null,
                'created_date' => null
            ];
        }
        
        return [
            'status' => 'registered',
            'expiry_date' => $this->extractExpiryDate($whoisData),
            'registrar' => $this->extractRegistrar($whoisData),
            'created_date' => $this->extractCreatedDate($whoisData)
        ];
    }
    
    /**
     * WHOIS verisi al
     */
    private function getWhoisData($domain) {
        $whoisServers = [
            'com' => 'whois.verisign-grs.com',
            'net' => 'whois.verisign-grs.com',
            'org' => 'whois.pir.org',
            'com.tr' => 'whois.nic.tr',
            'net.tr' => 'whois.nic.tr',
            'org.tr' => 'whois.nic.tr'
        ];
        
        $extension = $this->getExtension($domain);
        $server = $whoisServers[$extension] ?? 'whois.iana.org';
        
        $fp = @fsockopen($server, 43, $errno, $errstr, 10);
        if (!$fp) {
            return null;
        }
        
        fwrite($fp, $domain . "\r\n");
        $response = '';
        while (!feof($fp)) {
            $response .= fgets($fp, 128);
        }
        fclose($fp);
        
        return $response;
    }
    
    /**
     * Domain uzantısını al
     */
    private function getExtension($domain) {
        $parts = explode('.', $domain);
        if (count($parts) >= 3) {
            return $parts[count($parts) - 2] . '.' . end($parts);
        }
        return end($parts);
    }
    
    /**
     * Expiry date çıkar
     */
    private function extractExpiryDate($whoisData) {
        $patterns = [
            '/Registry Expiry Date:\s*(.+)/i',
            '/Expiry Date:\s*(.+)/i',
            '/Expires On:\s*(.+)/i',
            '/Expiration Date:\s*(.+)/i',
            '/Bitiş Tarihi:\s*(.+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $whoisData, $matches)) {
                $date = trim($matches[1]);
                $timestamp = strtotime($date);
                if ($timestamp) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Registrar bilgisini çıkar
     */
    private function extractRegistrar($whoisData) {
        $patterns = [
            '/Registrar:\s*(.+)/i',
            '/Registrar Name:\s*(.+)/i',
            '/Kayıt Kuruluşu:\s*(.+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $whoisData, $matches)) {
                return trim($matches[1]);
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Created date çıkar
     */
    private function extractCreatedDate($whoisData) {
        $patterns = [
            '/Creation Date:\s*(.+)/i',
            '/Created On:\s*(.+)/i',
            '/Registration Date:\s*(.+)/i',
            '/Kayıt Tarihi:\s*(.+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $whoisData, $matches)) {
                $date = trim($matches[1]);
                $timestamp = strtotime($date);
                if ($timestamp) {
                    return date('Y-m-d H:i:s', $timestamp);
                }
            }
        }
        
        return null;
    }
    
    /**
     * Premium domain kontrolü
     */
    private function isPremiumDomain($domain) {
        // Premium domain kriterleri
        $premiumKeywords = [
            'business', 'money', 'finance', 'tech', 'online', 'shop', 'store',
            'market', 'trade', 'invest', 'crypto', 'bitcoin', 'bank', 'loan',
            'insurance', 'real', 'estate', 'property', 'home', 'car', 'auto',
            'travel', 'hotel', 'book', 'movie', 'music', 'game', 'sport',
            'health', 'fitness', 'beauty', 'fashion', 'food', 'restaurant',
            'education', 'school', 'university', 'course', 'training'
        ];
        
        $domainName = strtolower(explode('.', $domain)[0]);
        
        // Kısa domain kontrolü (3 karakter ve altı)
        if (strlen($domainName) <= 3) {
            return true;
        }
        
        // Premium keyword kontrolü
        foreach ($premiumKeywords as $keyword) {
            if (strpos($domainName, $keyword) !== false) {
                return true;
            }
        }
        
        // Sayısal domain kontrolü
        if (preg_match('/^\d+$/', $domainName)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Drop date hesapla
     */
    private function calculateDropDate($expiryDate) {
        if (!$expiryDate) {
            return null;
        }
        
        $expiry = new DateTime($expiryDate);
        $gracePeriod = $this->config['grace_period_days'] ?? 30;
        $expiry->add(new DateInterval("P{$gracePeriod}D"));
        
        return $expiry->format('Y-m-d H:i:s');
    }
    
    /**
     * Sonuçları veritabanına kaydet
     */
    public function saveResults($results) {
        $stmt = $this->db->prepare("
            INSERT INTO domain_checks 
            (domain, status, expiry_date, registrar, created_date, last_checked, is_premium, drop_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            expiry_date = VALUES(expiry_date),
            registrar = VALUES(registrar),
            last_checked = VALUES(last_checked),
            is_premium = VALUES(is_premium),
            drop_date = VALUES(drop_date)
        ");
        
        foreach ($results as $result) {
            $stmt->execute([
                $result['domain'],
                $result['status'],
                $result['expiry_date'],
                $result['registrar'],
                $result['created_date'],
                $result['last_checked'],
                $result['is_premium'] ? 1 : 0,
                $result['drop_date']
            ]);
        }
    }
    
    /**
     * Düşen domainleri getir
     */
    public function getDroppedDomains() {
        $stmt = $this->db->prepare("
            SELECT * FROM domain_checks 
            WHERE status = 'available' 
            AND is_premium = 1 
            ORDER BY last_checked DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Yakında düşecek domainleri getir
     */
    public function getExpiringDomains($days = 30) {
        $stmt = $this->db->prepare("
            SELECT * FROM domain_checks 
            WHERE status = 'registered' 
            AND drop_date <= DATE_ADD(NOW(), INTERVAL ? DAY)
            AND drop_date > NOW()
            ORDER BY drop_date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Domain arama
     */
    public function searchDomains($keyword) {
        $stmt = $this->db->prepare("
            SELECT * FROM domain_checks 
            WHERE domain LIKE ? 
            ORDER BY is_premium DESC, last_checked DESC
        ");
        $stmt->execute(["%{$keyword}%"]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Web arayüzü
if (isset($_GET['action'])) {
    $checker = new DomainChecker();
    
    switch ($_GET['action']) {
        case 'check':
            if (isset($_POST['domain'])) {
                $results = $checker->checkDomain($_POST['domain']);
                $checker->saveResults($results);
                echo json_encode($results);
            }
            break;
            
        case 'dropped':
            $domains = $checker->getDroppedDomains();
            echo json_encode($domains);
            break;
            
        case 'expiring':
            $days = $_GET['days'] ?? 30;
            $domains = $checker->getExpiringDomains($days);
            echo json_encode($domains);
            break;
            
        case 'search':
            if (isset($_GET['keyword'])) {
                $domains = $checker->searchDomains($_GET['keyword']);
                echo json_encode($domains);
            }
            break;
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Domain Checker</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }
        
        .content {
            padding: 30px;
        }
        
        .search-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .search-form {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-form input {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .search-form input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .btn {
            padding: 12px 25px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        
        .tab {
            padding: 15px 25px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .tab.active {
            border-bottom-color: #667eea;
            color: #667eea;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .domain-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .domain-card {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }
        
        .domain-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .domain-card.premium {
            border-color: #ffd700;
            background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
        }
        
        .domain-card.available {
            border-color: #28a745;
        }
        
        .domain-card.registered {
            border-color: #dc3545;
        }
        
        .domain-name {
            font-size: 1.3em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }
        
        .domain-status {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .status-registered {
            background: #f8d7da;
            color: #721c24;
        }
        
        .premium-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: #ffd700;
            color: #333;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: bold;
        }
        
        .domain-info {
            font-size: 0.9em;
            color: #666;
            line-height: 1.5;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .tabs {
                flex-wrap: wrap;
            }
            
            .domain-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Premium Domain Checker</h1>
            <p>Düşen ve premium domainleri keşfedin</p>
        </div>
        
        <div class="content">
            <div class="search-section">
                <form class="search-form" id="searchForm">
                    <input type="text" id="domainInput" placeholder="Domain adı girin (örn: example)" required>
                    <button type="submit" class="btn">🔍 Kontrol Et</button>
                </form>
                
                <div class="tabs">
                    <div class="tab active" data-tab="results">Sonuçlar</div>
                    <div class="tab" data-tab="dropped">Düşen Domainler</div>
                    <div class="tab" data-tab="expiring">Yakında Düşecekler</div>
                </div>
            </div>
            
            <div id="results" class="tab-content active">
                <div class="loading">Domain kontrolü için yukarıdaki formu kullanın</div>
            </div>
            
            <div id="dropped" class="tab-content">
                <div class="loading">Düşen domainler yükleniyor...</div>
            </div>
            
            <div id="expiring" class="tab-content">
                <div class="loading">Yakında düşecek domainler yükleniyor...</div>
            </div>
        </div>
    </div>

    <script>
        // Tab switching
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', () => {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
                
                tab.classList.add('active');
                document.getElementById(tab.dataset.tab).classList.add('active');
                
                if (tab.dataset.tab === 'dropped') {
                    loadDroppedDomains();
                } else if (tab.dataset.tab === 'expiring') {
                    loadExpiringDomains();
                }
            });
        });
        
        // Search form
        document.getElementById('searchForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const domain = document.getElementById('domainInput').value.trim();
            if (!domain) return;
            
            const resultsDiv = document.getElementById('results');
            resultsDiv.innerHTML = '<div class="loading">Domain kontrol ediliyor...</div>';
            
            try {
                const formData = new FormData();
                formData.append('domain', domain);
                
                const response = await fetch('?action=check', {
                    method: 'POST',
                    body: formData
                });
                
                const results = await response.json();
                displayResults(results);
            } catch (error) {
                resultsDiv.innerHTML = '<div class="error">Hata: ' + error.message + '</div>';
            }
        });
        
        function displayResults(results) {
            const resultsDiv = document.getElementById('results');
            
            if (results.length === 0) {
                resultsDiv.innerHTML = '<div class="error">Sonuç bulunamadı</div>';
                return;
            }
            
            let html = '<div class="domain-grid">';
            
            results.forEach(result => {
                const statusClass = result.status === 'available' ? 'available' : 'registered';
                const statusText = result.status === 'available' ? 'Müsait' : 'Kayıtlı';
                
                html += `
                    <div class="domain-card ${statusClass}">
                        ${result.is_premium ? '<div class="premium-badge">PREMIUM</div>' : ''}
                        <div class="domain-name">${result.domain}</div>
                        <div class="domain-status status-${result.status}">${statusText}</div>
                        <div class="domain-info">
                            ${result.expiry_date ? `<strong>Bitiş:</strong> ${new Date(result.expiry_date).toLocaleDateString('tr-TR')}<br>` : ''}
                            ${result.drop_date ? `<strong>Düşme:</strong> ${new Date(result.drop_date).toLocaleDateString('tr-TR')}<br>` : ''}
                            ${result.registrar ? `<strong>Registrar:</strong> ${result.registrar}<br>` : ''}
                            <strong>Son Kontrol:</strong> ${new Date(result.last_checked).toLocaleString('tr-TR')}
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            resultsDiv.innerHTML = html;
        }
        
        async function loadDroppedDomains() {
            const droppedDiv = document.getElementById('dropped');
            droppedDiv.innerHTML = '<div class="loading">Düşen domainler yükleniyor...</div>';
            
            try {
                const response = await fetch('?action=dropped');
                const domains = await response.json();
                
                if (domains.length === 0) {
                    droppedDiv.innerHTML = '<div class="error">Düşen domain bulunamadı</div>';
                    return;
                }
                
                displayResults(domains);
            } catch (error) {
                droppedDiv.innerHTML = '<div class="error">Hata: ' + error.message + '</div>';
            }
        }
        
        async function loadExpiringDomains() {
            const expiringDiv = document.getElementById('expiring');
            expiringDiv.innerHTML = '<div class="loading">Yakında düşecek domainler yükleniyor...</div>';
            
            try {
                const response = await fetch('?action=expiring&days=30');
                const domains = await response.json();
                
                if (domains.length === 0) {
                    expiringDiv.innerHTML = '<div class="error">Yakında düşecek domain bulunamadı</div>';
                    return;
                }
                
                displayResults(domains);
            } catch (error) {
                expiringDiv.innerHTML = '<div class="error">Hata: ' + error.message + '</div>';
            }
        }
    </script>
</body>
</html>
<?php
require_once 'domain_hunter.php';

$hunter = new DomainHunter();
$action = $_GET['action'] ?? 'dashboard';
$message = '';

// AJAX istekleri için
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'check_domains':
            $results = $hunter->runAutoCheck();
            echo json_encode(['success' => true, 'results' => $results, 'count' => count($results)]);
            exit;
            
        case 'get_available':
            $limit = intval($_POST['limit'] ?? 50);
            $minValue = intval($_POST['min_value'] ?? 0);
            $domains = $hunter->getAvailableDomains($limit, $minValue);
            echo json_encode(['success' => true, 'domains' => $domains]);
            exit;
            
        case 'get_expiring':
            $days = intval($_POST['days'] ?? 30);
            $domains = $hunter->getExpiringDomains($days);
            echo json_encode(['success' => true, 'domains' => $domains]);
            exit;
    }
    
    echo json_encode(['success' => false, 'error' => 'Geçersiz işlem']);
    exit;
}

// Manuel işlemler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['action']) {
        case 'manual_check':
            if (!empty($_POST['domain']) && !empty($_POST['tld'])) {
                $domain = trim($_POST['domain']);
                $tld = trim($_POST['tld']);
                $result = $hunter->whoisQuery($domain, $tld);
                
                if ($result !== false) {
                    if ($result['available']) {
                        $message = "<div class='alert alert-success'>✅ $domain.$tld müsait!</div>";
                    } else {
                        $expiry = $result['expiry_date'] ? " (Bitiş: {$result['expiry_date']})" : "";
                        $message = "<div class='alert alert-warning'>❌ $domain.$tld kayıtlı$expiry</div>";
                    }
                } else {
                    $message = "<div class='alert alert-error'>❌ Sorgu hatası</div>";
                }
            }
            break;
    }
}

$stats = $hunter->getStats();
$availableDomains = $hunter->getAvailableDomains(20);
$expiringDomains = $hunter->getExpiringDomains(30);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Domain Hunter - Premium Domain Takipçisi</title>
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
            color: #333;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .header h1 {
            color: #667eea;
            font-size: 2.5em;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
            font-size: 1.1em;
        }
        
        .nav-tabs {
            display: flex;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .nav-tab {
            flex: 1;
            text-align: center;
            padding: 15px 20px;
            background: transparent;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: #666;
        }
        
        .nav-tab.active {
            background: #667eea;
            color: white;
            box-shadow: 0 3px 10px rgba(102, 126, 234, 0.3);
        }
        
        .nav-tab:hover:not(.active) {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
        }
        
        .card h3 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 1.4em;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 1.1em;
        }
        
        .domain-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        .domain-table th,
        .domain-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .domain-table th {
            background: #f8f9ff;
            color: #667eea;
            font-weight: 600;
        }
        
        .domain-table tr:hover {
            background: #f8f9ff;
        }
        
        .domain-name {
            font-weight: 600;
            color: #333;
        }
        
        .domain-value {
            color: #28a745;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        
        .form-inline {
            display: flex;
            gap: 15px;
            align-items: end;
        }
        
        .form-inline .form-group {
            flex: 1;
        }
        
        .btn {
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: #667eea;
            color: white;
        }
        
        .btn-primary:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-warning {
            background: #ffc107;
            color: #333;
        }
        
        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.3);
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
        }
        
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .tld-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }
        
        .tld-badge {
            background: #f8f9ff;
            border: 1px solid #667eea;
            border-radius: 20px;
            padding: 8px 15px;
            text-align: center;
            font-weight: 600;
            color: #667eea;
            font-size: 14px;
        }
        
        .empty-state {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        
        .empty-state h3 {
            margin-bottom: 15px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 15px;
            }
            
            .nav-tabs {
                flex-direction: column;
            }
            
            .form-inline {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Domain Hunter</h1>
            <p>Premium jenerik domainleri bulun ve takip edin</p>
        </div>
        
        <?php echo $message; ?>
        
        <div class="nav-tabs">
            <button class="nav-tab active" onclick="showTab('dashboard')">📊 Dashboard</button>
            <button class="nav-tab" onclick="showTab('available')">✅ Müsait Domainler</button>
            <button class="nav-tab" onclick="showTab('expiring')">⏰ Süresi Dolacaklar</button>
            <button class="nav-tab" onclick="showTab('search')">🔎 Manuel Arama</button>
            <button class="nav-tab" onclick="showTab('scanner')">🚀 Otomatik Tarama</button>
        </div>
        
        <!-- Dashboard Tab -->
        <div id="dashboard" class="tab-content active">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_domains'] ?? 0); ?></div>
                    <div class="stat-label">Toplam Domain</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['available_domains'] ?? 0); ?></div>
                    <div class="stat-label">Müsait Domain</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['registered_domains'] ?? 0); ?></div>
                    <div class="stat-label">Kayıtlı Domain</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo count($stats['tld_distribution'] ?? []); ?></div>
                    <div class="stat-label">Desteklenen TLD</div>
                </div>
            </div>
            
            <?php if (!empty($stats['tld_distribution'])): ?>
            <div class="card">
                <h3>TLD Dağılımı</h3>
                <div class="tld-grid">
                    <?php foreach ($stats['tld_distribution'] as $tld): ?>
                        <div class="tld-badge">
                            .<?php echo $tld['tld']; ?> (<?php echo $tld['available_count']; ?>)
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($stats['most_valuable'])): ?>
            <div class="card">
                <h3>En Değerli Müsait Domainler</h3>
                <table class="domain-table">
                    <thead>
                        <tr>
                            <th>Domain</th>
                            <th>Tahmini Değer</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['most_valuable'] as $domain): ?>
                        <tr>
                            <td class="domain-name"><?php echo $domain['domain_name'] . '.' . $domain['tld']; ?></td>
                            <td class="domain-value">$<?php echo number_format($domain['estimated_value']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Domains Tab -->
        <div id="available" class="tab-content">
            <div class="card">
                <h3>Müsait Domainler</h3>
                <div class="form-inline">
                    <div class="form-group">
                        <label>Limit</label>
                        <input type="number" id="available-limit" class="form-control" value="50" min="1" max="500">
                    </div>
                    <div class="form-group">
                        <label>Min. Değer ($)</label>
                        <input type="number" id="available-min-value" class="form-control" value="0" min="0">
                    </div>
                    <button class="btn btn-primary" onclick="loadAvailableDomains()">🔄 Yenile</button>
                </div>
                
                <div id="available-loading" class="loading">
                    <div class="spinner"></div>
                    <p>Müsait domainler yükleniyor...</p>
                </div>
                
                <div id="available-results">
                    <?php if (!empty($availableDomains)): ?>
                    <table class="domain-table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Tahmini Değer</th>
                                <th>Kontrol Sayısı</th>
                                <th>Son Kontrol</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($availableDomains as $domain): ?>
                            <tr>
                                <td class="domain-name"><?php echo $domain['domain_name'] . '.' . $domain['tld']; ?></td>
                                <td class="domain-value">$<?php echo number_format($domain['estimated_value']); ?></td>
                                <td><?php echo $domain['check_count']; ?>x</td>
                                <td><?php echo date('d.m.Y H:i', strtotime($domain['last_checked'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <h3>Henüz müsait domain bulunamadı</h3>
                        <p>Otomatik tarama başlatarak domainleri kontrol edebilirsiniz.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Expiring Domains Tab -->
        <div id="expiring" class="tab-content">
            <div class="card">
                <h3>Süresi Dolacak Domainler</h3>
                <div class="form-inline">
                    <div class="form-group">
                        <label>Gün Sayısı</label>
                        <input type="number" id="expiring-days" class="form-control" value="30" min="1" max="365">
                    </div>
                    <button class="btn btn-warning" onclick="loadExpiringDomains()">🔄 Yenile</button>
                </div>
                
                <div id="expiring-loading" class="loading">
                    <div class="spinner"></div>
                    <p>Süresi dolacak domainler yükleniyor...</p>
                </div>
                
                <div id="expiring-results">
                    <?php if (!empty($expiringDomains)): ?>
                    <table class="domain-table">
                        <thead>
                            <tr>
                                <th>Domain</th>
                                <th>Bitiş Tarihi</th>
                                <th>Registrar</th>
                                <th>Tahmini Değer</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($expiringDomains as $domain): ?>
                            <tr>
                                <td class="domain-name"><?php echo $domain['domain_name'] . '.' . $domain['tld']; ?></td>
                                <td><?php echo date('d.m.Y H:i', strtotime($domain['expiry_date'])); ?></td>
                                <td><?php echo $domain['registrar']; ?></td>
                                <td class="domain-value">$<?php echo number_format($domain['estimated_value']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else: ?>
                    <div class="empty-state">
                        <h3>Süresi dolacak domain bulunamadı</h3>
                        <p>Belirtilen süre içinde süresi dolacak domain bulunmuyor.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Manual Search Tab -->
        <div id="search" class="tab-content">
            <div class="card">
                <h3>Manuel Domain Arama</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="manual_check">
                    <div class="form-inline">
                        <div class="form-group">
                            <label>Domain Adı</label>
                            <input type="text" name="domain" class="form-control" placeholder="örnek: google" required>
                        </div>
                        <div class="form-group">
                            <label>TLD</label>
                            <select name="tld" class="form-control" required>
                                <option value="com">.com</option>
                                <option value="net">.net</option>
                                <option value="org">.org</option>
                                <option value="info">.info</option>
                                <option value="biz">.biz</option>
                                <option value="com.tr">.com.tr</option>
                                <option value="net.tr">.net.tr</option>
                                <option value="org.tr">.org.tr</option>
                                <option value="io">.io</option>
                                <option value="co">.co</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">🔍 Sorgula</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Auto Scanner Tab -->
        <div id="scanner" class="tab-content">
            <div class="card">
                <h3>Otomatik Domain Tarayıcısı</h3>
                <p>Bu araç premium jenerik domainleri otomatik olarak tarar ve müsait olanları bulur.</p>
                
                <button class="btn btn-success" onclick="startAutoScan()">🚀 Taramayı Başlat</button>
                
                <div id="scan-loading" class="loading">
                    <div class="spinner"></div>
                    <p>Domainler taranıyor, bu işlem birkaç dakika sürebilir...</p>
                </div>
                
                <div id="scan-results" style="display: none;">
                    <h4>Tarama Sonuçları</h4>
                    <div id="scan-summary"></div>
                    <div id="scan-domains"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.nav-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function loadAvailableDomains() {
            const loading = document.getElementById('available-loading');
            const results = document.getElementById('available-results');
            const limit = document.getElementById('available-limit').value;
            const minValue = document.getElementById('available-min-value').value;
            
            loading.style.display = 'block';
            results.style.display = 'none';
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_available');
            formData.append('limit', limit);
            formData.append('min_value', minValue);
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                results.style.display = 'block';
                
                if (data.success && data.domains.length > 0) {
                    let html = '<table class="domain-table"><thead><tr><th>Domain</th><th>Tahmini Değer</th><th>Kontrol Sayısı</th><th>Son Kontrol</th></tr></thead><tbody>';
                    
                    data.domains.forEach(domain => {
                        const lastChecked = new Date(domain.last_checked).toLocaleDateString('tr-TR');
                        html += `<tr>
                            <td class="domain-name">${domain.domain_name}.${domain.tld}</td>
                            <td class="domain-value">$${parseInt(domain.estimated_value).toLocaleString()}</td>
                            <td>${domain.check_count}x</td>
                            <td>${lastChecked}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    results.innerHTML = html;
                } else {
                    results.innerHTML = '<div class="empty-state"><h3>Müsait domain bulunamadı</h3><p>Belirtilen kriterlere uygun domain bulunmuyor.</p></div>';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                results.style.display = 'block';
                results.innerHTML = '<div class="alert alert-error">Hata: ' + error.message + '</div>';
            });
        }
        
        function loadExpiringDomains() {
            const loading = document.getElementById('expiring-loading');
            const results = document.getElementById('expiring-results');
            const days = document.getElementById('expiring-days').value;
            
            loading.style.display = 'block';
            results.style.display = 'none';
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'get_expiring');
            formData.append('days', days);
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                results.style.display = 'block';
                
                if (data.success && data.domains.length > 0) {
                    let html = '<table class="domain-table"><thead><tr><th>Domain</th><th>Bitiş Tarihi</th><th>Registrar</th><th>Tahmini Değer</th></tr></thead><tbody>';
                    
                    data.domains.forEach(domain => {
                        const expiryDate = new Date(domain.expiry_date).toLocaleDateString('tr-TR');
                        html += `<tr>
                            <td class="domain-name">${domain.domain_name}.${domain.tld}</td>
                            <td>${expiryDate}</td>
                            <td>${domain.registrar || 'Bilinmiyor'}</td>
                            <td class="domain-value">$${parseInt(domain.estimated_value).toLocaleString()}</td>
                        </tr>`;
                    });
                    
                    html += '</tbody></table>';
                    results.innerHTML = html;
                } else {
                    results.innerHTML = '<div class="empty-state"><h3>Süresi dolacak domain bulunamadı</h3><p>Belirtilen süre içinde süresi dolacak domain bulunmuyor.</p></div>';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                results.style.display = 'block';
                results.innerHTML = '<div class="alert alert-error">Hata: ' + error.message + '</div>';
            });
        }
        
        function startAutoScan() {
            const loading = document.getElementById('scan-loading');
            const results = document.getElementById('scan-results');
            const summary = document.getElementById('scan-summary');
            const domains = document.getElementById('scan-domains');
            
            loading.style.display = 'block';
            results.style.display = 'none';
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'check_domains');
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                loading.style.display = 'none';
                results.style.display = 'block';
                
                if (data.success) {
                    summary.innerHTML = `<div class="alert alert-success">✅ Tarama tamamlandı! ${data.count} yeni müsait domain bulundu.</div>`;
                    
                    if (data.results.length > 0) {
                        let html = '<table class="domain-table"><thead><tr><th>Domain</th><th>Tahmini Değer</th><th>TLD Fiyatı</th></tr></thead><tbody>';
                        
                        data.results.forEach(result => {
                            html += `<tr>
                                <td class="domain-name">${result.domain}</td>
                                <td class="domain-value">$${parseInt(result.estimated_value).toLocaleString()}</td>
                                <td>$${result.tld_price}</td>
                            </tr>`;
                        });
                        
                        html += '</tbody></table>';
                        domains.innerHTML = html;
                    } else {
                        domains.innerHTML = '<div class="empty-state"><h3>Bu turda müsait domain bulunamadı</h3><p>Taramayı daha sonra tekrar deneyebilirsiniz.</p></div>';
                    }
                } else {
                    summary.innerHTML = '<div class="alert alert-error">❌ Tarama hatası: ' + (data.error || 'Bilinmeyen hata') + '</div>';
                }
            })
            .catch(error => {
                loading.style.display = 'none';
                results.style.display = 'block';
                summary.innerHTML = '<div class="alert alert-error">❌ Bağlantı hatası: ' + error.message + '</div>';
            });
        }
        
        // Auto refresh stats every 5 minutes
        setInterval(() => {
            if (document.getElementById('dashboard').classList.contains('active')) {
                location.reload();
            }
        }, 300000);
    </script>
</body>
</html>
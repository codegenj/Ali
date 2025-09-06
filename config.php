<?php
/**
 * Domain Checker Konfigürasyon Dosyası
 */

return [
    // Veritabanı ayarları
    'db' => [
        'host' => 'localhost',
        'name' => 'domain_checker',
        'user' => 'root',
        'pass' => ''
    ],
    
    // Domain uzantıları (istediğiniz gibi düzenleyebilirsiniz)
    'extensions' => [
        'com',
        'net', 
        'org',
        'com.tr',
        'net.tr',
        'org.tr',
        'info',
        'biz',
        'co',
        'io',
        'me',
        'tv',
        'cc',
        'name',
        'pro',
        'mobi',
        'asia',
        'tel',
        'travel',
        'jobs',
        'aero',
        'museum',
        'coop',
        'int',
        'edu',
        'gov',
        'mil'
    ],
    
    // Grace period (gün cinsinden)
    // Domain süresi bittikten sonra kaç gün sonra düşecek
    'grace_period_days' => 30,
    
    // Premium domain kriterleri
    'premium_keywords' => [
        // İş ve finans
        'business', 'money', 'finance', 'tech', 'online', 'shop', 'store',
        'market', 'trade', 'invest', 'crypto', 'bitcoin', 'bank', 'loan',
        'insurance', 'real', 'estate', 'property', 'home', 'car', 'auto',
        
        // Eğlence ve yaşam
        'travel', 'hotel', 'book', 'movie', 'music', 'game', 'sport',
        'health', 'fitness', 'beauty', 'fashion', 'food', 'restaurant',
        
        // Eğitim ve profesyonel
        'education', 'school', 'university', 'course', 'training',
        'law', 'legal', 'medical', 'doctor', 'clinic', 'hospital',
        
        // Teknoloji
        'app', 'software', 'cloud', 'data', 'ai', 'ml', 'blockchain',
        'crypto', 'nft', 'web3', 'metaverse', 'vr', 'ar',
        
        // Türkçe kelimeler
        'is', 'ticaret', 'saglik', 'egitim', 'emlak', 'araba', 'ev',
        'yemek', 'seyahat', 'spor', 'muzik', 'film', 'kitap',
        'teknoloji', 'yazilim', 'internet', 'web', 'site'
    ],
    
    // WHOIS sunucuları
    'whois_servers' => [
        'com' => 'whois.verisign-grs.com',
        'net' => 'whois.verisign-grs.com', 
        'org' => 'whois.pir.org',
        'info' => 'whois.afilias.net',
        'biz' => 'whois.neulevel.biz',
        'co' => 'whois.nic.co',
        'io' => 'whois.nic.io',
        'me' => 'whois.nic.me',
        'tv' => 'whois.nic.tv',
        'cc' => 'whois.nic.cc',
        'name' => 'whois.nic.name',
        'pro' => 'whois.registrypro.pro',
        'mobi' => 'whois.dotmobiregistry.net',
        'asia' => 'whois.nic.asia',
        'tel' => 'whois.nic.tel',
        'travel' => 'whois.nic.travel',
        'jobs' => 'whois.nic.jobs',
        'aero' => 'whois.information.aero',
        'museum' => 'whois.museum',
        'coop' => 'whois.nic.coop',
        'int' => 'whois.iana.org',
        'edu' => 'whois.educause.edu',
        'gov' => 'whois.nic.gov',
        'mil' => 'whois.nic.mil',
        'com.tr' => 'whois.nic.tr',
        'net.tr' => 'whois.nic.tr',
        'org.tr' => 'whois.nic.tr'
    ],
    
    // API ayarları (gelecekte kullanım için)
    'apis' => [
        'whois_api' => [
            'enabled' => false,
            'url' => 'https://api.whoisjson.com/v1/whois',
            'key' => ''
        ],
        'domain_api' => [
            'enabled' => false,
            'url' => 'https://api.domain.com/v1/check',
            'key' => ''
        ]
    ],
    
    // Cron job ayarları
    'cron' => [
        'enabled' => true,
        'check_interval' => 3600, // saniye cinsinden (1 saat)
        'max_domains_per_run' => 100,
        'timeout' => 30 // saniye
    ],
    
    // Bildirim ayarları
    'notifications' => [
        'email' => [
            'enabled' => false,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_user' => '',
            'smtp_pass' => '',
            'from_email' => '',
            'to_email' => ''
        ],
        'telegram' => [
            'enabled' => false,
            'bot_token' => '',
            'chat_id' => ''
        ]
    ],
    
    // Cache ayarları
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // saniye cinsinden (1 saat)
        'path' => '/tmp/domain_cache'
    ],
    
    // Log ayarları
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'path' => '/var/log/domain_checker.log',
        'max_size' => '10MB'
    ]
];
?>
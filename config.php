<?php
/**
 * Domain Hunter Konfigürasyon Dosyası
 * Tüm ayarları buradan yönetebilirsiniz
 */

return [
    // Veritabanı ayarları
    'database' => [
        'type' => 'sqlite', // sqlite veya mysql
        'file' => __DIR__ . '/domain_hunter.db', // SQLite için
        // MySQL için (isteğe bağlı)
        'host' => 'localhost',
        'name' => 'domain_hunter',
        'user' => 'username',
        'pass' => 'password',
        'charset' => 'utf8mb4'
    ],
    
    // TLD ayarları ve fiyatları
    'tlds' => [
        'com' => [
            'whois_server' => 'whois.verisign-grs.com',
            'price' => 12,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 10
        ],
        'net' => [
            'whois_server' => 'whois.verisign-grs.com',
            'price' => 12,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 9
        ],
        'org' => [
            'whois_server' => 'whois.pir.org',
            'price' => 15,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 8
        ],
        'info' => [
            'whois_server' => 'whois.afilias.net',
            'price' => 10,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 7
        ],
        'biz' => [
            'whois_server' => 'whois.neulevel.biz',
            'price' => 10,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 6
        ],
        'com.tr' => [
            'whois_server' => 'whois.nic.tr',
            'price' => 25,
            'currency' => 'USD',
            'registrar_url' => 'https://www.turhost.com',
            'priority' => 8
        ],
        'net.tr' => [
            'whois_server' => 'whois.nic.tr',
            'price' => 25,
            'currency' => 'USD',
            'registrar_url' => 'https://www.turhost.com',
            'priority' => 7
        ],
        'org.tr' => [
            'whois_server' => 'whois.nic.tr',
            'price' => 25,
            'currency' => 'USD',
            'registrar_url' => 'https://www.turhost.com',
            'priority' => 6
        ],
        'io' => [
            'whois_server' => 'whois.nic.io',
            'price' => 60,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 9
        ],
        'co' => [
            'whois_server' => 'whois.nic.co',
            'price' => 30,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 7
        ],
        'xyz' => [
            'whois_server' => 'whois.nic.xyz',
            'price' => 2,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 5
        ],
        'online' => [
            'whois_server' => 'whois.nic.online',
            'price' => 40,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 6
        ],
        'site' => [
            'whois_server' => 'whois.nic.site',
            'price' => 30,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 6
        ],
        'tech' => [
            'whois_server' => 'whois.nic.tech',
            'price' => 50,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 8
        ],
        'app' => [
            'whois_server' => 'whois.nic.google',
            'price' => 20,
            'currency' => 'USD',
            'registrar_url' => 'https://www.namecheap.com',
            'priority' => 9
        ]
    ],
    
    // Premium kelimeler - değer hesaplama için kullanılır
    'premium_keywords' => [
        // Yüksek değerli teknoloji terimleri
        'high_value' => [
            'ai', 'blockchain', 'crypto', 'nft', 'metaverse', 'web3', 'defi',
            'fintech', 'saas', 'api', 'cloud', 'data', 'analytics', 'iot',
            'vr', 'ar', 'ml', 'bitcoin', 'ethereum', 'trading', 'invest'
        ],
        
        // Orta değerli iş terimleri
        'medium_value' => [
            'business', 'company', 'corp', 'enterprise', 'startup', 'venture',
            'market', 'shop', 'store', 'buy', 'sell', 'deal', 'money', 'bank',
            'pay', 'payment', 'finance', 'loan', 'insurance', 'real', 'estate'
        ],
        
        // Düşük değerli genel terimler
        'low_value' => [
            'app', 'web', 'site', 'blog', 'news', 'info', 'guide', 'help',
            'service', 'solution', 'system', 'platform', 'network', 'social',
            'community', 'forum', 'chat', 'message', 'email', 'mobile'
        ],
        
        // Türkçe premium kelimeler
        'turkish' => [
            'para', 'is', 'sirket', 'ticaret', 'pazarlama', 'satis', 'alis',
            'emlak', 'ev', 'araba', 'otomobil', 'saglik', 'hastane', 'doktor',
            'egitim', 'okul', 'universite', 'kurs', 'ders', 'ogren', 'teknoloji',
            'dijital', 'yazilim', 'uygulama', 'mobil', 'internet', 'web', 'site'
        ],
        
        // Sektör spesifik terimler
        'health' => [
            'health', 'medical', 'doctor', 'hospital', 'clinic', 'pharmacy',
            'medicine', 'care', 'wellness', 'fitness', 'diet', 'nutrition'
        ],
        
        'education' => [
            'education', 'school', 'university', 'college', 'course', 'training',
            'learn', 'study', 'book', 'tutorial', 'skill', 'knowledge'
        ],
        
        'travel' => [
            'travel', 'hotel', 'flight', 'vacation', 'holiday', 'trip', 'tour',
            'booking', 'reservation', 'destination', 'adventure', 'cruise'
        ],
        
        'food' => [
            'food', 'restaurant', 'recipe', 'cooking', 'chef', 'kitchen',
            'delivery', 'meal', 'diet', 'nutrition', 'organic', 'fresh'
        ]
    ],
    
    // Değer hesaplama çarpanları
    'value_multipliers' => [
        'length' => [
            1 => 50,  // 1 karakter - çok yüksek değer
            2 => 25,  // 2 karakter - yüksek değer
            3 => 15,  // 3 karakter - yüksek değer
            4 => 8,   // 4 karakter - orta yüksek değer
            5 => 4,   // 5 karakter - orta değer
            6 => 2,   // 6 karakter - düşük değer
            7 => 1.5, // 7+ karakter - normal değer
        ],
        'keyword_category' => [
            'high_value' => 10,
            'medium_value' => 5,
            'low_value' => 2,
            'turkish' => 3,
            'health' => 4,
            'education' => 3,
            'travel' => 3,
            'food' => 2
        ],
        'pattern' => [
            'numeric_only' => 0.5,        // Sadece sayı
            'alphabetic_only' => 2,       // Sadece harf
            'alphanumeric' => 1.5,        // Harf + sayı
            'no_repeating' => 1.2,        // Tekrar eden karakter yok
            'dictionary_word' => 3,       // Sözlük kelimesi
            'brandable' => 2.5,           // Marka olabilir
        ]
    ],
    
    // Tarama ayarları
    'scanning' => [
        'max_domains_per_run' => 100,     // Her çalıştırmada kontrol edilecek maksimum domain
        'timeout' => 30,                  // WHOIS sorgu timeout (saniye)
        'delay_between_queries' => 0.5,   // Sorgular arası bekleme süresi (saniye)
        'max_threads' => 5,               // Eşzamanlı sorgu sayısı
        'retry_attempts' => 3,            // Başarısız sorgu tekrar sayısı
        'check_interval' => 3600,         // Otomatik kontrol aralığı (saniye)
        'min_domain_length' => 2,         // Minimum domain uzunluğu
        'max_domain_length' => 20,        // Maksimum domain uzunluğu
    ],
    
    // Bildirim ayarları
    'notifications' => [
        'email' => [
            'enabled' => false,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_user' => 'your-email@gmail.com',
            'smtp_pass' => 'your-app-password',
            'from_email' => 'domain-hunter@yoursite.com',
            'from_name' => 'Domain Hunter',
            'to_email' => 'your-email@gmail.com'
        ],
        'webhook' => [
            'enabled' => false,
            'url' => 'https://hooks.slack.com/services/YOUR/SLACK/WEBHOOK',
            'method' => 'POST'
        ]
    ],
    
    // Log ayarları
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error
        'file' => __DIR__ . '/logs/domain_hunter.log',
        'max_size' => 10485760, // 10MB
        'rotate' => true
    ],
    
    // Güvenlik ayarları
    'security' => [
        'api_key' => null, // API erişimi için (isteğe bağlı)
        'allowed_ips' => [], // İzin verilen IP'ler (boş = herkese açık)
        'rate_limit' => [
            'enabled' => true,
            'max_requests' => 100, // Saatte maksimum istek
            'window' => 3600 // Zaman penceresi (saniye)
        ]
    ],
    
    // Gelişmiş ayarlar
    'advanced' => [
        'cache_enabled' => true,
        'cache_ttl' => 3600, // Cache süresi (saniye)
        'database_cleanup' => true,
        'cleanup_older_than' => 2592000, // 30 gün (saniye)
        'export_formats' => ['json', 'csv', 'xml'],
        'backup_enabled' => true,
        'backup_interval' => 86400, // 24 saat
        'backup_retention' => 7 // Kaç gün saklanacak
    ]
];
?>
Domain Scout (PHP CLI)

Domain availability checker and drop-time estimator for TLDs like `com`, `net`, `org`, `com.tr`.

Features

- Check single or bulk domains via CLI
- Parse WHOIS for creation/expiry/status
- Estimate drop windows (heuristic) and show confidence
- Output as table, CSV, or JSON
- Configurable TLD WHOIS servers and parsers via `config/tlds.php`

Requirements

- PHP 8.0+
- Composer

Install

```
cd /path/to/domain-scout
composer install
```

Usage

```
# Single domains
php bin/domainscout check example.com example.net

# From file
php bin/domainscout check -f domains.txt

# Filter by TLDs
php bin/domainscout check -f domains.txt -t com,net

# Output formats
php bin/domainscout check -f domains.txt --format json
php bin/domainscout check -f domains.txt --format csv

# Custom WHOIS timeout and sleep between queries (ms)
php bin/domainscout check -f domains.txt --timeout 10 --sleep 250

# Custom TLD config path
php bin/domainscout check -f domains.txt -c /your/tlds.php
```

Notes

- Drop time estimates are heuristic. For `pendingDelete`, ~5 days after last update; for `redemption`, ~30 days. Otherwise expiry + 60–75 days depending on TLD.
- WHOIS formats change. Adjust regex or parsers in `src/Whois/Parsers/` if needed.
- For `com.tr`, WHOIS responses may vary across hosts; update patterns if your registry changes output.

Configuring TLDs

Edit `config/tlds.php`. Example entry:

```
return [
    'com' => [
        'host' => 'whois.verisign-grs.com',
        'query' => '%s',
        'not_found' => '/No match for\s+\"[^\"]+\"/i',
        'parser' => 'verisign',
    ],
];
```

Disclaimer

Use responsibly and respect WHOIS rate limits/terms.

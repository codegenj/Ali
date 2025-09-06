#!/usr/bin/env php
<?php

declare(strict_types=1);

// Simple, dependency-free domain checker using WHOIS.
// Usage examples:
//   php bin/domain-checker.php --domains samples/domains.txt --tlds-file samples/tlds.txt
//   php bin/domain-checker.php --domains samples/domains.txt --output json --available-only
//   php bin/domain-checker.php --domains one-per-line.txt --tlds com,net,org --timeout 8 --retries 1

require_once __DIR__ . '/../src/Util.php';
require_once __DIR__ . '/../src/WhoisClient.php';
require_once __DIR__ . '/../src/Parsers.php';
require_once __DIR__ . '/../src/DropEstimator.php';

use DomainTool\Util;
use DomainTool\WhoisClient;
use DomainTool\Parsers;
use DomainTool\DropEstimator;

function print_help(): void {
	$help = "\nKullanım:\n  php bin/domain-checker.php --domains <dosya> [--tlds <virgülle_ayrılmış>] [--tlds-file <dosya>]\\\n    [--output table|json|csv] [--timeout <sn>] [--retries <n>] [--available-only] [--follow-referral]\\\n    [--config <tlds.json_yolu>]\n\nSeçenekler:\n  --domains           Satır başına bir domain kökü (ör: 'bestshoes', 'mybrand') içeren dosya\n  --tlds              Virgülle ayrılmış TLD listesi (ör: com,net,org,com.tr)\n  --tlds-file         Satır başına bir TLD içeren dosya\n  --output            Çıktı formatı: table (varsayılan) | json | csv\n  --timeout           WHOIS istek zaman aşımı (sn), varsayılan 8\n  --retries           Başarısız WHOIS denemesi tekrar sayısı, varsayılan 1\n  --available-only    Sadece şu an boşta (available) olanları göster\n  --follow-referral   Registrar WHOIS yönlendirmesini takip etmeye çalış\n  --config            TLD/WINDOW/Regex ayarlarının olduğu JSON (varsayılan config/tlds.json)\n\nÖrnek:\n  php bin/domain-checker.php --domains samples/domains.txt --tlds com,net,org --available-only\n\n";
	fwrite(STDERR, $help);
}

$options = getopt("", [
	"domains:",
	"tlds::",
	"tlds-file::",
	"output::",
	"timeout::",
	"retries::",
	"available-only",
	"follow-referral",
	"config::",
	"help",
]);

if (isset($options['help']) || !isset($options['domains'])) {
	print_help();
	exit(isset($options['help']) ? 0 : 1);
}

$domainsFile = Util::absPath((string)$options['domains']);
if (!is_file($domainsFile)) {
	fwrite(STDERR, "Hata: domains dosyası bulunamadı: {$domainsFile}\n");
	exit(1);
}

$configPath = isset($options['config']) ? Util::absPath((string)$options['config']) : Util::absPath(__DIR__ . '/../config/tlds.json');
if (!is_file($configPath)) {
	fwrite(STDERR, "Hata: config dosyası bulunamadı: {$configPath}\n");
	exit(1);
}

$config = json_decode((string)file_get_contents($configPath), true);
if (!is_array($config)) {
	fwrite(STDERR, "Hata: config JSON okunamadı: {$configPath}\n");
	exit(1);
}

$tlds = [];
if (!empty($options['tlds'])) {
	$tlds = array_values(array_filter(array_map('trim', explode(',', (string)$options['tlds']))));
}
if (!empty($options['tlds-file'])) {
	$tldsFromFile = Util::readLines(Util::absPath((string)$options['tlds-file']));
	$tlds = array_merge($tlds, $tldsFromFile);
}
if (empty($tlds)) {
	$tlds = array_keys($config['tlds'] ?? []);
}

if (empty($tlds)) {
	fwrite(STDERR, "Hata: geçerli TLD listesi bulunamadı. --tlds veya --tlds-file verin, ya da config/tlds.json'u doldurun.\n");
	exit(1);
}

$domains = Util::readLines($domainsFile);
if (empty($domains)) {
	fwrite(STDERR, "Hata: domain listesi boş.\n");
	exit(1);
}

$timeout = isset($options['timeout']) ? max(2, (int)$options['timeout']) : 8;
$retries = isset($options['retries']) ? max(0, (int)$options['retries']) : 1;
$followReferral = isset($options['follow-referral']);
$output = isset($options['output']) ? strtolower((string)$options['output']) : 'table';
$availableOnly = isset($options['available-only']);

$client = new WhoisClient($timeout, $retries, $config['tlds'] ?? []);
$dropEstimator = new DropEstimator($config['drop_windows'] ?? [], $config['lifecycle_days'] ?? []);

$results = [];

foreach ($domains as $domainRoot) {
	$domainRoot = strtolower(trim($domainRoot));
	if ($domainRoot === '' || strpos($domainRoot, ' ') !== false) {
		continue;
	}
	foreach ($tlds as $tld) {
		$tld = strtolower(trim($tld));
		if ($tld === '') { continue; }
		$fullDomain = $domainRoot . '.' . $tld;

		$whoisResult = $client->lookup($fullDomain, $tld, $followReferral);
		$parsed = Parsers::parseWhois($whoisResult['raw'] ?? '', $tld, $config['tlds'][$tld] ?? []);
		$available = Parsers::isAvailable($whoisResult['raw'] ?? '', $tld, $config['tlds'][$tld] ?? []);
		$phase = Parsers::inferPhase($parsed['status_list'] ?? []);
		$estimatedDrop = $dropEstimator->estimateDropDateUtc($tld, $parsed, $phase);

		$record = [
			'domain' => $fullDomain,
			'tld' => $tld,
			'available' => $available,
			'status' => implode('; ', $parsed['status_list'] ?? []),
			'expiry_utc' => $parsed['expiry_utc'] ?? null,
			'phase' => $phase,
			'estimated_drop_utc' => $estimatedDrop['date_utc'] ?? null,
			'estimated_window_utc' => $estimatedDrop['window_utc'] ?? null,
			'whois_server' => $whoisResult['server'] ?? null,
		];

		if (!$availableOnly || $available) {
			$results[] = $record;
		}
	}
}

// Output
switch ($output) {
	case 'json':
		echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
		break;
	case 'csv':
		$headers = ['domain','tld','available','status','expiry_utc','phase','estimated_drop_utc','estimated_window_utc','whois_server'];
		echo implode(',', $headers) . "\n";
		foreach ($results as $row) {
			$line = [];
			foreach ($headers as $h) {
				$val = $row[$h] ?? '';
				$val = is_bool($val) ? ($val ? 'true' : 'false') : (string)$val;
				$val = str_replace(["\n","\r","\""], [' ',' ', '""'], $val);
				if (strpos($val, ',') !== false || strpos($val, ' ') !== false) {
					$val = '"' . $val . '"';
				}
				$line[] = $val;
			}
			echo implode(',', $line) . "\n";
		}
		break;
	case 'table':
	default:
		// simple table
		$headers = ['Domain','TLD','Boşta','Durum','Bitiş (UTC)','Faz','Düşüş (UTC)','Pencere (UTC)','WHOIS'];
		$widths = array_map('strlen', $headers);
		$rows = [];
		foreach ($results as $r) {
			$row = [
				$r['domain'] ?? '',
				$r['tld'] ?? '',
				(isset($r['available']) && $r['available']) ? 'Evet' : 'Hayır',
				$r['status'] ?? '',
				$r['expiry_utc'] ?? '-',
				$r['phase'] ?? '-',
				$r['estimated_drop_utc'] ?? '-',
				$r['estimated_window_utc'] ?? '-',
				$r['whois_server'] ?? '-',
			];
			$rows[] = $row;
			foreach ($row as $i => $col) {
				$widths[$i] = max($widths[$i], strlen((string)$col));
			}
		}
		$sep = function() use ($widths) {
			$out = '+';
			foreach ($widths as $w) { $out .= str_repeat('-', $w + 2) . '+'; }
			return $out . "\n";
		};
		$line = function(array $cols) use ($widths) {
			$out = '|';
			foreach ($cols as $i => $c) {
				$out .= ' ' . str_pad((string)$c, $widths[$i]) . ' |';
			}
			return $out . "\n";
		};
		echo $sep();
		echo $line($headers);
		echo $sep();
		foreach ($rows as $r) {
			echo $line($r);
		}
		echo $sep();
}

exit(0);


<?php

declare(strict_types=1);

namespace DomainTool;

final class WhoisClient
{
	private int $timeoutSeconds;
	private int $retries;
	private array $tldConfig;

	public function __construct(int $timeoutSeconds, int $retries, array $tldConfig)
	{
		$this->timeoutSeconds = $timeoutSeconds;
		$this->retries = $retries;
		$this->tldConfig = $tldConfig;
	}

	public function lookup(string $fullDomain, string $tld, bool $followReferral = false): array
	{
		$server = $this->resolveServer($tld);
		$raw = $this->queryWhois($server, $fullDomain);
		$usedServer = $server;

		if ($followReferral && is_string($raw)) {
			$refServer = $this->extractReferralServer($raw);
			if ($refServer !== null && strtolower($refServer) !== strtolower($server)) {
				$refRaw = $this->queryWhois($refServer, $fullDomain);
				if (is_string($refRaw) && trim($refRaw) !== '') {
					$raw = $refRaw;
					$usedServer = $refServer;
				}
			}
		}

		return [
			'domain' => $fullDomain,
			'server' => $usedServer,
			'raw' => $raw,
		];
	}

	private function resolveServer(string $tld): string
	{
		$conf = $this->tldConfig[$tld] ?? [];
		$server = (string)($conf['whois'] ?? '');
		if ($server === '') {
			// Fallbacks
			$map = [
				'com' => 'whois.verisign-grs.com',
				'net' => 'whois.verisign-grs.com',
				'org' => 'whois.pir.org',
				'io' => 'whois.nic.io',
				'co' => 'whois.nic.co',
				'com.tr' => 'whois.trabis.gov.tr',
			];
			return $map[$tld] ?? 'whois.iana.org';
		}
		return $server;
	}

	private function extractReferralServer(string $raw): ?string
	{
		$patterns = [
			'/Registrar WHOIS Server:\s*([^\s]+)\s*/i',
			'/ReferralServer:\s*whois:\/\/([^\s]+)\s*/i',
			'/Whois Server:\s*([^\s]+)\s*/i',
		];
		foreach ($patterns as $rx) {
			if (preg_match($rx, $raw, $m) === 1) {
				return trim((string)$m[1]);
			}
		}
		return null;
	}

	private function queryWhois(string $server, string $domain): string
	{
		$attempt = 0;
		$lastErr = '';
		while ($attempt <= $this->retries) {
			$attempt++;
			$fp = @fsockopen($server, 43, $errno, $errstr, $this->timeoutSeconds);
			if (!$fp) {
				$lastErr = $errstr;
				continue;
			}
			stream_set_timeout($fp, $this->timeoutSeconds);
			fwrite($fp, $domain . "\r\n");
			$resp = '';
			while (!feof($fp)) {
				$resp .= (string)fgets($fp, 4096);
			}
			fclose($fp);
			if ($resp !== '') { return $resp; }
		}
		return ""; // empty raw if all attempts failed
	}
}


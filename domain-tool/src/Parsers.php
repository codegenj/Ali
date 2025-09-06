<?php

declare(strict_types=1);

namespace DomainTool;

final class Parsers
{
	public static function isAvailable(string $raw, string $tld, array $tldConf): bool
	{
		$rawLower = strtolower($raw);
		$phrases = (array)($tldConf['available_phrases'] ?? []);
		foreach ($phrases as $p) {
			if ($p !== '' && strpos($rawLower, strtolower($p)) !== false) {
				return true;
			}
		}
		$regexes = (array)($tldConf['available_regexes'] ?? []);
		foreach ($regexes as $rx) {
			if ($rx !== '' && @preg_match($rx, $raw) === 1) {
				return true;
			}
		}
		return false;
	}

	public static function parseWhois(string $raw, string $tld, array $tldConf): array
	{
		$expiry = Util::firstMatch((array)($tldConf['expiry_regexes'] ?? []), $raw);
		$created = Util::firstMatch((array)($tldConf['created_regexes'] ?? []), $raw);
		$statusList = self::parseStatuses($raw, (array)($tldConf['status_regexes'] ?? []));

		return [
			'created_utc' => Util::parseDateToUtc($created),
			'expiry_utc' => Util::parseDateToUtc($expiry),
			'status_list' => $statusList,
		];
	}

	public static function parseStatuses(string $raw, array $regexes): array
	{
		$statuses = [];
		if (empty($regexes)) {
			$regexes = [
				'/^Status:\s*(.+)$/im',
				'/^Domain Status:\s*(.+)$/im',
			];
		}
		foreach ($regexes as $rx) {
			if (@preg_match_all($rx, $raw, $m) && !empty($m[1])) {
				foreach ($m[1] as $s) {
					$s = trim((string)$s);
					if ($s !== '' && !in_array($s, $statuses, true)) {
						$statuses[] = $s;
					}
				}
			}
		}
		return $statuses;
	}

	public static function inferPhase(array $statusList): string
	{
		$lower = array_map('strtolower', $statusList);
		foreach ($lower as $s) {
			if (strpos($s, 'pending delete') !== false || strpos($s, 'pendingdelete') !== false) {
				return 'pendingDelete';
			}
			if (strpos($s, 'redemption') !== false) { return 'redemption'; }
			if (strpos($s, 'clienthold') !== false || strpos($s, 'serverhold') !== false) {
				return 'hold';
			}
		}
		return count($statusList) > 0 ? 'active' : 'unknown';
	}
}


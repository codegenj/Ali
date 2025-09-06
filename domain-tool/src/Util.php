<?php

declare(strict_types=1);

namespace DomainTool;

final class Util
{
	public static function absPath(string $path): string
	{
		if ($path === '') { return $path; }
		if ($path[0] === '/' || preg_match('#^[A-Za-z]:\\\\#', $path) === 1) {
			return $path;
		}
		return realpath(getcwd() . DIRECTORY_SEPARATOR . $path) ?: ($path);
	}

	public static function readLines(string $filePath): array
	{
		if (!is_file($filePath)) { return []; }
		$lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
		return array_values(array_filter(array_map(static function ($l) {
			$l = trim((string)$l);
			if ($l === '' || $l[0] === '#') { return ''; }
			return strtolower($l);
		}, $lines)));
	}

	public static function parseDateToUtc(?string $text): ?string
	{
		if ($text === null) { return null; }
		$text = trim($text);
		if ($text === '') { return null; }

		// Try multiple common WHOIS formats
		$patterns = [
			'D, d M Y H:i:s T',  // Tue, 14 May 2024 12:34:56 UTC
			'Y-m-d\TH:i:s\Z',   // 2024-05-14T12:34:56Z
			'Y-m-d H:i:s',       // 2024-05-14 12:34:56
			'Y-m-d',              // 2024-05-14
			'd-m-Y H:i:s',        // 14-05-2024 12:34:56
			'd.m.Y H:i:s',        // 14.05.2024 12:34:56
			'd/m/Y H:i:s',        // 14/05/2024 12:34:56
			'M d Y H:i:s',        // May 14 2024 12:34:56
			'M d, Y',             // May 14, 2024
		];

		foreach ($patterns as $fmt) {
			$dt = \DateTimeImmutable::createFromFormat($fmt, $text, new \DateTimeZone('UTC'));
			if ($dt instanceof \DateTimeImmutable) {
				return $dt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
			}
		}

		// Fallback: try strtotime
		$ts = strtotime($text);
		if ($ts !== false) {
			return gmdate('Y-m-d H:i:s', $ts);
		}
		return null;
	}

	public static function firstMatch(array $regexes, string $text): ?string
	{
		foreach ($regexes as $rx) {
			if (@preg_match($rx, $text, $m) === 1) {
				foreach ($m as $k => $v) {
					if ($k === 0) { continue; }
					if ($v !== '') { return (string)$v; }
				}
			}
		}
		return null;
	}
}


<?php

declare(strict_types=1);

namespace DomainTool;

final class DropEstimator
{
	private array $dropWindows;
	private array $lifecycleDays;

	public function __construct(array $dropWindows, array $lifecycleDays)
	{
		$this->dropWindows = $dropWindows;
		$this->lifecycleDays = $lifecycleDays;
	}

	public function estimateDropDateUtc(string $tld, array $parsed, string $phase): array
	{
		$expiryUtc = $parsed['expiry_utc'] ?? null;
		$window = $this->dropWindows[$tld] ?? $this->dropWindows['default'] ?? ['start' => '14:00', 'end' => '15:00'];
		$lifecycle = $this->lifecycleDays[$tld] ?? $this->lifecycleDays['default'] ?? [
			'auto_renew_grace' => 0,
			'redemption' => 30,
			'pending_delete' => 5,
		];

		$estimated = [ 'date_utc' => null, 'window_utc' => $window['start'] . '-' . $window['end'] ];

		if ($phase === 'pendingDelete' && $expiryUtc !== null) {
			// If we are sure it is pending delete but expiry is known, rough estimate: expiry + grace + redemption + pending
			$days = (int)$lifecycle['auto_renew_grace'] + (int)$lifecycle['redemption'] + (int)$lifecycle['pending_delete'];
			$ts = strtotime($expiryUtc . ' UTC') + $days * 86400;
			$estimated['date_utc'] = gmdate('Y-m-d', $ts) . ' ' . $window['start'];
			return $estimated;
		}

		if ($expiryUtc !== null) {
			// General estimation from expiry date
			$days = (int)$lifecycle['auto_renew_grace'] + (int)$lifecycle['redemption'] + (int)$lifecycle['pending_delete'];
			$ts = strtotime($expiryUtc . ' UTC') + $days * 86400;
			$estimated['date_utc'] = gmdate('Y-m-d', $ts) . ' ' . $window['start'];
			return $estimated;
		}

		return $estimated;
	}
}


<?php

namespace DomainScout\Domain;

final class DropEstimator
{
	public static function estimate(string $tld, DomainInfo $info): void
	{
		$tld = strtolower($tld);
		$statuses = array_map('strtolower', $info->statuses);
		$hasPendingDelete = self::containsStatus($statuses, 'pendingdelete');
		$hasRedemption = self::containsStatus($statuses, 'redemption');

		if ($info->isAvailable) {
			$info->dropEstimatedAt = null;
			$info->dropEstimateConfidence = 'n/a';
			return;
		}

		if ($hasPendingDelete) {
			$base = $info->updatedAt ?? $info->expiresAt;
			if ($base) {
				$info->dropEstimatedAt = $base->modify('+5 days');
				$info->dropEstimateConfidence = 'high';
				return;
			}
		}

		if ($hasRedemption) {
			$base = $info->updatedAt ?? $info->expiresAt;
			if ($base) {
				$info->dropEstimatedAt = $base->modify('+30 days');
				$info->dropEstimateConfidence = 'medium';
				return;
			}
		}

		if ($info->expiresAt) {
			$days = in_array($tld, ['com','net','org'], true) ? 75 : 60;
			$info->dropEstimatedAt = $info->expiresAt->modify("+{$days} days");
			$info->dropEstimateConfidence = 'low';
			return;
		}

		$info->dropEstimatedAt = null;
		$info->dropEstimateConfidence = 'unknown';
	}

	private static function containsStatus(array $statuses, string $needle): bool
	{
		foreach ($statuses as $status) {
			if (str_contains($status, $needle)) {
				return true;
			}
		}
		return false;
	}
}

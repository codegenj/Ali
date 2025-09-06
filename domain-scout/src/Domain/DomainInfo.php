<?php

namespace DomainScout\Domain;

final class DomainInfo
{
	public string $domain;
	public string $tld;
	public bool $isAvailable = false;
	/** @var string[] */
	public array $statuses = [];
	public ?\DateTimeImmutable $createdAt = null;
	public ?\DateTimeImmutable $updatedAt = null;
	public ?\DateTimeImmutable $expiresAt = null;
	public ?\DateTimeImmutable $dropEstimatedAt = null;
	public string $dropEstimateConfidence = 'unknown';
	public string $whoisServer = '';
	public string $raw = '';

	public function __construct(string $domain, string $tld)
	{
		$this->domain = $domain;
		$this->tld = $tld;
	}

	public function toArray(): array
	{
		return [
			'domain' => $this->domain,
			'tld' => $this->tld,
			'available' => $this->isAvailable,
			'statuses' => $this->statuses,
			'createdAt' => $this->createdAt?->format(DATE_ATOM),
			'updatedAt' => $this->updatedAt?->format(DATE_ATOM),
			'expiresAt' => $this->expiresAt?->format(DATE_ATOM),
			'dropEstimatedAt' => $this->dropEstimatedAt?->format(DATE_ATOM),
			'dropEstimateConfidence' => $this->dropEstimateConfidence,
			'whoisServer' => $this->whoisServer,
		];
	}
}

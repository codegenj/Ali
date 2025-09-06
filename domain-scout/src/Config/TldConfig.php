<?php

namespace DomainScout\Config;

final class TldConfig
{
	private array $config;

	public function __construct(string $configFile)
	{
		if (!is_file($configFile)) {
			throw new \RuntimeException("TLD config not found: {$configFile}");
		}
		$config = require $configFile;
		if (!is_array($config)) {
			throw new \RuntimeException('Invalid TLD config format');
		}
		$this->config = $config;
	}

	public function getAllTlds(): array
	{
		return array_keys($this->config);
	}

	public function get(string $tld): array
	{
		$tld = strtolower($tld);
		if (!isset($this->config[$tld])) {
			throw new \InvalidArgumentException("Unsupported TLD: {$tld}");
		}
		return $this->config[$tld];
	}
}

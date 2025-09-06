<?php

namespace DomainScout\Whois;

use DomainScout\Config\TldConfig;
use DomainScout\Domain\DomainInfo;
use DomainScout\Domain\DropEstimator;

final class WhoisService
{
    public function __construct(
        private readonly TldConfig $tldConfig,
        private readonly Client $client
    ) {}

    /**
     * @param string $fqdn e.g. example.com
     */
    public function check(string $fqdn): DomainInfo
    {
        $fqdn = strtolower(trim($fqdn));
        [$domain, $tld] = $this->splitDomain($fqdn);
        $config = $this->tldConfig->get($tld);

        $whoisServer = $config['host'];
        $queryFmt = $config['query'] ?? '%s';
        $notFound = $config['not_found'] ?? '';
        $parserKey = $config['parser'] ?? 'verisign';

        $raw = $this->client->query($whoisServer, $fqdn, $queryFmt);

        if ($this->shouldFollowReferral($raw)) {
            $refServer = $this->extractReferralServer($raw);
            if ($refServer) {
                $whoisServer = $refServer;
                $raw = $this->client->query($whoisServer, $fqdn, $queryFmt);
            }
        }

        $parser = ParserFactory::forKey($parserKey);
        $info = $parser->parse($domain, $tld, $whoisServer, $raw, $notFound);
        DropEstimator::estimate($tld, $info);
        return $info;
    }

    /**
     * @param string[] $fqdns
     * @return DomainInfo[]
     */
    public function checkMany(array $fqdns): array
    {
        $results = [];
        foreach ($fqdns as $fqdn) {
            try {
                $results[] = $this->check($fqdn);
            } catch (\Throwable $e) {
                $info = new DomainInfo($fqdn, '');
                $info->raw = 'ERROR: ' . $e->getMessage();
                $results[] = $info;
            }
        }
        return $results;
    }

    private function splitDomain(string $fqdn): array
    {
        $parts = explode('.', $fqdn);
        if (count($parts) < 2) {
            throw new \InvalidArgumentException("Invalid domain: {$fqdn}");
        }
        // support multi-part tlds like com.tr
        $lastTwo = implode('.', array_slice($parts, -2));
        if ($this->hasTld($lastTwo)) {
            return [implode('.', array_slice($parts, 0, -2)), $lastTwo];
        }
        $lastOne = end($parts);
        return [implode('.', array_slice($parts, 0, -1)), $lastOne];
    }

    private function hasTld(string $tld): bool
    {
        return in_array($tld, $this->tldConfig->getAllTlds(), true);
    }

    private function shouldFollowReferral(string $raw): bool
    {
        return (bool)preg_match('/Whois Server:\s*([^\s]+)/i', $raw);
    }

    private function extractReferralServer(string $raw): ?string
    {
        if (preg_match('/Whois Server:\s*([^\s]+)/i', $raw, $m)) {
            return trim($m[1]);
        }
        return null;
    }
}


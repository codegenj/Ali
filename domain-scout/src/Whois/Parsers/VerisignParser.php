<?php

namespace DomainScout\Whois\Parsers;

use DomainScout\Domain\DomainInfo;

final class VerisignParser implements ParserInterface
{
    public function parse(string $domain, string $tld, string $whoisServer, string $whoisRaw, string $notFoundPattern): DomainInfo
    {
        $info = new DomainInfo($domain, $tld);
        $info->whoisServer = $whoisServer;
        $info->raw = $whoisRaw;

        if ($notFoundPattern !== '' && preg_match($notFoundPattern, $whoisRaw)) {
            $info->isAvailable = true;
            return $info;
        }

        $info->isAvailable = false;
        $info->statuses = $this->parseStatuses($whoisRaw);
        $info->createdAt = $this->parseDate($whoisRaw, '/Creation Date:\s*(.+)/i');
        $info->updatedAt = $this->parseDate($whoisRaw, '/Updated Date:\s*(.+)/i');
        $info->expiresAt = $this->parseDate($whoisRaw, '/Registry Expiry Date:\s*(.+)/i')
            ?? $this->parseDate($whoisRaw, '/Expiry Date:\s*(.+)/i');

        return $info;
    }

    private function parseStatuses(string $raw): array
    {
        $statuses = [];
        if (preg_match_all('/Domain Status:\s*([^\s]+).*$/mi', $raw, $m)) {
            foreach ($m[1] as $status) {
                $statuses[] = trim($status);
            }
        }
        return array_values(array_unique($statuses));
    }

    private function parseDate(string $raw, string $pattern): ?\DateTimeImmutable
    {
        if (preg_match($pattern, $raw, $m)) {
            $str = trim($m[1]);
            try {
                return new \DateTimeImmutable($str);
            } catch (\Throwable $e) {
                return null;
            }
        }
        return null;
    }
}


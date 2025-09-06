<?php

namespace DomainScout\Whois\Parsers;

use DomainScout\Domain\DomainInfo;

interface ParserInterface
{
    /**
     * Parse a WHOIS response into DomainInfo. Implementations should be resilient to minor format changes.
     */
    public function parse(string $domain, string $tld, string $whoisServer, string $whoisRaw, string $notFoundPattern): DomainInfo;
}


<?php

namespace DomainScout\Whois;

use DomainScout\Whois\Parsers\ParserInterface;
use DomainScout\Whois\Parsers\VerisignParser;
use DomainScout\Whois\Parsers\PirParser;
use DomainScout\Whois\Parsers\TrabisParser;

final class ParserFactory
{
    public static function forKey(string $key): ParserInterface
    {
        return match (strtolower($key)) {
            'verisign' => new VerisignParser(),
            'pir' => new PirParser(),
            'trabis' => new TrabisParser(),
            default => new VerisignParser(),
        };
    }
}


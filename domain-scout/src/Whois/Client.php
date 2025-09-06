<?php

namespace DomainScout\Whois;

final class Client
{
    private int $timeoutSeconds;
    private int $sleepBetweenQueriesMs;

    public function __construct(int $timeoutSeconds = 10, int $sleepBetweenQueriesMs = 250)
    {
        $this->timeoutSeconds = $timeoutSeconds;
        $this->sleepBetweenQueriesMs = $sleepBetweenQueriesMs;
    }

    public function query(string $whoisServer, string $domain, ?string $queryFormat = null): string
    {
        $query = $queryFormat ? sprintf($queryFormat, $domain) : $domain;
        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($whoisServer, 43, $errno, $errstr, $this->timeoutSeconds);
        if (!$fp) {
            throw new \RuntimeException("WHOIS connect failed to {$whoisServer}: {$errstr}");
        }

        stream_set_timeout($fp, $this->timeoutSeconds);
        fwrite($fp, $query . "\r\n");
        $response = '';
        while (!feof($fp)) {
            $chunk = fgets($fp, 4096);
            if ($chunk === false) {
                break;
            }
            $response .= $chunk;
        }
        fclose($fp);

        // rate limit between queries
        if ($this->sleepBetweenQueriesMs > 0) {
            usleep($this->sleepBetweenQueriesMs * 1000);
        }

        return $response;
    }
}


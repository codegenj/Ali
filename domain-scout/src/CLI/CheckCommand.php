<?php

namespace DomainScout\CLI;

use DomainScout\Config\TldConfig;
use DomainScout\Whois\Client;
use DomainScout\Whois\WhoisService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'check', description: 'Check domain availability and estimate drop times')]
final class CheckCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('domains', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Domain list (space separated)')
            ->addOption('file', 'f', InputOption::VALUE_REQUIRED, 'Path to file with domains (one per line)')
            ->addOption('tlds', 't', InputOption::VALUE_REQUIRED, 'Comma-separated TLDs to filter, e.g. com,net,org')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: table|csv|json', 'table')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'Path to tlds.php', __DIR__ . '/../../config/tlds.php')
            ->addOption('timeout', null, InputOption::VALUE_REQUIRED, 'WHOIS timeout seconds', '10')
            ->addOption('sleep', null, InputOption::VALUE_REQUIRED, 'Sleep between queries in ms', '250');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $domains = (array)$input->getArgument('domains');
        $file = $input->getOption('file');
        $tldsFilter = $input->getOption('tlds');
        $format = strtolower((string)$input->getOption('format'));
        $configPath = (string)$input->getOption('config');
        $timeout = (int)$input->getOption('timeout');
        $sleep = (int)$input->getOption('sleep');

        if ($file) {
            if (!is_file($file)) {
                $output->writeln("<error>File not found: {$file}</error>");
                return Command::FAILURE;
            }
            $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
            $domains = array_merge($domains, $lines);
        }
        $domains = array_values(array_unique(array_map('trim', array_filter($domains))));
        if (empty($domains)) {
            $output->writeln('<comment>No domains provided</comment>');
            return Command::SUCCESS;
        }

        $tldConfig = new TldConfig($configPath);
        if ($tldsFilter) {
            $wanted = array_map('strtolower', array_map('trim', explode(',', (string)$tldsFilter)));
            $domains = array_filter($domains, function (string $fqdn) use ($wanted) {
                $fqdn = strtolower($fqdn);
                foreach ($wanted as $tld) {
                    if (str_ends_with($fqdn, '.' . $tld) || $fqdn === $tld) {
                        return true;
                    }
                }
                return false;
            });
            $domains = array_values($domains);
        }

        $service = new WhoisService($tldConfig, new Client($timeout, $sleep));
        $results = $service->checkMany($domains);

        return match ($format) {
            'json' => $this->renderJson($results, $output),
            'csv' => $this->renderCsv($results, $output),
            default => $this->renderTable($results, $output),
        };
    }

    private function renderJson(array $results, OutputInterface $output): int
    {
        $data = array_map(fn($r) => $r->toArray(), $results);
        $output->writeln(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return Command::SUCCESS;
    }

    private function renderCsv(array $results, OutputInterface $output): int
    {
        $fh = fopen('php://temp', 'w+');
        fputcsv($fh, ['domain','tld','available','createdAt','updatedAt','expiresAt','dropEstimatedAt','confidence','whoisServer']);
        foreach ($results as $r) {
            fputcsv($fh, [
                $r->domain,
                $r->tld,
                $r->isAvailable ? '1' : '0',
                $r->createdAt?->format(DATE_ATOM),
                $r->updatedAt?->format(DATE_ATOM),
                $r->expiresAt?->format(DATE_ATOM),
                $r->dropEstimatedAt?->format(DATE_ATOM),
                $r->dropEstimateConfidence,
                $r->whoisServer,
            ]);
        }
        rewind($fh);
        $output->write(stream_get_contents($fh) ?: '');
        fclose($fh);
        return Command::SUCCESS;
    }

    private function renderTable(array $results, OutputInterface $output): int
    {
        $lines = [];
        $lines[] = sprintf("%-30s %-8s %-10s %-20s %-20s %-20s %-20s %-10s", 'Domain', 'TLD', 'Avail', 'Created', 'Updated', 'Expires', 'Drop ETA', 'Conf');
        foreach ($results as $r) {
            $lines[] = sprintf(
                "%-30s %-8s %-10s %-20s %-20s %-20s %-20s %-10s",
                $r->domain,
                $r->tld,
                $r->isAvailable ? 'yes' : 'no',
                $r->createdAt?->format('Y-m-d') ?? '-',
                $r->updatedAt?->format('Y-m-d') ?? '-',
                $r->expiresAt?->format('Y-m-d') ?? '-',
                $r->dropEstimatedAt?->format('Y-m-d H:i') ?? '-',
                $r->dropEstimateConfidence
            );
        }
        $output->writeln($lines);
        return Command::SUCCESS;
    }
}


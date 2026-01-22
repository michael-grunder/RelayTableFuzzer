<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer;

use Mgrunder\RelayTableFuzzer\Console\Options;
use Monolog\Logger;
use Relay\Relay;
use Relay\Table;
use Throwable;

final class FuzzerRunner
{
    public function __construct(
        private readonly CommandGenerator $generator
    ) {
    }

    public function run(Options $options, Logger $logger): void
    {
        if ($options->mode === 'queue') {
            $this->runQueueMode($options, $logger);
            return;
        }

        if ($options->mode !== 'random') {
            throw new \RuntimeException("Unknown --mode: {$options->mode}. Use random or queue.");
        }

        $this->runRandomMode($options, $logger);
    }

    private function runRandomMode(Options $options, Logger $logger): void
    {
        if ($options->workers === 0) {
            $this->runRandomWorker(0, $options, $logger);
            return;
        }

        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('pcntl extension is required for workers > 0.');
        }

        $pids = [];
        for ($i = 0; $i < $options->workers; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                throw new \RuntimeException("Failed to fork worker {$i}.");
            }
            if ($pid === 0) {
                $this->runRandomWorker($i, $options, $logger);
                exit(0);
            }
            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $this->printStatus('All workers completed.');
    }

    private function runRandomWorker(int $workerId, Options $options, Logger $logger): void
    {
        mt_srand($options->seed + $workerId);
        $start = microtime(true);
        $lastReport = $start;
        $executed = 0;

        for ($i = 0; $i < $options->ops; $i++) {
            $cmd = $this->generator->generate(
                $options->opSet,
                $options->keys,
                $options->maxKeySize,
                $options->maxMems
            );
            try {
                $this->executeCommand($options->namespace, $cmd, $logger, $workerId);
            } catch (Throwable $e) {
                fwrite(STDERR, "worker {$workerId} error: {$e->getMessage()}\n");
            }
            $executed++;
            $now = microtime(true);
            if ($options->statusInterval > 0 && ($now - $lastReport) >= $options->statusInterval) {
                $percent = $this->formatPercent($executed, $options->ops);
                $message = sprintf(
                    'worker %d: %0.2f%% %d/%d commands executed',
                    $workerId,
                    $percent,
                    $executed,
                    $options->ops
                );
                $this->printStatus($message . $this->formatMemoryStatusSuffix());
                $lastReport = $now;
            }
        }

        $elapsed = microtime(true) - $start;
        $rate = $elapsed > 0 ? ($executed / $elapsed) : 0.0;
        $this->printStatus(sprintf(
            'worker %d done: %d commands in %0.2fs (%0.1f ops/s)',
            $workerId,
            $executed,
            $elapsed,
            $rate
        ));
    }

    private function runQueueMode(Options $options, Logger $logger): void
    {
        if (!class_exists('Redis')) {
            throw new \RuntimeException('Redis extension is required for --mode=queue.');
        }

        [$host, $port] = $this->parseHostPort($options->redisSpec);
        $redis = new \Redis();
        if (!$redis->connect($host, $port, 2.5)) {
            throw new \RuntimeException("Failed to connect to Redis at {$host}:{$port}.");
        }

        $queueName = $options->listName . ':' . $options->seed;

        mt_srand($options->seed);
        for ($i = 0; $i < $options->ops; $i++) {
            $cmd = $this->generator->generate(
                $options->opSet,
                $options->keys,
                $options->maxKeySize,
                $options->maxMems
            );
            $redis->rPush($queueName, json_encode($cmd, JSON_UNESCAPED_SLASHES));
        }

        if ($options->workers === 0) {
            $this->runQueueConsumer($redis, $queueName, 0, $options, $logger);
            return;
        }

        if (!function_exists('pcntl_fork')) {
            throw new \RuntimeException('pcntl extension is required for workers > 0.');
        }

        $pids = [];
        for ($i = 0; $i < $options->workers; $i++) {
            $pid = pcntl_fork();
            if ($pid === -1) {
                throw new \RuntimeException("Failed to fork worker {$i}.");
            }
            if ($pid === 0) {
                $this->runQueueConsumer($redis, $queueName, $i, $options, $logger);
                exit(0);
            }
            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $this->printStatus('All workers completed.');
    }

    private function runQueueConsumer(\Redis $redis, string $queueName, int $workerId, Options $options, Logger $logger): void
    {
        $start = microtime(true);
        $lastReport = $start;
        $executed = 0;

        while (true) {
            $payload = $redis->lPop($queueName);
            if ($payload === false) {
                break;
            }
            $cmd = json_decode($payload, true);
            if (!is_array($cmd)) {
                continue;
            }
            try {
                $this->executeCommand($options->namespace, $cmd, $logger, $workerId);
            } catch (Throwable $e) {
                fwrite(STDERR, "worker {$workerId} error: {$e->getMessage()}\n");
            }
            $executed++;
            $now = microtime(true);
            if ($options->statusInterval > 0 && ($now - $lastReport) >= $options->statusInterval) {
                $percent = $this->formatPercent($executed, $options->ops);
                $message = sprintf(
                    'worker %d: %0.2f%% %d/%d commands executed',
                    $workerId,
                    $percent,
                    $executed,
                    $options->ops
                );
                $this->printStatus($message . $this->formatMemoryStatusSuffix());
                $lastReport = $now;
            }
        }

        $elapsed = microtime(true) - $start;
        $rate = $elapsed > 0 ? ($executed / $elapsed) : 0.0;
        $this->printStatus(sprintf(
            'worker %d done: %d commands in %0.2fs (%0.1f ops/s)',
            $workerId,
            $executed,
            $elapsed,
            $rate
        ));
    }

    private function executeCommand(string $namespace, array $cmd, Logger $logger, int $workerId): void
    {
        $op = $cmd['op'] ?? '';
        $logger->debug('Executing command', [
            'worker' => $workerId,
            'cmd' => $cmd,
        ]);

        switch ($op) {
            case 'get':
                Table::get((string) $cmd['key'], $namespace);
                break;
            case 'set':
                Table::set(
                    (string) $cmd['key'],
                    $cmd['value'] ?? null,
                    $cmd['expire'] ?? null,
                    $namespace
                );
                break;
            case 'exists':
                Table::exists((string) $cmd['key'], $namespace);
                break;
            case 'delete':
                Table::delete((string) $cmd['key'], $namespace);
                break;
            case 'ttl':
                Table::ttl((string) $cmd['key'], $namespace);
                break;
            case 'count':
                Table::count($namespace);
                break;
            case 'clear':
                Table::clear($namespace);
                break;
            case 'namespaces':
                Table::namespaces();
                break;
            case 'clearAll':
                Table::clearAll();
                break;
            default:
                break;
        }
    }

    private function printStatus(string $message): void
    {
        fwrite(STDOUT, $message . "\n");
    }

    private function formatPercent(int $done, int $total): float
    {
        if ($total <= 0) {
            return 0.0;
        }
        return min(100.0, ($done / $total) * 100.0);
    }

    private function formatMemoryStatusSuffix(): string
    {
        if (!class_exists(Relay::class)) {
            return '';
        }

        try {
            $stats = Relay::stats();
        } catch (Throwable) {
            return '';
        }

        $memory = $stats['memory'] ?? null;
        if (!is_array($memory) || !isset($memory['used'], $memory['total'])) {
            return '';
        }

        return sprintf(' mem %d/%d', (int) $memory['used'], (int) $memory['total']);
    }

    private function parseHostPort(string $spec): array
    {
        $parts = explode(':', $spec, 2);
        $host = $parts[0] !== '' ? $parts[0] : '127.0.0.1';
        $port = isset($parts[1]) && $parts[1] !== '' ? (int) $parts[1] : 6379;
        return [$host, $port];
    }
}

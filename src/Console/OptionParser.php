<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer\Console;

use RuntimeException;

final class OptionParser
{
    private const ALL_OPS = [
        'get', 'set', 'exists', 'delete', 'ttl', 'count', 'clear', 'namespaces', 'clearAll',
    ];

    /**
     * @param array<string, string|int|float|bool|null> $options
     */
    public static function parse(array $options, string $invocation): Options
    {
        $workers = isset($options['workers']) ? (int) $options['workers'] : 0;
        $ops = (int) $options['ops'];
        $seed = isset($options['seed']) ? (int) $options['seed'] : (int) hrtime(true);
        $keys = isset($options['keys']) ? (int) $options['keys'] : 16;
        $namespaces = isset($options['namespaces']) ? (int) $options['namespaces'] : 1;
        $maxKeySize = isset($options['max-key-size']) ? (int) $options['max-key-size'] : 24;
        $maxMems = isset($options['max-mems']) ? (int) $options['max-mems'] : 4;
        $mode = isset($options['mode']) ? strtolower((string) $options['mode']) : 'random';
        $redisSpec = isset($options['redis']) ? (string) $options['redis'] : '127.0.0.1:6379';
        $listName = isset($options['list']) ? (string) $options['list'] : 'relay-table-fuzzer';
        $logLevel = isset($options['log-level']) ? strtolower((string) $options['log-level']) : 'info';
        $statusInterval = isset($options['status-interval']) ? (float) $options['status-interval'] : 1.0;

        if ($ops <= 0 || $keys <= 0 || $namespaces <= 0 || $maxKeySize <= 0 || $maxMems <= 0 || $workers < 0 || $statusInterval <= 0) {
            throw new RuntimeException('Invalid --ops, --keys, --namespaces, --max-key-size, --max-mems, --workers, or --status-interval value.');
        }

        $includeRaw = isset($options['include']) ? (string) $options['include'] : '';
        $excludeRaw = isset($options['exclude']) ? (string) $options['exclude'] : '';

        $include = self::parseCsl($includeRaw);
        $exclude = self::parseCsl($excludeRaw);

        $opSet = self::ALL_OPS;
        if ($include !== []) {
            $opSet = array_values(array_intersect($opSet, $include));
        }
        if ($exclude !== []) {
            $opSet = array_values(array_diff($opSet, $exclude));
        }

        if ($opSet === []) {
            throw new RuntimeException('No operations selected after include/exclude filtering.');
        }

        $seedSource = isset($options['seed']) ? 'provided' : 'generated';
        $rerunCommand = self::buildRerunCommand(
            $invocation,
            $ops,
            $seed,
            $workers,
            $keys,
            $namespaces,
            $maxKeySize,
            $maxMems,
            $mode,
            $logLevel,
            $redisSpec,
            $listName,
            $statusInterval,
            $includeRaw,
            $excludeRaw,
            isset($options['status-interval'])
        );

        return new Options(
            $workers,
            $ops,
            $seed,
            $keys,
            $namespaces,
            $maxKeySize,
            $maxMems,
            $mode,
            $redisSpec,
            $listName,
            $logLevel,
            $statusInterval,
            $include,
            $exclude,
            $opSet,
            $seedSource,
            $rerunCommand,
            $includeRaw,
            $excludeRaw
        );
    }

    /**
     * @return list<string>
     */
    private static function parseCsl(string $value): array
    {
        if ($value === '') {
            return [];
        }
        $items = array_map('trim', explode(',', $value));
        return array_values(array_filter($items, static fn($item) => $item !== ''));
    }

    private static function buildRerunCommand(
        string $invocation,
        int $ops,
        int $seed,
        int $workers,
        int $keys,
        int $namespaces,
        int $maxKeySize,
        int $maxMems,
        string $mode,
        string $logLevel,
        string $redisSpec,
        string $listName,
        float $statusInterval,
        string $includeRaw,
        string $excludeRaw,
        bool $statusIntervalProvided
    ): string {
        $rerunParts = [
            $invocation,
            '--ops', (string) $ops,
            '--seed', (string) $seed,
            '--workers', (string) $workers,
            '--keys', (string) $keys,
            '--namespaces', (string) $namespaces,
            '--max-key-size', (string) $maxKeySize,
            '--max-mems', (string) $maxMems,
            '--mode', $mode,
            '--log-level', $logLevel,
        ];
        if ($includeRaw !== '') {
            $rerunParts[] = '--include';
            $rerunParts[] = $includeRaw;
        }
        if ($excludeRaw !== '') {
            $rerunParts[] = '--exclude';
            $rerunParts[] = $excludeRaw;
        }
        if ($mode === 'queue') {
            $rerunParts[] = '--redis';
            $rerunParts[] = $redisSpec;
            $rerunParts[] = '--list';
            $rerunParts[] = $listName;
        }
        if ($statusIntervalProvided) {
            $rerunParts[] = '--status-interval';
            $rerunParts[] = (string) $statusInterval;
        }
        return implode(' ', array_map('escapeshellarg', $rerunParts));
    }
}

<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer\Console;

final class Options
{
    /**
     * @param list<string> $include
     * @param list<string> $exclude
     * @param list<string> $opSet
     */
    public function __construct(
        public readonly int $workers,
        public readonly int $ops,
        public readonly int $seed,
        public readonly int $keys,
        public readonly int $namespaces,
        public readonly int $maxKeySize,
        public readonly int $maxMems,
        public readonly string $mode,
        public readonly string $redisSpec,
        public readonly string $listName,
        public readonly string $logLevel,
        public readonly float $statusInterval,
        public readonly array $include,
        public readonly array $exclude,
        public readonly array $opSet,
        public readonly string $seedSource,
        public readonly string $rerunCommand,
        public readonly string $includeRaw,
        public readonly string $excludeRaw
    ) {
    }
}

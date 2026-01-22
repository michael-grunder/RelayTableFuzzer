<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer\Console;

final class Usage
{
    public static function fuzzer(): string
    {
        return <<<'TXT'
Relay Table fuzzer

Usage:
  ./bin/fuzzer --ops N [--workers N] [--seed INT] [--keys N]
               [--max-key-size N] [--max-mems N]
               [--include csl] [--exclude csl]
               [--mode random|queue] [--redis host:port] [--list name]
               [--log-level level] [--status-interval SECONDS]

Notes:
  - If --seed is omitted, hrtime(true) is used.
  - If --workers=0, no fork is performed.
  - --mode=queue uses Redis; parent enqueues commands to a list and children pop.
  - --log-level accepts Monolog/PSR-3 levels (debug, info, notice, warning, error, critical, alert, emergency).
  - --status-interval controls how often human-readable progress is printed (default: 1).

TXT;
    }

    public static function generator(): string
    {
        return <<<'TXT'
Relay Table fuzzer script generator

Usage:
  ./bin/fuzzer-generate --ops N [--workers N] [--seed INT] [--keys N]
                        [--max-key-size N] [--max-mems N]
                        [--include csl] [--exclude csl]
                        [--mode random|queue]

Notes:
  - If --seed is omitted, hrtime(true) is used.
  - --mode=queue generates a single deterministic command stream (no Redis).
  - Output is a standalone PHP script written to stdout.

TXT;
    }
}

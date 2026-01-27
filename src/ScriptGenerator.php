<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer;

use Mgrunder\RelayTableFuzzer\Console\Options;

final class ScriptGenerator
{
    public function __construct(
        private readonly CommandGenerator $generator
    ) {
    }

    public function generate(Options $options, array $contextLines = []): string
    {
        $lines = [];
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'use Relay\\Table;';
        $lines[] = '';
        if ($contextLines !== []) {
            foreach ($contextLines as $line) {
                $line = rtrim((string) $line);
                if ($line === '') {
                    $lines[] = '//';
                    continue;
                }
                $lines[] = '// ' . $line;
            }
            $lines[] = '';
        }
        $lines[] = sprintf('// seed: %d (%s)', $options->seed, $options->seedSource);
        $lines[] = sprintf(
            '// ops: %d, workers: %d, keys: %d, max-key-size: %d, max-mems: %d, mode: %s',
            $options->ops,
            $options->workers,
            $options->keys,
            $options->maxKeySize,
            $options->maxMems,
            $options->mode
        );
        $lines[] = sprintf('// namespace: %s', $options->namespace);
        $lines[] = '';
        $lines[] = sprintf('$namespace = %s;', var_export($options->namespace, true));
        $lines[] = '';

        if ($options->mode === 'queue') {
            $this->appendQueueCommands($lines, $options);
        } elseif ($options->mode === 'random') {
            $this->appendRandomCommands($lines, $options);
        } else {
            throw new \RuntimeException("Unknown --mode: {$options->mode}. Use random or queue.");
        }

        $lines[] = '';
        return implode("\n", $lines) . "\n";
    }

    private function appendQueueCommands(array &$lines, Options $options): void
    {
        $lines[] = '// queue mode command stream';
        mt_srand($options->seed);
        for ($i = 0; $i < $options->ops; $i++) {
            $cmd = $this->generator->generate(
                $options->opSet,
                $options->keys,
                $options->maxKeySize,
                $options->maxMems
            );
            $lines[] = $this->commandToPhp($cmd);
        }
    }

    private function appendRandomCommands(array &$lines, Options $options): void
    {
        $workers = $options->workers > 0 ? $options->workers : 1;
        for ($workerId = 0; $workerId < $workers; $workerId++) {
            $lines[] = sprintf('// worker %d sequence', $workerId);
            mt_srand($options->seed + $workerId);
            for ($i = 0; $i < $options->ops; $i++) {
                $cmd = $this->generator->generate(
                    $options->opSet,
                    $options->keys,
                    $options->maxKeySize,
                    $options->maxMems
                );
                $lines[] = $this->commandToPhp($cmd);
            }
            $lines[] = '';
        }
    }

    private function commandToPhp(array $cmd): string
    {
        $op = $cmd['op'] ?? '';

        switch ($op) {
            case 'get':
                return sprintf('Table::get(%s, $namespace);', var_export((string) $cmd['key'], true));
            case 'set':
                return sprintf('Table::set(%s, %s, %s, $namespace);',
                    var_export((string) $cmd['key'], true),
                    var_export($cmd['value'] ?? null, true),
                    var_export($cmd['expire'] ?? null, true)
                );
            case 'exists':
                return sprintf('Table::exists(%s, $namespace);', var_export((string) $cmd['key'], true));
            case 'delete':
                return sprintf('Table::delete(%s, $namespace);', var_export((string) $cmd['key'], true));
            case 'ttl':
                return sprintf('Table::ttl(%s, $namespace);', var_export((string) $cmd['key'], true));
            case 'count':
                return 'Table::count($namespace);';
            case 'clear':
                return 'Table::clear($namespace);';
            case 'namespaces':
                return 'Table::namespaces();';
            case 'clearAll':
                return 'Table::clearAll();';
            default:
                return '// unknown op skipped';
        }
    }
}

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

    public function generate(Options $options): string
    {
        $lines = [];
        $lines[] = '#!/usr/bin/env php';
        $lines[] = '<?php';
        $lines[] = '';
        $lines[] = 'declare(strict_types=1);';
        $lines[] = '';
        $lines[] = 'use Relay\\Table;';
        $lines[] = '';
        $lines[] = sprintf('// seed: %d (%s)', $options->seed, $options->seedSource);
        $lines[] = sprintf('// ops: %d, workers: %d, keys: %d, mode: %s', $options->ops, $options->workers, $options->keys, $options->mode);
        $lines[] = sprintf('// namespace: %s', $options->namespace);
        $lines[] = '';
        $lines[] = sprintf('$table = new Table(%s);', var_export($options->namespace, true));
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
            $cmd = $this->generator->generate($options->opSet, $options->keys);
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
                $cmd = $this->generator->generate($options->opSet, $options->keys);
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
                return sprintf('$table->get(%s);', var_export((string) $cmd['key'], true));
            case 'pluck':
                return sprintf(
                    '$table->pluck(%s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export((string) $cmd['field'], true)
                );
            case 'set':
                return sprintf(
                    '$table->set(%s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export($cmd['value'] ?? null, true)
                );
            case 'exists':
                return sprintf('$table->exists(%s);', var_export((string) $cmd['key'], true));
            case 'delete':
                return sprintf('$table->delete(%s);', var_export((string) $cmd['key'], true));
            case 'count':
                return '$table->count();';
            case 'namespace':
                return '$table->namespace();';
            case 'clear':
                return '$table->clear();';
            case 'namespaces':
                return 'Table::namespaces();';
            case 'clearAll':
                return 'Table::clearAll();';
            default:
                return '// unknown op skipped';
        }
    }
}

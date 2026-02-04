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

    /**
     * @param list<string> $contextLines
     */
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
        $lines[] = sprintf('// namespaces: %d', $options->namespaces);
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

    /**
     * @param list<string> $lines
     */
    private function appendQueueCommands(array &$lines, Options $options): void
    {
        $lines[] = '// queue mode command stream';
        mt_srand($options->seed);
        for ($i = 0; $i < $options->ops; $i++) {
            $cmd = $this->generator->generate(
                $options->opSet,
                $options->keys,
                $options->namespaces,
                $options->maxKeySize,
                $options->maxMems
            );
            $lines[] = $this->commandToPhp($cmd);
        }
    }

    /**
     * @param list<string> $lines
     */
    private function appendRandomCommands(array &$lines, Options $options): void
    {
        $workers = $options->workers > 0 ? $options->workers : 1;
        $sequences = $this->buildRandomCommandSequences($options, $workers);

        if ($options->workers <= 0) {
            $lines[] = '// worker 0 sequence';
            foreach ($sequences[0] as $command) {
                $lines[] = $command;
            }
            return;
        }

        $lines[] = 'function work(int $workerId): void';
        $lines[] = '{';
        $lines[] = '    switch ($workerId) {';
        foreach ($sequences as $workerId => $commands) {
            $lines[] = sprintf('        case %d:', $workerId);
            $lines[] = sprintf('            // worker %d sequence', $workerId);
            foreach ($commands as $command) {
                $lines[] = '            ' . $command;
            }
            $lines[] = '            break;';
        }
        $lines[] = '        default:';
        $lines[] = '            return;';
        $lines[] = '    }';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = 'if (!function_exists(\'pcntl_fork\')) {';
        $lines[] = '    fwrite(STDERR, "pcntl extension is required for workers > 0.\n");';
        $lines[] = '    exit(1);';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = '$pids = [];';
        $lines[] = sprintf('for ($i = 0; $i < %d; $i++) {', $options->workers);
        $lines[] = '    $pid = pcntl_fork();';
        $lines[] = '    if ($pid === -1) {';
        $lines[] = '        fwrite(STDERR, "Failed to fork worker {$i}.\n");';
        $lines[] = '        exit(1);';
        $lines[] = '    }';
        $lines[] = '    if ($pid === 0) {';
        $lines[] = '        work($i);';
        $lines[] = '        exit(0);';
        $lines[] = '    }';
        $lines[] = '    $pids[] = $pid;';
        $lines[] = '}';
        $lines[] = '';
        $lines[] = 'foreach ($pids as $pid) {';
        $lines[] = '    pcntl_waitpid($pid, $status);';
        $lines[] = '}';
    }

    /**
     * @return array<int, list<string>>
     */
    private function buildRandomCommandSequences(Options $options, int $workers): array
    {
        $sequences = [];
        for ($workerId = 0; $workerId < $workers; $workerId++) {
            mt_srand($options->seed + $workerId);
            $commands = [];
            for ($i = 0; $i < $options->ops; $i++) {
                $cmd = $this->generator->generate(
                    $options->opSet,
                    $options->keys,
                    $options->namespaces,
                    $options->maxKeySize,
                    $options->maxMems
                );
                $commands[] = $this->commandToPhp($cmd);
            }
            $sequences[$workerId] = $commands;
        }

        return $sequences;
    }

    /**
     * @param array<string, mixed> $cmd
     */
    private function commandToPhp(array $cmd): string
    {
        $op = $cmd['op'] ?? '';
        $namespace = $cmd['namespace'] ?? '';

        switch ($op) {
            case 'get':
                return sprintf('Table::get(%s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export((string) $namespace, true)
                );
            case 'set':
                return sprintf('Table::set(%s, %s, %s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export($cmd['value'] ?? null, true),
                    var_export($cmd['expire'] ?? null, true),
                    var_export((string) $namespace, true)
                );
            case 'exists':
                return sprintf('Table::exists(%s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export((string) $namespace, true)
                );
            case 'delete':
                return sprintf('Table::delete(%s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export((string) $namespace, true)
                );
            case 'ttl':
                return sprintf('Table::ttl(%s, %s);',
                    var_export((string) $cmd['key'], true),
                    var_export((string) $namespace, true)
                );
            case 'count':
                return sprintf('Table::count(%s);', var_export((string) $namespace, true));
            case 'clear':
                return sprintf('Table::clear(%s);', var_export((string) $namespace, true));
            case 'namespaces':
                return 'Table::namespaces();';
            case 'clearAll':
                return 'Table::clearAll();';
            default:
                return '// unknown op skipped';
        }
    }
}

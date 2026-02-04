<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer;

final class CommandGenerator
{
    /**
     * @param list<string> $ops
     * @return array<string, mixed>
     */
    public function generate(array $ops, int $keys, int $namespaces, int $maxKeySize, int $maxMems): array
    {
        $op = $ops[mt_rand(0, count($ops) - 1)];
        $cmd = ['op' => $op];

        switch ($op) {
            case 'get':
            case 'exists':
            case 'delete':
            case 'ttl':
                $cmd['key'] = $this->pickKey($keys);
                break;
            case 'set':
                $cmd['key'] = $this->pickKey($keys);
                $cmd['value'] = $this->randomValue($maxKeySize, $maxMems);
                $expire = $this->randomExpire();
                if ($expire !== null) {
                    $cmd['expire'] = $expire;
                }
                break;
            case 'count':
            case 'clear':
            case 'namespaces':
            case 'clearAll':
                break;
            default:
                $cmd['op'] = 'get';
                $cmd['key'] = $this->pickKey($keys);
                break;
        }

        $effectiveOp = $cmd['op'] ?? $op;
        if (!in_array($effectiveOp, ['namespaces', 'clearAll'], true)) {
            $cmd['namespace'] = $this->pickNamespace($namespaces);
        }

        return $cmd;
    }

    private function pickKey(int $keys): string
    {
        $idx = mt_rand(0, $keys - 1);
        return 'key:' . $idx;
    }

    private function pickNamespace(int $namespaces): string
    {
        $idx = mt_rand(0, $namespaces - 1);
        return 'namespace:' . $idx;
    }

    private function randomValue(int $maxKeySize, int $maxMems): mixed
    {
        if (mt_rand(0, 4) < 4) {
            return $this->randomScalarValue($maxKeySize);
        }
        $fields = mt_rand(1, $maxMems);
        $arr = [];
        for ($i = 0; $i < $fields; $i++) {
            $arr['field:' . $i] = $this->randomScalarValue($maxKeySize);
        }
        return $arr;
    }

    private function randomScalarValue(int $maxKeySize): mixed
    {
        $choice = mt_rand(0, 4);
        switch ($choice) {
            case 0:
                return mt_rand(-100000, 100000);
            case 1:
                return mt_rand() / (mt_rand(1, 1000));
            case 2:
                return $this->randomString(mt_rand(1, $maxKeySize));
            case 3:
                return (bool) mt_rand(0, 1);
            case 4:
                return null;
            default:
                return null;
        }
    }

    private function randomString(int $len): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $max = strlen($chars) - 1;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $out .= $chars[mt_rand(0, $max)];
        }
        return $out;
    }

    private function randomExpire(): ?int
    {
        if (mt_rand(0, 2) === 0) {
            return null;
        }
        return mt_rand(1, 300);
    }
}

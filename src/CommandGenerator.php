<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer;

final class CommandGenerator
{
    public function generate(array $ops, int $keys): array
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
                $cmd['value'] = $this->randomValue();
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

        return $cmd;
    }

    private function pickKey(int $keys): string
    {
        $idx = mt_rand(0, $keys - 1);
        return 'key:' . $idx;
    }

    private function randomValue(): mixed
    {
        $choice = mt_rand(0, 3);
        switch ($choice) {
            case 0:
                return mt_rand(-100000, 100000);
            case 1:
                return mt_rand() / (mt_rand(1, 1000));
            case 2:
                return $this->randomString(mt_rand(1, 24));
            case 3:
                $fields = mt_rand(1, 4);
                $arr = [];
                for ($i = 0; $i < $fields; $i++) {
                    $arr['field:' . $i] = $this->randomString(mt_rand(1, 10));
                }
                return $arr;
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

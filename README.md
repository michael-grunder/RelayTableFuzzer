# Relay Table Fuzzer

Quick examples for running the fuzzer utilities in this repo.

## Run the fuzzer

```bash
./bin/fuzzer --ops 5000 --workers 4 --keys 200 --mode random
```

```bash
./bin/fuzzer --ops 2000 --seed 12345 --include set,get,del --exclude flushall
```

## Generate a standalone script

```bash
./bin/fuzzer-generate --ops 1000 --seed 4242 --workers 1 > /tmp/relay-fuzzer.php
```

```bash
php /tmp/relay-fuzzer.php
```

## Run with the crash harness

```bash
./bin/harness --php-bin ./php85d -- ./bin/fuzzer --ops 1000 --seed {hrtime} --keys {range(1,500)}
```

```bash
./bin/harness --php-bin /usr/bin/php --php-ini ./relay.ini -- ./bin/fuzzer --ops 1000 --mode random
```

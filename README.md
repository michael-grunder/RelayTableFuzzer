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
./bin/fuzzer --mode=script:random --ops 1000 --seed 4242 --workers 1 > /tmp/relay-fuzzer.php
```

```bash
php /tmp/relay-fuzzer.php
```

## Run with the crash harness

```bash
./bin/harness --php-bin ./php85d --ops 1000 --reduce -- ./bin/fuzzer --ops {ops} --seed {hrtime} --keys {range(1,500)}
```

To run the script-mode harness under valgrind, add `--mode=script --valgrind`:

```bash
./bin/harness --mode=script --valgrind --php-bin ./php85d --ops 1000 --reduce -- ./bin/fuzzer --ops {ops} --seed {hrtime} --keys {range(1,500)}
```

To try reproducing a crash multiple times before classifying it, pass `--repro-tries`:

```bash
./bin/harness --php-bin ./php85d --repro-tries 5 --ops 1000 --reduce -- ./bin/fuzzer --ops {ops} --seed {hrtime} --keys {range(1,500)}
```

```bash
./bin/harness --php-bin /usr/bin/php --php-ini ./relay.ini -- ./bin/fuzzer --ops 1000 --mode random
```

## Reduce script-mode reproducers

Reduce a script-mode reproducer by removing whole `Table::` statements (including
multi-line calls) while preserving crashing behavior:

```bash
./bin/final-reduce-script --php-bin ./php85d --php-ini ./relay.ini --max-runs 10 \
  -drelay.maxmemory=206715 \
  reproducers/reproducing/20260210_230216_fd61f666/reproducer.php
```

## Complex example of running the harness

Run 36 parallel fuzzing jobs where we generate a PHP script and then run it under
valgrind, capturing reproducers when valgrind reports errors. For each reproducer
the fuzzer will try to reduce it to the smallest possible program that still has
a memory error.

**Note**: Valgrind is optional. Without valgrind we will just detect if the process exits with a crashing signal.

```bash
./bin/harness \
    --php-bin /path/to/php \
    --capture-php-leaks \
    --valgrind \
    --mode script \
    --repro-tries 10 \
    --jobs 36 \
    --reduce \
    --ops 50 \
    --timeout 10 \
    -drelay.maxmemory='{range(200000,256000)}' \
    -- \
        ./bin/fuzzer \
            --workers 2 \
            --ops '{ops}' \
            --seed '{range}' \
            --keys '{range(1,1000)}' \
            --namespaces '{range(1,20)}'
```

## List saved reproducers

List all saved reproducers (aggregated from both `reproducing` and `non-reproducing`):

```bash
./bin/reproducers
```

Only show reproducing entries:

```bash
./bin/reproducers --reproducing
```

Only show non-reproducing entries in one-line form:

```bash
./bin/reproducers --non-reproducing --oneline
```

Sort by operation count instead of script line count:

```bash
./bin/reproducers --sort=ops
```

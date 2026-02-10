# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Added
- Added `--repro-tries` to `bin/harness` to retry reproducer runs before classifying crashes or leaks.
- Added `--valgrind` support to `bin/harness` (script mode) to capture valgrind logs and classify reproducers by valgrind errors.
- Added `bin/final-reduce-script` to reduce script-mode reproducers by removing whole worker `Table::` statements, with forwarded PHP args and `--max-runs` retry support for nondeterministic crashes.
### Changed
- `run-reproducer.sh` now uses absolute paths and copies the active php.ini into the reproducer directory.
- `run-reproducer.sh` now resolves the script directory at runtime for reproducer-local paths.
- `bin/reprodcuers` now aggregates entries from `reproducing` and `non-reproducing`, supports `--reproducing` and `--non-reproducing` filters, and sorts by `reproducer.php` line count by default.
### Fixed
### Internal

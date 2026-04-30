# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Unreleased
### Added
- Added `--repro-tries` to `bin/harness` to retry reproducer runs before classifying crashes or leaks.
- Added `--valgrind` support to `bin/harness` (script mode) to capture valgrind logs and classify reproducers by valgrind errors.
- Added `bin/final-reduce-script` to reduce script-mode reproducers by removing whole worker `Table::` statements, with forwarded PHP args and `--max-runs` retry support for nondeterministic crashes.
- Added `--valgrind` and `--timeout` to `bin/final-reduce-script`; valgrind mode classifies crashes by valgrind memory errors only (leaks ignored), while timeout mode lets reducer treat infinite loops as reproducible failures.
- Added `-v`/`-vv` verbosity controls to `bin/final-reduce-script` for richer runtime progress and retry diagnostics.
- Added `--progress-mode=screen` and `--interval` to `bin/final-reduce-script` for a periodically refreshed live status screen showing elapsed time, iteration counter, and best-so-far script/command details.
### Changed
- `bin/harness` now archives reproducers and leak captures into short sequential directories like `0001`, while keeping active concurrent captures in unique temporary directories.
- Tightened the `bin/harness` live status panel so it no longer renders spacer rows between summary lines.
- Reworked `bin/harness` screen drawing to use `php-tui/php-tui` and added a bottom table of recent reproducers with failure type, reproduction status, and path.
- `run-reproducer.sh` now uses absolute paths and copies the active php.ini into the reproducer directory.
- `run-reproducer.sh` now resolves the script directory at runtime for reproducer-local paths.
- `bin/reprodcuers` now aggregates entries from `reproducing` and `non-reproducing`, supports `--reproducing` and `--non-reproducing` filters, and sorts by `reproducer.php` line count by default.
- Reworked `bin/final-reduce-script` to use Symfony Console command parsing/output/error handling and Console sections for live screen redraws.
- `bin/final-reduce-script --progress-mode=screen` now includes elapsed time since the last best reduction, so long gaps between improvements are visible.
- `bin/final-reduce-script` now prints a final `Best command: ...` line in plain output mode for quick copy/paste reruns.
- `bin/harness --timeout` now manages deadlines internally, reaps timed out children itself, and saves live `gdb` timeout backtraces into reproducer directories.
### Fixed
- Normalized `bin/harness` reproducer paths in the live status display so project-local paths are shown relative to the current working directory.
- Fixed `bin/harness` saved reproducer reports so core-dump crashes are labeled as core failures instead of `EXIT_0`.
### Internal

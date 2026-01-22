<?php

declare(strict_types=1);

namespace Mgrunder\RelayTableFuzzer;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\LogRecord;
use Monolog\Logger;

final class LoggerFactory
{
    public static function create(string $level): Logger
    {
        $logger = new Logger('relay-table-fuzzer');
        $handler = new StreamHandler('php://stderr', Logger::toMonologLevel($level));
        $format = "[%extra.microtime% %extra.pid% %level_name%] %message% %context%\n";
        $formatter = new LineFormatter($format, null, true, true);
        $handler->setFormatter($formatter);
        $logger->pushProcessor(static function (LogRecord $record): LogRecord {
            $extra = $record->extra;
            $extra['microtime'] = sprintf('%.6f', microtime(true));
            $extra['pid'] = getmypid();
            return $record->with(extra: $extra);
        });
        $logger->pushHandler($handler);
        return $logger;
    }
}

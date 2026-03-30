<?php

declare(strict_types=1);

namespace App\Logging;

use Gelf\Publisher;
use Gelf\Transport\UdpTransport;
use Monolog\Handler\GelfHandler;
use Monolog\Level;
use Monolog\Logger;

final class GraylogLoggerFactory
{
    /**
     * Create a custom Monolog logger instance for Graylog (GELF over UDP).
     *
     * @param  array{host?: string, port?: int, level?: string}  $config
     */
    public function __invoke(array $config): Logger
    {
        $transport = new UdpTransport(
            host: $config['host'] ?? '127.0.0.1',
            port: (int) ($config['port'] ?? 12201),
        );

        $publisher = new Publisher($transport);

        $level = Level::fromName($config['level'] ?? 'info');

        $handler = new GelfHandler(
            publisher: $publisher,
            level: $level,
        );

        return new Logger('graylog', [$handler]);
    }
}

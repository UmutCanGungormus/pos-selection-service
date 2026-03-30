<?php

declare(strict_types=1);

use App\Logging\GraylogLoggerFactory;
use Monolog\Handler\GelfHandler;
use Monolog\Level;
use Monolog\Logger;

it('creates a monolog logger with a GelfHandler', function (): void {
    $factory = new GraylogLoggerFactory;

    $config = [
        'host' => '127.0.0.1',
        'port' => 12201,
        'level' => 'info',
    ];

    $logger = $factory($config);

    expect($logger)->toBeInstanceOf(Logger::class)
        ->and($logger->getName())->toBe('graylog')
        ->and($logger->getHandlers())->toHaveCount(1)
        ->and($logger->getHandlers()[0])->toBeInstanceOf(GelfHandler::class);
});

it('uses the configured log level', function (): void {
    $factory = new GraylogLoggerFactory;

    $config = [
        'host' => '127.0.0.1',
        'port' => 12201,
        'level' => 'warning',
    ];

    $logger = $factory($config);

    /** @var GelfHandler $handler */
    $handler = $logger->getHandlers()[0];

    expect($handler->getLevel())->toBe(Level::Warning);
});

it('defaults to info level when level is not provided', function (): void {
    $factory = new GraylogLoggerFactory;

    $config = [
        'host' => '127.0.0.1',
        'port' => 12201,
    ];

    $logger = $factory($config);

    /** @var GelfHandler $handler */
    $handler = $logger->getHandlers()[0];

    expect($handler->getLevel())->toBe(Level::Info);
});

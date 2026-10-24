<?php

declare(strict_types=1);

namespace App\Core;

use App\Contracts\LoggerInterface;
use Stringable;

final class Logger implements LoggerInterface
{
    private const LEVELS = [
        'debug' => 100,
        'info' => 200,
        'warning' => 300,
        'error' => 400,
        'critical' => 500,
        'emergency' => 600,
    ];

    public function __construct(private readonly string $path, private readonly string $minimumLevel = 'debug')
    {
    }

    public function log(string $level, string|Stringable $message, array $context = []): void
    {
        if ((self::LEVELS[$level] ?? 0) < (self::LEVELS[$this->minimumLevel] ?? 0)) {
            return;
        }

        if (!is_dir($this->path)) {
            @mkdir($this->path, 0775, true);
        }

        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR),
        );

        file_put_contents($this->path . '/app-' . date('Y-m-d') . '.log', $line, FILE_APPEND | LOCK_EX);
    }

    public function debug(string|Stringable $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }

    public function info(string|Stringable $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }

    public function error(string|Stringable $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = []): void
    {
        $this->log('critical', $message, $context);
    }
}

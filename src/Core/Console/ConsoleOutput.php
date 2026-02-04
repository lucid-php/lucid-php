<?php

declare(strict_types=1);

namespace Core\Console;

class ConsoleOutput implements OutputInterface
{
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_BLUE = "\033[34m";
    private const COLOR_RESET = "\033[0m";

    public function write(string $message): void
    {
        echo $message;
    }

    public function writeln(string $message): void
    {
        echo $message . PHP_EOL;
    }

    public function success(string $message): void
    {
        $this->writeln(self::COLOR_GREEN . '✓ ' . $message . self::COLOR_RESET);
    }

    public function error(string $message): void
    {
        $this->writeln(self::COLOR_RED . '✗ ' . $message . self::COLOR_RESET);
    }

    public function warning(string $message): void
    {
        $this->writeln(self::COLOR_YELLOW . '⚠ ' . $message . self::COLOR_RESET);
    }

    public function info(string $message): void
    {
        $this->writeln(self::COLOR_BLUE . 'ℹ ' . $message . self::COLOR_RESET);
    }

    public function table(array $headers, array $rows): void
    {
        if (empty($headers) || empty($rows)) {
            return;
        }

        // Calculate column widths
        $widths = array_map('strlen', $headers);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], strlen((string)$cell));
            }
        }

        // Print header
        $this->writeln('');
        $headerLine = '| ';
        foreach ($headers as $i => $header) {
            $headerLine .= str_pad($header, $widths[$i]) . ' | ';
        }
        $this->writeln($headerLine);

        // Print separator
        $separator = '|-';
        foreach ($widths as $width) {
            $separator .= str_repeat('-', $width) . '-|-';
        }
        $this->writeln($separator);

        // Print rows
        foreach ($rows as $row) {
            $rowLine = '| ';
            foreach ($row as $i => $cell) {
                $rowLine .= str_pad((string)$cell, $widths[$i]) . ' | ';
            }
            $this->writeln($rowLine);
        }
        $this->writeln('');
    }
}

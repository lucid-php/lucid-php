<?php

declare(strict_types=1);

namespace Core\Console;

class ConsoleOutput implements OutputInterface
{
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_RED = "\033[31m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_BLUE = "\033[34m";
    private const COLOR_GRAY = "\033[90m";
    private const COLOR_RESET = "\033[0m";

    /**
     * Inline markup tags supported in messages. Keep this an explicit, small
     * whitelist — no open-ended parsing.
     *
     * <info> green, <comment> yellow, <error> red (emphasis); <dim> gray
     * (de-emphasis for secondary text).
     */
    private const TAG_COLORS = [
        '<comment>' => self::COLOR_YELLOW,
        '<info>' => self::COLOR_GREEN,
        '<error>' => self::COLOR_RED,
        '<dim>' => self::COLOR_GRAY,
    ];

    private readonly bool $colors;

    /**
     * @param bool|null $colors Force colors on/off; null auto-detects (TTY + NO_COLOR).
     */
    public function __construct(?bool $colors = null)
    {
        $this->colors = $colors ?? $this->detectColorSupport();
    }

    public function write(string $message): void
    {
        echo $this->format($message);
    }

    public function writeln(string $message): void
    {
        echo $this->format($message) . PHP_EOL;
    }

    /**
     * Render inline tags. With colors enabled, tags become ANSI codes; with
     * colors disabled (piped output, NO_COLOR, non-TTY) tags and any ANSI codes
     * are stripped so logs stay clean.
     */
    private function format(string $message): string
    {
        if ($this->colors) {
            $replacements = self::TAG_COLORS + [
                '</comment>' => self::COLOR_RESET,
                '</info>' => self::COLOR_RESET,
                '</error>' => self::COLOR_RESET,
                '</dim>' => self::COLOR_RESET,
            ];

            return strtr($message, $replacements);
        }

        return $this->plain($message);
    }

    /**
     * Strip inline tags and any ANSI escape sequences, leaving the visible text.
     */
    private function plain(string $message): string
    {
        $message = strtr($message, array_fill_keys([
            '<comment>', '</comment>', '<info>', '</info>', '<error>', '</error>', '<dim>', '</dim>',
        ], ''));

        return preg_replace('/\033\[[0-9;]*m/', '', $message) ?? $message;
    }

    /**
     * Visible width of a string, ignoring inline tags / ANSI codes.
     */
    private function visibleLength(string $value): int
    {
        return strlen($this->plain($value));
    }

    /**
     * Right-pad a (possibly tagged) cell to a visible width.
     */
    private function padCell(string $cell, int $width): string
    {
        $pad = $width - $this->visibleLength($cell);

        return $cell . ($pad > 0 ? str_repeat(' ', $pad) : '');
    }

    private function detectColorSupport(): bool
    {
        // https://no-color.org/ — any non-empty value disables color.
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (!defined('STDOUT')) {
            return false;
        }

        if (function_exists('posix_isatty')) {
            return @posix_isatty(STDOUT);
        }

        return true;
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

        // Calculate column widths from VISIBLE length, so colored cells
        // (which contain tags) still line up.
        $widths = array_map(fn(string $h): int => $this->visibleLength($h), $headers);
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $widths[$i] = max($widths[$i], $this->visibleLength((string) $cell));
            }
        }

        // Print header (whole row emphasized)
        $this->writeln('');
        $headerLine = '| ';
        foreach ($headers as $i => $header) {
            $headerLine .= $this->padCell($header, $widths[$i]) . ' | ';
        }
        $this->writeln('<info>' . $headerLine . '</info>');

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
                $rowLine .= $this->padCell((string) $cell, $widths[$i]) . ' | ';
            }
            $this->writeln($rowLine);
        }
        $this->writeln('');
    }
}

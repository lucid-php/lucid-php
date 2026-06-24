<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Commands\ConfigShowCommand;
use App\Commands\MakeMigrationCommand;
use Core\Config\Config;
use Core\Console\OutputInterface;
use PHPUnit\Framework\TestCase;

class ConsoleCommandsTest extends TestCase
{
    public function testConfigShowMasksSensitiveValues(): void
    {
        $dir = sys_get_temp_dir() . '/lucid_cfg_' . uniqid();
        mkdir($dir);
        file_put_contents(
            $dir . '/secrets.php',
            "<?php\nreturn ['host' => 'db.local', 'password' => 'hunter2', 'api' => ['secret_key' => 'sk_live_x']];"
        );

        try {
            $output = new CapturingOutput();
            $command = new ConfigShowCommand(new Config($dir));
            $command->execute($output, 'secrets');

            $text = $output->text;
            $this->assertStringContainsString('host = db.local', $text);
            $this->assertStringContainsString('password = ***', $text);
            $this->assertStringContainsString('api.secret_key = ***', $text);
            $this->assertStringNotContainsString('hunter2', $text);
            $this->assertStringNotContainsString('sk_live_x', $text);
        } finally {
            array_map('unlink', glob($dir . '/*') ?: []);
            rmdir($dir);
        }
    }

    public function testMakeMigrationCreatesUpAndDownFiles(): void
    {
        $name = 'tmptest_' . uniqid();
        $output = new CapturingOutput();

        (new MakeMigrationCommand())->execute($output, $name);

        $dir = dirname(__DIR__, 2) . '/database/migrations';
        $created = glob($dir . "/*_{$name}.*.sql") ?: [];

        try {
            $this->assertCount(2, $created, 'creates an up and a down file');

            $ups = preg_grep('/\.up\.sql$/', $created);
            $downs = preg_grep('/\.down\.sql$/', $created);
            $this->assertCount(1, $ups);
            $this->assertCount(1, $downs);
            $this->assertStringContainsString('(up)', file_get_contents((string) reset($ups)));
        } finally {
            foreach ($created as $file) {
                @unlink($file);
            }
        }
    }
}

class CapturingOutput implements OutputInterface
{
    public string $text = '';

    public function write(string $message): void
    {
        $this->text .= $this->strip($message);
    }
    public function writeln(string $message): void
    {
        $this->text .= $this->strip($message) . "\n";
    }
    public function success(string $message): void
    {
        $this->text .= $this->strip($message) . "\n";
    }
    public function error(string $message): void
    {
        $this->text .= $this->strip($message) . "\n";
    }
    public function warning(string $message): void
    {
        $this->text .= $this->strip($message) . "\n";
    }
    public function info(string $message): void
    {
        $this->text .= $this->strip($message) . "\n";
    }
    public function table(array $headers, array $rows): void
    {
        $this->text .= implode(' | ', $headers) . "\n";
        foreach ($rows as $row) {
            $this->text .= implode(' | ', array_map(fn ($c): string => (string) $c, $row)) . "\n";
        }
    }

    /** Strip inline markup tags so assertions see plain text. */
    private function strip(string $message): string
    {
        return strtr($message, array_fill_keys(
            ['<comment>', '</comment>', '<info>', '</info>', '<error>', '</error>', '<dim>', '</dim>'],
            ''
        ));
    }
}

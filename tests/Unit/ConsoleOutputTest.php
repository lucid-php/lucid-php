<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Console\ConsoleOutput;
use PHPUnit\Framework\TestCase;

class ConsoleOutputTest extends TestCase
{
    private ConsoleOutput $output;

    protected function setUp(): void
    {
        $this->output = new ConsoleOutput();
    }

    public function testWriteOutputsMessage(): void
    {
        ob_start();
        $this->output->write('test message');
        $output = ob_get_clean();
        
        $this->assertEquals('test message', $output);
    }

    public function testWritelnOutputsMessageWithNewline(): void
    {
        ob_start();
        $this->output->writeln('test message');
        $output = ob_get_clean();
        
        $this->assertEquals("test message" . PHP_EOL, $output);
    }

    public function testSuccessOutputsGreenMessage(): void
    {
        ob_start();
        $this->output->success('Success message');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Success message', $output);
        $this->assertStringContainsString('✓', $output);
    }

    public function testErrorOutputsRedMessage(): void
    {
        ob_start();
        $this->output->error('Error message');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Error message', $output);
        $this->assertStringContainsString('✗', $output);
    }

    public function testWarningOutputsYellowMessage(): void
    {
        ob_start();
        $this->output->warning('Warning message');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Warning message', $output);
        $this->assertStringContainsString('⚠', $output);
    }

    public function testInfoOutputsBlueMessage(): void
    {
        ob_start();
        $this->output->info('Info message');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Info message', $output);
        $this->assertStringContainsString('ℹ', $output);
    }

    public function testTableOutputsFormattedTable(): void
    {
        ob_start();
        $this->output->table(
            ['Name', 'Age'],
            [
                ['John', '25'],
                ['Jane', '30']
            ]
        );
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Name', $output);
        $this->assertStringContainsString('Age', $output);
        $this->assertStringContainsString('John', $output);
        $this->assertStringContainsString('Jane', $output);
        $this->assertStringContainsString('|', $output);
    }

    public function testTableWithEmptyHeadersOutputsNothing(): void
    {
        ob_start();
        $this->output->table([], [['data']]);
        $output = ob_get_clean();
        
        $this->assertEquals('', $output);
    }

    public function testTableWithEmptyRowsOutputsNothing(): void
    {
        ob_start();
        $this->output->table(['Header'], []);
        $output = ob_get_clean();

        $this->assertEquals('', $output);
    }

    public function testRendersInlineTagsAsAnsiWhenColorsEnabled(): void
    {
        $output = new ConsoleOutput(colors: true);

        ob_start();
        $output->writeln('Driver: <comment>sqlite</comment>');
        $rendered = ob_get_clean();

        $this->assertStringNotContainsString('<comment>', $rendered);
        $this->assertStringContainsString("\033[33m", $rendered); // yellow
        $this->assertStringContainsString("\033[0m", $rendered);  // reset
        $this->assertStringContainsString('sqlite', $rendered);
    }

    public function testTableAlignsColoredCellsByVisibleWidth(): void
    {
        $output = new ConsoleOutput(colors: false);

        ob_start();
        $output->table(['Method'], [['<info>GET</info>'], ['DELETE']]);
        $plain = ob_get_clean();

        // Column width is 6 ("Method"/"DELETE"); the tagged GET cell must pad to 6.
        $this->assertStringContainsString('| GET    |', $plain);
        $this->assertStringContainsString('| DELETE |', $plain);
        $this->assertStringNotContainsString('<info>', $plain);
    }

    public function testStripsTagsAndAnsiWhenColorsDisabled(): void
    {
        $output = new ConsoleOutput(colors: false);

        ob_start();
        $output->writeln('Driver: <comment>sqlite</comment>');
        $output->success('Done');
        $plain = ob_get_clean();

        $this->assertSame("Driver: sqlite" . PHP_EOL . "✓ Done" . PHP_EOL, $plain);
        $this->assertStringNotContainsString("\033[", $plain); // no ANSI at all
        $this->assertStringNotContainsString('<comment>', $plain);
    }
}

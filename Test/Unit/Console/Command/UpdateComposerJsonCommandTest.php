<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Test\Unit\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ModuleDuplicator\Console\Command\UpdateComposerJsonCommand;

class UpdateComposerJsonCommandTest extends TestCase
{
    private const ORIGINAL_JSON = '{"name": "foo/bar", "keywords": ["old"]}';

    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/yireo-module-duplicator-' . uniqid();
        mkdir($this->tmpDir);
        file_put_contents($this->tmpDir . '/composer.json', self::ORIGINAL_JSON);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->tmpDir . '/*') ?: []);
        rmdir($this->tmpDir);
    }

    /**
     * @dataProvider getValidKeywords
     */
    public function testKeywordsAreUpdated(string $option, array $expectedKeywords): void
    {
        $tester = $this->createTester($this->tmpDir);

        $this->assertSame(Command::SUCCESS, $tester->execute(['module' => 'Foo_Bar', '--keywords' => $option]));
        $this->assertSame($expectedKeywords, $this->readComposerData()['keywords']);
    }

    public static function getValidKeywords(): array
    {
        return [
            'single keyword' => ['foo', ['foo']],
            'multiple keywords' => ['foo,bar', ['foo', 'bar']],
            'whitespace and empty parts' => [' foo , bar-2 ,,', ['foo', 'bar-2']],
        ];
    }

    public function testWithoutKeywordsLeavesKeywordsUntouched(): void
    {
        $this->assertSame(Command::SUCCESS, $this->createTester($this->tmpDir)->execute(['module' => 'Foo_Bar']));
        $this->assertSame(['old'], $this->readComposerData()['keywords']);
    }

    /**
     * @dataProvider getInvalidKeywords
     */
    public function testInvalidKeywordsAreRejected(string $option): void
    {
        $tester = $this->createTester($this->tmpDir);

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => 'Foo_Bar', '--keywords' => $option]));
        $this->assertSame(self::ORIGINAL_JSON, file_get_contents($this->tmpDir . '/composer.json'));
    }

    public static function getInvalidKeywords(): array
    {
        return [
            'invalid utf-8' => ["foo,\xB1\x31"],
            'uppercase' => ['Foo'],
            'quotes' => ['foo","bar'],
            'trailing newline' => ["foo\n,bar\nbaz"],
        ];
    }

    public function testUnregisteredModuleFails(): void
    {
        $tester = $this->createTester(null);

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => 'Foo_Bar', '--keywords' => 'foo']));
        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    public function testInvalidJsonIsNotOverwritten(): void
    {
        file_put_contents($this->tmpDir . '/composer.json', '{invalid');
        $tester = $this->createTester($this->tmpDir);

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => 'Foo_Bar', '--keywords' => 'foo']));
        $this->assertSame('{invalid', file_get_contents($this->tmpDir . '/composer.json'));
    }

    public function testProductionModeIsRefused(): void
    {
        $tester = $this->createTester($this->tmpDir, State::MODE_PRODUCTION);

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => 'Foo_Bar', '--keywords' => 'foo']));
        $this->assertSame(self::ORIGINAL_JSON, file_get_contents($this->tmpDir . '/composer.json'));
    }

    private function createTester(?string $modulePath, string $mode = State::MODE_DEVELOPER): CommandTester
    {
        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')->willReturn($modulePath);

        $appState = $this->createMock(State::class);
        $appState->method('getMode')->willReturn($mode);

        return new CommandTester(new UpdateComposerJsonCommand($componentRegistrar, $appState));
    }

    private function readComposerData(): array
    {
        return json_decode((string)file_get_contents($this->tmpDir . '/composer.json'), true);
    }
}

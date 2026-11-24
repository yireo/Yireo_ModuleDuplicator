<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Test\Integration\Console\Command;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Console\CommandListInterface;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ModuleDuplicator\Console\Command\UpdateComposerJsonCommand;

class UpdateComposerJsonCommandTest extends TestCase
{
    private string $tmpDir;
    private string $moduleName;

    protected function setUp(): void
    {
        $suffix = uniqid();
        $this->tmpDir = sys_get_temp_dir() . '/yireo-module-duplicator-' . $suffix;
        $this->moduleName = 'YireoCmd' . $suffix . '_Test';
        mkdir($this->tmpDir);
        file_put_contents($this->tmpDir . '/composer.json', '{"name": "foo/bar"}');
        ComponentRegistrar::register(ComponentRegistrar::MODULE, $this->moduleName, $this->tmpDir);
    }

    protected function tearDown(): void
    {
        unlink($this->tmpDir . '/composer.json');
        rmdir($this->tmpDir);
    }

    public function testCommandIsRegistered(): void
    {
        $commands = Bootstrap::getObjectManager()->get(CommandListInterface::class)->getCommands();
        $this->assertArrayHasKey('Yireo_ModuleDuplicator::updateComposerJson', $commands);
        $this->assertInstanceOf(UpdateComposerJsonCommand::class, $commands['Yireo_ModuleDuplicator::updateComposerJson']);
    }

    public function testKeywordsAreUpdated(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::SUCCESS, $tester->execute(['module' => $this->moduleName, '--keywords' => 'foo,bar']));
        $composerData = json_decode((string)file_get_contents($this->tmpDir . '/composer.json'), true);
        $this->assertSame(['foo', 'bar'], $composerData['keywords']);
    }

    public function testUnregisteredModuleFails(): void
    {
        $tester = $this->createTester();

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => 'Unknown_Module', '--keywords' => 'foo']));
        $this->assertStringContainsString('not found', $tester->getDisplay());
    }

    private function createTester(): CommandTester
    {
        return new CommandTester(Bootstrap::getObjectManager()->create(UpdateComposerJsonCommand::class));
    }
}

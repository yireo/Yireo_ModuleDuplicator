<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Test\Unit\Console\Command;

use InvalidArgumentException;
use Magento\Framework\App\State;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Yireo\ModuleDuplicator\Console\Command\CreateModuleCommand;
use Yireo\ModuleDuplicator\Service\ModuleCreator;

class CreateModuleCommandTest extends TestCase
{
    public function testProductionModeIsRefused(): void
    {
        $moduleCreator = $this->createMock(ModuleCreator::class);
        $moduleCreator->expects($this->never())->method('create');

        $tester = $this->createTester($moduleCreator, State::MODE_PRODUCTION);

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => 'Foo_Bar', 'template' => 'Yireo_Empty']));
        $this->assertStringContainsString('production mode', $tester->getDisplay());
    }

    public function testInvalidInputIsReported(): void
    {
        $moduleCreator = $this->createMock(ModuleCreator::class);
        $moduleCreator->method('create')->willThrowException(new InvalidArgumentException('Invalid module name'));

        $tester = $this->createTester($moduleCreator, State::MODE_DEVELOPER);

        $this->assertSame(Command::FAILURE, $tester->execute(['module' => '../x_Y', 'template' => 'Yireo_Empty']));
        $this->assertStringContainsString('Invalid module name', $tester->getDisplay());
    }

    public function testModuleIsCreated(): void
    {
        $moduleCreator = $this->createMock(ModuleCreator::class);
        $moduleCreator->expects($this->once())->method('create')->with('Foo_Bar', 'Yireo_Empty');

        $tester = $this->createTester($moduleCreator, State::MODE_DEVELOPER);

        $this->assertSame(Command::SUCCESS, $tester->execute(['module' => 'Foo_Bar', 'template' => 'Yireo_Empty']));
    }

    private function createTester(ModuleCreator $moduleCreator, string $mode): CommandTester
    {
        $appState = $this->createMock(State::class);
        $appState->method('getMode')->willReturn($mode);

        return new CommandTester(new CreateModuleCommand($moduleCreator, $appState));
    }
}

<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Test\Unit\Service;

use InvalidArgumentException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ModuleDuplicator\Service\ModuleContext;

class ModuleContextTest extends TestCase
{
    public function testNameParts(): void
    {
        $context = $this->createContext('YireoTraining_ModuleDuplicator');

        $this->assertSame('YireoTraining_ModuleDuplicator', $context->getModuleName());
        $this->assertSame('YireoTraining', $context->getVendorName());
        $this->assertSame('ModuleDuplicator', $context->getPackageName());
        $this->assertSame('YireoTraining\\ModuleDuplicator', $context->getNamespace());
        $this->assertSame('module-duplicator', $context->getKebabCaseName());
        $this->assertSame('yireo-training/magento2-module-duplicator', $context->getComposerName());
    }

    public function testKebabCaseWithDigits(): void
    {
        $this->assertSame('foo-2-bar', $this->createContext('Yireo_Foo2Bar')->getKebabCaseName());
        $this->assertSame('empty', $this->createContext('Yireo_Empty')->getKebabCaseName());
    }

    public function testModulePathFallsBackToAppCode(): void
    {
        $this->assertSame('/var/www/app/code/Foo/Bar', $this->createContext('Foo_Bar')->getModulePath());
    }

    public function testModulePathOfRegisteredModule(): void
    {
        $this->assertSame('/vendor/foo/bar', $this->createContext('Foo_Bar', '/vendor/foo/bar')->getModulePath());
    }

    /**
     * @dataProvider getInvalidModuleNames
     */
    public function testInvalidModuleNames(string $moduleName): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->createContext($moduleName);
    }

    public static function getInvalidModuleNames(): array
    {
        return [
            [''],
            ['Foo'],
            ['Foo_'],
            ['_Bar'],
            ['foo_Bar'],
            ['Foo_bar'],
            ['Foo_Bar_Baz'],
            ['../../../pub/x_Y'],
            ['Foo_../../Bar'],
            ['Foo/Bar_Baz'],
            ['Foo\\Bar_Baz'],
            ['Foo_Bar;'],
            ['Foo_Bar\'.phpinfo().\''],
            ["Foo_Bar\n"],
            ['Foo_Bär'],
            ['Foo-Bar_Baz'],
        ];
    }

    private function createContext(string $moduleName, ?string $registeredPath = null): ModuleContext
    {
        $directoryList = $this->createMock(DirectoryList::class);
        $directoryList->method('getRoot')->willReturn('/var/www');

        $componentRegistrar = $this->createMock(ComponentRegistrar::class);
        $componentRegistrar->method('getPath')->willReturn($registeredPath);

        return new ModuleContext($directoryList, $componentRegistrar, $moduleName);
    }
}

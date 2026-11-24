<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Test\Unit\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use PHPUnit\Framework\TestCase;
use Yireo\ModuleDuplicator\Service\ContentReplacer;
use Yireo\ModuleDuplicator\Service\ModuleContext;

class ContentReplacerTest extends TestCase
{
    /**
     * @dataProvider getReplacements
     */
    public function testReplace(string $source, string $expected): void
    {
        $result = (new ContentReplacer())->replace(
            $source,
            $this->createContext('Yireo_Empty'),
            $this->createContext('AcmeCorp_NewModule')
        );

        $this->assertSame($expected, $result);
    }

    public static function getReplacements(): array
    {
        return [
            'module name' => [
                "register(ComponentRegistrar::MODULE, 'Yireo_Empty', __DIR__);",
                "register(ComponentRegistrar::MODULE, 'AcmeCorp_NewModule', __DIR__);",
            ],
            'module.xml' => ['<module name="Yireo_Empty"/>', '<module name="AcmeCorp_NewModule"/>'],
            'php namespace' => ['namespace Yireo\\Empty\\Model;', 'namespace AcmeCorp\\NewModule\\Model;'],
            'fqcn in xml' => ['<type name="\\Yireo\\Empty\\Foo"/>', '<type name="\\AcmeCorp\\NewModule\\Foo"/>'],
            'json namespace' => ['"Yireo\\\\Empty\\\\": ""', '"AcmeCorp\\\\NewModule\\\\": ""'],
            'composer name' => ['"name": "yireo/magento2-empty",', '"name": "acme-corp/magento2-new-module",'],
            'unrelated words' => [
                'echo "GITHUB_TOKEN is empty"; isEmpty(); Yireo rocks',
                'echo "GITHUB_TOKEN is empty"; isEmpty(); Yireo rocks',
            ],
            'other modules of vendor' => ['<module name="Yireo_Core"/>', '<module name="Yireo_Core"/>'],
            'longer module names' => ['Yireo_EmptyTwo Yireo_Empty', 'Yireo_EmptyTwo AcmeCorp_NewModule'],
            'no chained replacement' => ['Yireo_Empty Yireo\\Empty', 'AcmeCorp_NewModule AcmeCorp\\NewModule'],
        ];
    }

    public function testTargetContainingSourceIsNotReplacedTwice(): void
    {
        $result = (new ContentReplacer())->replace(
            'Yireo_Empty',
            $this->createContext('Yireo_Empty'),
            $this->createContext('Yireo_Empty')
        );

        $this->assertSame('Yireo_Empty', $result);
    }

    private function createContext(string $moduleName): ModuleContext
    {
        return new ModuleContext(
            $this->createMock(DirectoryList::class),
            $this->createMock(ComponentRegistrar::class),
            $moduleName
        );
    }
}

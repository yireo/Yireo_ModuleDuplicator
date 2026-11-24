<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Test\Integration\Service;

use InvalidArgumentException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Driver\File;
use Magento\TestFramework\Helper\Bootstrap;
use PHPUnit\Framework\TestCase;
use Yireo\ModuleDuplicator\Service\ModuleCreator;
use Yireo\ModuleDuplicator\Service\TemplateModuleListing;

class ModuleCreatorTest extends TestCase
{
    private string $tmpDir;
    private string $templateName;
    private string $targetVendor;
    private string $targetPath;

    protected function setUp(): void
    {
        $suffix = strtr(uniqid(), '0123456789', 'ghijklmnop');
        $this->tmpDir = sys_get_temp_dir() . '/yireo-module-duplicator-' . $suffix;
        $this->templateName = 'YireoTpl' . $suffix . '_Empty';
        $this->targetVendor = 'YireoTarget' . $suffix;

        $templatePath = $this->tmpDir . '/template';
        $this->writeFile($templatePath . '/registration.php', "<?php\n'" . $this->templateName . "';\n");
        $this->writeFile($templatePath . '/etc/module.xml', '<module name="' . $this->templateName . '"/>');
        $this->writeFile($templatePath . '/Model/Foo.php', 'namespace YireoTpl' . $suffix . '\\Empty\\Model;');
        $this->writeFile($templatePath . '/README.md', 'This is empty, see Yireo_Core');
        $this->writeFile($templatePath . '/composer.json', json_encode([
            'name' => 'yireo-tpl' . $suffix . '/magento2-empty',
            'keywords' => ['dev'],
            'autoload' => ['psr-4' => ['YireoTpl' . $suffix . '\\Empty\\' => '']],
        ], JSON_UNESCAPED_SLASHES));
        $this->writeFile($this->tmpDir . '/outside/secret.txt', 'secret');
        symlink($this->tmpDir . '/outside/secret.txt', $templatePath . '/linked-secret.txt');
        symlink($this->tmpDir . '/outside', $templatePath . '/linked-folder');

        ComponentRegistrar::register(ComponentRegistrar::MODULE, $this->templateName, $templatePath);

        $root = Bootstrap::getObjectManager()->get(DirectoryList::class)->getRoot();
        $this->targetPath = $root . '/app/code/' . $this->targetVendor . '/Created';
    }

    protected function tearDown(): void
    {
        $file = new File();
        foreach ([$this->tmpDir, dirname($this->targetPath)] as $folder) {
            if (is_dir($folder)) {
                $file->deleteDirectory($folder);
            }
        }
    }

    public function testCreate(): void
    {
        $this->getModuleCreator()->create($this->targetVendor . '_Created', $this->templateName);

        $this->assertStringContainsString(
            "'" . $this->targetVendor . "_Created'",
            (string)file_get_contents($this->targetPath . '/registration.php')
        );
        $this->assertSame(
            '<module name="' . $this->targetVendor . '_Created"/>',
            file_get_contents($this->targetPath . '/etc/module.xml')
        );
        $this->assertSame(
            'namespace ' . $this->targetVendor . '\\Created\\Model;',
            file_get_contents($this->targetPath . '/Model/Foo.php')
        );
        $this->assertSame('This is empty, see Yireo_Core', file_get_contents($this->targetPath . '/README.md'));

        $composerData = json_decode((string)file_get_contents($this->targetPath . '/composer.json'), true);
        $this->assertSame('yireo-target' . substr($this->targetVendor, 11) . '/magento2-created', $composerData['name']);
        $this->assertArrayNotHasKey('keywords', $composerData);
        $this->assertSame([$this->targetVendor . '\\Created\\' => ''], $composerData['autoload']['psr-4']);
    }

    public function testSymlinksAreNotCopied(): void
    {
        $this->getModuleCreator()->create($this->targetVendor . '_Created', $this->templateName);

        $this->assertFileDoesNotExist($this->targetPath . '/linked-secret.txt');
        $this->assertFileDoesNotExist($this->targetPath . '/linked-folder/secret.txt');
    }

    public function testExistingTargetFolderIsRefused(): void
    {
        $this->writeFile($this->targetPath . '/existing.txt', 'existing');

        try {
            $this->getModuleCreator()->create($this->targetVendor . '_Created', $this->templateName);
            $this->fail('Expected an exception');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('already exists', $exception->getMessage());
        }

        $this->assertSame('existing', file_get_contents($this->targetPath . '/existing.txt'));
        $this->assertFileDoesNotExist($this->targetPath . '/registration.php');
    }

    public function testPathTraversalIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->getModuleCreator()->create('../../../pub/x_Y', $this->templateName);
    }

    public function testRegisteredModuleIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already exists');
        $this->getModuleCreator()->create('Magento_Catalog', $this->templateName);
    }

    public function testUnknownTemplateIsRefused(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not exist');
        $this->getModuleCreator()->create($this->targetVendor . '_Created', 'Magento_Catalog');
    }

    private function getModuleCreator(): ModuleCreator
    {
        return Bootstrap::getObjectManager()->create(ModuleCreator::class, [
            'templateModuleListing' => new TemplateModuleListing([$this->templateName => $this->templateName]),
        ]);
    }

    private function writeFile(string $path, string $contents): void
    {
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $contents);
    }
}

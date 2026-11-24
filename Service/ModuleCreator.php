<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

use InvalidArgumentException;
use Magento\Framework\Component\ComponentRegistrar;
use RuntimeException;
use Symfony\Component\Finder\Finder;

class ModuleCreator
{
    public function __construct(
        private readonly ModuleContextFactory $moduleContextFactory,
        private readonly TemplateModuleListing $templateModuleListing,
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly ContentReplacer $contentReplacer,
    ) {
    }

    public function create(string $moduleName, string $templateModuleName): void
    {
        $targetContext = $this->moduleContextFactory->create(['moduleName' => $moduleName]);

        if ($this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName)) {
            throw new InvalidArgumentException('Module name already exists');
        }

        if (!in_array($templateModuleName, $this->templateModuleListing->getAll(), true)) {
            throw new InvalidArgumentException("Template code \"$templateModuleName\" does not exist");
        }

        $sourceContext = $this->moduleContextFactory->create(['moduleName' => $templateModuleName]);

        $templatePath = (string)$this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $templateModuleName);
        $sourcePath = $templatePath !== '' ? realpath($templatePath) : false;
        if ($sourcePath === false) {
            throw new InvalidArgumentException("Template module \"$templateModuleName\" is not found");
        }

        $targetPath = $targetContext->getModulePath();
        if (file_exists($targetPath)) {
            throw new InvalidArgumentException("Target folder \"$targetPath\" already exists");
        }

        $finder = new Finder();
        $finder->files()->in($sourcePath);

        foreach ($finder as $file) {
            $absoluteSourcePath = $file->getRealPath();
            if ($file->isLink() || $absoluteSourcePath === false
                || !str_starts_with($absoluteSourcePath, $sourcePath . '/')) {
                continue;
            }

            $contents = $this->readFile($absoluteSourcePath);
            $contents = $this->contentReplacer->replace($contents, $sourceContext, $targetContext);

            if ($file->getRelativePathname() === 'composer.json') {
                $contents = $this->parseComposer($contents);
            }

            $this->writeFile($targetPath . '/' . $file->getRelativePathname(), $contents);
        }
    }

    private function readFile(string $path): string
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException("Failed to read file \"$path\"");
        }

        return $contents;
    }

    private function writeFile(string $path, string $contents): void
    {
        $dirName = dirname($path);
        if (!is_dir($dirName) && !mkdir($dirName, 0755, true) && !is_dir($dirName)) {
            throw new RuntimeException("Failed to create folder \"$dirName\"");
        }

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException("Failed to write file \"$path\"");
        }
    }

    private function parseComposer(string $contents): string
    {
        $data = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Template composer.json does not contain a JSON object');
        }

        if (isset($data['keywords']) && is_array($data['keywords']) && in_array('dev', $data['keywords'], true)) {
            unset($data['keywords']);
        }

        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}

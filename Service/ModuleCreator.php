<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;
use Symfony\Component\Finder\Finder;

class ModuleCreator
{
    public function __construct(
        private ModuleContextFactory $moduleContextFactory,
        private TemplateModuleListing $templateModuleListing,
        private ComponentRegistrar $componentRegistrar,
    ) {
    }

    public function create(string $moduleName, string $templateModuleName): void
    {
        if ($this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName)) {
            //throw new \InvalidArgumentException('Module name already exists');
        }

        $templateModules = $this->templateModuleListing->getAll();
        if (!in_array($templateModuleName, $templateModules)) {
            throw new \InvalidArgumentException('Template code does not exist');
        }

        $templatePath = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $templateModuleName);
        if (empty($templatePath)) {
            throw new \InvalidArgumentException('Template module does not exist');
        }

        $targetContext = $this->moduleContextFactory->create([
            'moduleName' => $moduleName,
        ]);

        $sourceContext = $this->moduleContextFactory->create([
            'moduleName' => $templateModuleName,
        ]);

        $targetPath = $targetContext->getModulePath();
        $sourcePath = $sourceContext->getModulePath();

        $finder = new Finder();
        $finder->files()->in($sourcePath);

        foreach ($finder as $file) {
            $absoluteTargetPath = $targetPath . '/'.$file->getRelativePathname();
            $absoluteSourcePath = $file->getRealPath();

            $sourceContents = file_get_contents($absoluteSourcePath);
            $targetContents = $this->parse($sourceContents, $sourceContext, $targetContext);

            $dirName = dirname($absoluteTargetPath);
            if (!is_dir($dirName)) {
                mkdir($dirName, 0755, true);
            }

            file_put_contents($absoluteTargetPath, $targetContents);
        }
    }

    private function parse(string $contents, ModuleContext $sourceContext, ModuleContext $targetContext): string
    {
        $contents = str_replace($sourceContext->getVendorName(), $targetContext->getVendorName(), $contents);
        $contents = str_replace($sourceContext->getPackageName(), $targetContext->getPackageName(), $contents);
        $contents = str_replace($sourceContext->getKebabCaseName(), $targetContext->getKebabCaseName(), $contents);
        $contents = str_replace($sourceContext->getNamespace(), $targetContext->getNamespace(), $contents);

        return $contents;
    }
}

<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;

class ModuleContext
{
    public function __construct(
        private DirectoryList $directoryList,
        private ComponentRegistrar $componentRegistrar,
        private string $moduleName,
    ) {
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getNamespace(): string
    {
        return '\\'.str_replace('_', '\\', $this->moduleName).'\\';
    }

    public function getKebabCaseName(): string
    {
        $pattern = '/(?<=\\w)(?=[A-Z])|(?<=[a-z])(?=[0-9])/';
        return strtolower(preg_replace($pattern, '-', $this->getPackageName()));
    }

    public function getVendorName(): string
    {
        $parts = explode('_', $this->getModuleName());

        return $parts[0];
    }

    public function getPackageName(): string
    {
        $parts = explode('_', $this->getModuleName());

        return $parts[1];
    }

    public function getModulePath(): string
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $this->getModuleName());
        if (!empty($path)) {
            return $path;
        }

        return $this->directoryList->getRoot().'/app/code/'.$this->getVendorName().'/'.$this->getPackageName();
    }
}

<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

use InvalidArgumentException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Component\ComponentRegistrar;

class ModuleContext
{
    private const MODULE_NAME_PATTERN = '/^[A-Z][A-Za-z0-9]*_[A-Z][A-Za-z0-9]*$/D';

    public function __construct(
        private readonly DirectoryList $directoryList,
        private readonly ComponentRegistrar $componentRegistrar,
        private readonly string $moduleName,
    ) {
        if (!preg_match(self::MODULE_NAME_PATTERN, $moduleName)) {
            throw new InvalidArgumentException(
                'Invalid module name "' . $moduleName . '": Use the format "Vendor_Package" (letters and digits only)'
            );
        }
    }

    public function getModuleName(): string
    {
        return $this->moduleName;
    }

    public function getNamespace(): string
    {
        return $this->getVendorName() . '\\' . $this->getPackageName();
    }

    public function getKebabCaseName(): string
    {
        return $this->toKebabCase($this->getPackageName());
    }

    public function getComposerName(): string
    {
        return $this->toKebabCase($this->getVendorName()) . '/magento2-' . $this->getKebabCaseName();
    }

    public function getVendorName(): string
    {
        return explode('_', $this->moduleName)[0];
    }

    public function getPackageName(): string
    {
        return explode('_', $this->moduleName)[1];
    }

    public function getModulePath(): string
    {
        $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $this->moduleName);
        if (!empty($path)) {
            return $path;
        }

        return $this->directoryList->getRoot() . '/app/code/' . $this->getVendorName() . '/' . $this->getPackageName();
    }

    private function toKebabCase(string $name): string
    {
        $pattern = '/(?<=\\w)(?=[A-Z])|(?<=[a-z])(?=[0-9])/';
        return strtolower((string)preg_replace($pattern, '-', $name));
    }
}

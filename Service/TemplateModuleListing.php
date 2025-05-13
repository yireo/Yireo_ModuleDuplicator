<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

class TemplateModuleListing
{
    public function __construct(
        private array $templates = []
    ) {
    }

    public function getAll(): array
    {
        return array_merge($this->templates, [
            'Yireo_LokiCheckoutEmpty',
            'Yireo_LokiCheckoutEmptyPayment',
            'Yireo_LokiCheckoutEmptyShipment',
        ]);
    }
}

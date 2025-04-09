<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

class TemplateModuleListing
{
    public function __construct(
        array $templates = []
    ) {
    }

    public function getAll(): array
    {
        return [
            'Yireo_LokiCheckoutEmpty',
            'Yireo_LokiCheckoutEmptyPayment',
            'Yireo_LokiCheckoutEmptyShipment',
        ];
    }
}

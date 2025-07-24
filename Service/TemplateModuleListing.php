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
        return [
            'Yireo_Empty',
            'LokiCheckout_Empty',
            'LokiCheckout_EmptyPayment',
            'LokiCheckout_EmptyShipment',
        ];
    }
}

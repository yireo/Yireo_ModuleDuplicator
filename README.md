# Yireo_ModuleDuplicator

<!-- badges.specs.start -->
![Magento version](https://img.shields.io/badge/Magento-2.4.6%20%7C%202.4.9-orange)
![PHP version](https://img.shields.io/badge/PHP-8.2%E2%80%938.5-777BB4)
![License](https://img.shields.io/badge/License-OSL--3.0-blue)
![Latest Version](https://img.shields.io/packagist/v/yireo/magento2-module-duplicator)
<!-- badges.specs.end -->


**This module duplicates another given *template* module to create a brand new module**

## Current status

<!-- badges.test.start -->
![Static Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ModuleDuplicator/static-tests.yml?label=static-tests)
![Unit Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ModuleDuplicator/unit-tests.yml?label=unit-tests)
![Integration Tests](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ModuleDuplicator/integration-tests.yml?label=integration-tests)
![Playwright](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ModuleDuplicator/playwright.yml?label=playwright)
![DI Compilation](https://img.shields.io/github/actions/workflow/status/yireo/Yireo_ModuleDuplicator/compile.yml?label=compile)
<!-- badges.test.end -->

## Installation
This module is a development tool. Install it as a development dependency only:

```bash
composer require --dev yireo/magento2-module-duplicator
bin/magento module:enable Yireo_ModuleDuplicator
```

The commands that write files refuse to run in production mode.

## Usage
```bash
bin/magento yireo:module-duplicator:list-templates
bin/magento yireo:module-duplicator:create Vendor_Package Yireo_Empty
bin/magento yireo:module-duplicator:update-composer-json Vendor_Package --keywords=foo,bar
```

Module names must use the format `Vendor_Package` (letters and digits only, both parts starting with an uppercase letter).
Keywords may only contain lowercase letters, digits and dashes.

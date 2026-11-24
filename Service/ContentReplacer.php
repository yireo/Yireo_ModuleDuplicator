<?php declare(strict_types=1);

namespace Yireo\ModuleDuplicator\Service;

use RuntimeException;

class ContentReplacer
{
    public function replace(string $contents, ModuleContext $sourceContext, ModuleContext $targetContext): string
    {
        $replacements = [
            $this->escapeBackslashes($sourceContext->getNamespace())
                => $this->escapeBackslashes($targetContext->getNamespace()),
            $sourceContext->getNamespace() => $targetContext->getNamespace(),
            $sourceContext->getModuleName() => $targetContext->getModuleName(),
            $sourceContext->getComposerName() => $targetContext->getComposerName(),
        ];

        uksort($replacements, fn (string $a, string $b) => strlen($b) <=> strlen($a));

        $tokens = array_map(fn (string $token) => preg_quote($token, '/'), array_keys($replacements));
        $pattern = '/(?<![A-Za-z0-9])(' . implode('|', $tokens) . ')(?![A-Za-z0-9])/';

        $result = preg_replace_callback($pattern, fn (array $match) => $replacements[$match[1]], $contents);
        if ($result === null) {
            throw new RuntimeException('Failed to replace module names in contents');
        }

        return $result;
    }

    private function escapeBackslashes(string $value): string
    {
        return str_replace('\\', '\\\\', $value);
    }
}

<?php

namespace App\Services\Automation;

/**
 * Renders `{{ placeholder }}` tokens in a reply template from a context map.
 * Unknown placeholders render as an empty string.
 */
class TemplateRenderer
{
    /**
     * @param  array<string, string|null>  $context
     */
    public function render(string $template, array $context): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*(\w+)\s*\}\}/',
            fn (array $matches): string => (string) ($context[$matches[1]] ?? ''),
            $template,
        );
    }
}

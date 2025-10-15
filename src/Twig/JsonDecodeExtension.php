<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class JsonDecodeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            // Usage in Twig: {{ someJson|json_decode(true) }}
            new TwigFilter('json_decode', [$this, 'jsonDecode']),
        ];
    }

    /**
     * @param string|null $json
     * @param bool $assoc When true, returned objects will be converted into associative arrays
     * @return mixed
     */
    public function jsonDecode(?string $json, bool $assoc = false)
    {
        if ($json === null || $json === '') {
            return $assoc ? [] : null;
        }
        $decoded = json_decode($json, $assoc);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Return original value on error to avoid Twig fatal
            return $assoc ? [] : null;
        }
        return $decoded;
    }
}

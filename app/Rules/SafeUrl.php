<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    private const ALLOWED_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $value = (string) $value;

        if ($value === '' || $value === '#') {
            return;
        }

        if (str_starts_with($value, '/') || str_starts_with($value, '#')) {
            return;
        }

        $scheme = parse_url($value, PHP_URL_SCHEME);

        if ($scheme === null) {
            return;
        }

        if (! in_array(strtolower($scheme), self::ALLOWED_SCHEMES, true)) {
            $fail("The {$attribute} contains an unsafe URL scheme.");
        }
    }
}

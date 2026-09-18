<?php

namespace App\Casts;

use App\Support\PlainTextNormalizer;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

final class PlainText implements CastsAttributes
{
    public function __construct(
        private readonly string $mode = 'required',
        private readonly string $whitespace = 'trim'
    ) {
    }

    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return PlainTextNormalizer::normalize($value, $this->shouldTrim());
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = PlainTextNormalizer::normalize($value, $this->shouldTrim());
        if ($this->mode === 'nullable' && trim($text) === '') {
            return null;
        }

        return $text;
    }

    private function shouldTrim(): bool
    {
        return $this->whitespace !== 'preserve';
    }
}

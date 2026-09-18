<?php

namespace App\Support;

final class PlainTextNormalizer
{
    public static function normalize(mixed $value, bool $trim = true): string
    {
        $text = (string) ($value ?? '');

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            if ($decoded === $text) {
                break;
            }

            $text = $decoded;
        }

        $text = str_replace(["\u{00A0}", "\u{202F}"], ' ', $text);
        $text = preg_replace(
            '/[\x{0000}-\x{0008}\x{000B}\x{000C}\x{000E}-\x{001F}\x{007F}\x{FEFF}]/u',
            '',
            $text
        ) ?? $text;

        return $trim ? trim($text) : $text;
    }

    public static function nullable(mixed $value, bool $trim = true): ?string
    {
        $text = self::normalize($value, $trim);

        return $text !== '' ? $text : null;
    }
}

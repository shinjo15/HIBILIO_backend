<?php

declare(strict_types=1);

namespace Src\Routine\Domain\ValueObject;

use Src\Shared\Domain\ValueObject\Base\StringValueObject;

final readonly class RoutineVideoUrl extends StringValueObject
{
    protected function validate(string $value): void
    {
        $parts = parse_url($value);
        if (mb_strlen($value) > 2048 || ! mb_check_encoding($value, 'UTF-8')
            || preg_match('/[\s\p{C}<>"{}|\\\\^`]|%(?![0-9a-fA-F]{2})/u', $value)
            || $parts === false || ! isset($parts['host'], $parts['scheme'])
            || ! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            throw new \InvalidArgumentException('動画URLは2048文字以下のHTTPまたはHTTPS URLである必要があります。');
        }

        $host = $parts['host'];
        if (preg_match('/[^\x00-\x7f]/', $host)) {
            $asciiHost = idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_USE_STD3_RULES, INTL_IDNA_VARIANT_UTS46);
            if ($asciiHost === false) {
                throw new \InvalidArgumentException('不正な動画URLです。');
            }
            $value = preg_replace('~^([a-z][a-z0-9+.-]*://(?:[^/@]*@)?)'.preg_quote($host, '~').'~iu', '$1'.$asciiHost, $value, 1);
            $host = $asciiHost;
        }
        if (! $this->hasValidBrowserNumericHost($host)) {
            throw new \InvalidArgumentException('不正な動画URLです。');
        }
        $asciiUrl = preg_replace_callback('/[^\x00-\x7f]+/u', static fn (array $match): string => rawurlencode($match[0]), $value);
        if ($asciiUrl === null || filter_var($asciiUrl, FILTER_VALIDATE_URL) === false) {
            throw new \InvalidArgumentException('不正な動画URLです。');
        }
    }

    /** @see https://url.spec.whatwg.org/#concept-ipv4-parser */
    private function hasValidBrowserNumericHost(string $host): bool
    {
        $parts = explode('.', $host);
        if (end($parts) === '' && count($parts) > 1) {
            array_pop($parts);
        }
        $last = end($parts);
        if (! is_string($last) || ! preg_match('/^(?:[0-9]+|0[xX][0-9a-fA-F]*)$/D', $last)) {
            return true;
        }
        if (count($parts) > 4) {
            return false;
        }

        $lastIndex = count($parts) - 1;
        foreach ($parts as $index => $part) {
            $number = $this->parseBrowserIpv4Number($part);
            if ($number === null || ($index < $lastIndex && $number > 255)
                || ($index === $lastIndex && $number >= 256 ** (4 - $index))) {
                return false;
            }
        }

        return true;
    }

    private function parseBrowserIpv4Number(string $part): ?float
    {
        if ($part === '') {
            return null;
        }
        $base = 10;
        if (strncasecmp($part, '0x', 2) === 0) {
            $base = 16;
            $part = substr($part, 2);
        } elseif (strlen($part) >= 2 && $part[0] === '0') {
            $base = 8;
            $part = substr($part, 1);
        }
        if ($part !== '' && ! preg_match(match ($base) {
            16 => '/^[0-9a-fA-F]+$/D',
            8 => '/^[0-7]+$/D',
            default => '/^[0-9]+$/D',
        }, $part)) {
            return null;
        }

        // Every intermediate integer is capped at 2^32-1, exactly representable as a float even on 32-bit PHP.
        $number = 0.0;
        foreach (str_split($part) as $digit) {
            $number = $number * $base + hexdec($digit);
            if ($number > 4294967295) {
                return null;
            }
        }

        return $number;
    }
}

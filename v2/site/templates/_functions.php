<?php

namespace ProcessWire;

/** True when the current request renders the default (German) language. */
function isGerman(): bool
{
    return wire('user')->language->isDefault();
}

/** Pick the string for the current language. */
function t(string $de, string $en): string
{
    return isGerman() ? $de : $en;
}

/** Short weekday label for a timestamp in the current language. */
function weekdayShort(int $timestamp): string
{
    $formatter = new \IntlDateFormatter(
        isGerman() ? 'de_DE' : 'en_US',
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::NONE,
        'Europe/Berlin',
        \IntlDateFormatter::GREGORIAN,
        'EEE'
    );
    return (string)$formatter->format($timestamp);
}

/** Label for a Termin status option in the current language. */
function statusLabel(string $value): string
{
    $labels = [
        'geplant'    => ['geplant', 'planned'],
        'angemeldet' => ['als Versammlung angemeldet', 'registered as assembly'],
        'abgesagt'   => ['abgesagt', 'cancelled'],
    ];
    $pair = $labels[$value] ?? [$value, $value];
    return isGerman() ? $pair[0] : $pair[1];
}

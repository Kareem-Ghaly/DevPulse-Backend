<?php

namespace App\Enums;

enum SocialProvider: string
{
    case GOOGLE = 'google';
    case GITHUB = 'github';

    public static function isSupported(string $provider): bool
    {
        return in_array($provider, array_column(self::cases(), 'value'), true);
    }
}

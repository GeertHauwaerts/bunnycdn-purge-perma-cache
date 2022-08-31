<?php

namespace App\Helpers;

use Carbon\Carbon;
use App\Controllers\Cache;

class StatusMessage
{
    const KEY_TIMESTAMP = 'StatusMessage::Timestamp';
    const FREQUENCY = 60;

    public static function touch()
    {
        $cache = new Cache();
        $cache->set(self::KEY_TIMESTAMP, Carbon::now(), 0);
    }

    public static function expired(): bool
    {
        $cache = new Cache();
        $ts = $cache->get(self::KEY_TIMESTAMP);

        if ($ts === null || Carbon::now()->diffInSeconds($ts) >= self::FREQUENCY) {
            return true;
        }

        return false;
    }
}

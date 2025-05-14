<?php

declare(strict_types=1);

namespace App\Telegram;

use Illuminate\Support\Facades\Cache;

class UserStateService
{
    private const string STATE_CACHE_KEY = 'user_state';

    private const string STATE_SEPARATOR = '@';

    public function getUserState(int $userId): ?array
    {
        $key = $this->getCacheKey($userId);
        $state = Cache::get($key, null);

        if (!$state) {
            return null;
        }

        return explode(self::STATE_SEPARATOR, $state);
    }

    public function clearUserState(int $userId): void
    {
        Cache::forget($this->getCacheKey($userId));
    }

    public function setUserState(int $userId, string $command, string $state): void
    {
        $key = $this->getCacheKey($userId);
        Cache::set($key, implode(self::STATE_SEPARATOR, [$command, $state]));
    }

    private function getCacheKey(int $userId): string
    {
        return implode("_", [
            self::STATE_CACHE_KEY, $userId
        ]);
    }
}

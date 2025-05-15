<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Structures\UserState;
use Illuminate\Support\Facades\Cache;

class UserStateService
{
    private const string STATE_CACHE_KEY = 'user_state';

    public function getUserState(int $userId): ?UserState
    {
        $key = $this->getCacheKey($userId);
        return Cache::get($key);
    }

    public function clearUserState(int $userId): void
    {
        Cache::forget($this->getCacheKey($userId));
    }

    public function setUserState(UserState $state): void
    {
        $key = $this->getCacheKey($state->userId);
        Cache::set($key, $state);
    }

    private function getCacheKey(int $userId): string
    {
        return implode("_", [
            self::STATE_CACHE_KEY, $userId
        ]);
    }
}

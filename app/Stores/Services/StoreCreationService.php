<?php

declare(strict_types=1);

namespace App\Stores\Services;

use App\Stores\Models\Store;
use App\Users\Models\TelegramUser;

class StoreCreationService
{
    public function createStore(TelegramUser $telegramUser, string $name): Store
    {
        //TODO VALIDATE
        return Store::create([
            'telegram_user_id' => $telegramUser->id,
            'name' => $name,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Users\Services;

use App\Users\Models\TelegramUser;
use Cache;
use Exception;
use Telegram\Bot\Objects\Update;

class TelegramUserService
{
    /**
     * @throws Exception
     */
    public function findOrCreateUserFromUpdate(Update $update): TelegramUser
    {
        $telegramUserId = $update->getMessage()->from->id;

        $user = TelegramUser::where('id', $telegramUserId)->first();
        if ($user) {
            return $user;
        }

        $lock = Cache::lock('processing-job-lock', 5);
        if ($lock->get()) {
            try {
                $user = TelegramUser::where('id', $telegramUserId)->first();

                if ($user) {
                    return $user;
                }

                return $this->createUserFromUpdate($update);
            } finally {
                $lock->release();
            }
        } else {
            //TODO Shouldn't happen - but perhaps some handling here is necessary
            throw new Exception("Lock wait exceeded");
        }
    }


    private function createUserFromUpdate(Update $update): TelegramUser
    {
        $userId = $update->getMessage()->from->id;
        return TelegramUser::create([
            'id' => $userId,
        ]);
    }
}

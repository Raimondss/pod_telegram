<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use App\Telegram\UpdateProcessor;
use Telegram;
use Telegram\Bot\Objects\Update;

class AddProductToStoreFlowProcessor implements FlowProcessorInterface
{

    private const string STEP_WAIT_IMAGE_UPLOAD = 'waiting_image_upload';

    public function startFlow(int $userId, Update $update, int $selectedStoreId): UserState
    {
        $state = UserState::getFreshState($userId, UpdateProcessor::ADD_PRODUCT_TO_STORE_FLOW_KEY, [
            'selectedStoreId' => $selectedStoreId,
        ]);

        return $this->processUserState($state, $update);
    }

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        if (!$previousState->previousStepKey) {
            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => "Send image to add product to store",
            ]);

            $previousState->previousStepKey = self::STEP_WAIT_IMAGE_UPLOAD;

            return $previousState;
        }

        if ($previousState->previousStepKey == self::STEP_WAIT_IMAGE_UPLOAD) {
            //TODO Validate that update is image etc...
            Telegram::sendMessage([
                'chat_id' => $previousState->userId,
                'text' => "Generating things...",
            ]);
        }

        return $previousState;
    }
}

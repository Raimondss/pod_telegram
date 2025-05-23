<?php

declare(strict_types=1);

namespace App\Telegram\Structures;

class UserState
{
    public int $userId;
    //Flow - started by receiving command /add_product or from other flows
    private ?string $startedFlowKey = null;
    //Previously sent message to customer -> "Enter Store name" . "Select store"
    //Usually dictates next message into the flow or some middle step
    public ?string $previousStepKey = null;

    //Some extra data that might be necessary for the flow. e.g - selectedStoreId
    public array $extra = [];

    public function __construct(int $userId)
    {
        $this->userId = $userId;
    }

    public function setFlow(?string $flowKey): void
    {
        //TODO PERHAPS CLEAN UP OBJECT? - FLOW CHANGED
        $this->startedFlowKey = $flowKey;
    }

    public function getStartedFlowKey(): ?string
    {
        return $this->startedFlowKey;
    }

    public static function getFreshState(int $userId, ?string $flowKey, array $extra = []): UserState
    {
        $state = new self($userId);
        $state->setFlow($flowKey);
        $state->extra = $extra;

        return $state;
    }
}

<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\FlowProcessors\AddProductToStoreFlowProcessor;
use App\Telegram\FlowProcessors\CreateStoreFlowProcessor;
use App\Telegram\FlowProcessors\EmptyFlowProcessor;
use App\Telegram\FlowProcessors\FlowProcessorInterface;
use App\Telegram\FlowProcessors\ManageStoresFlowProcessor;
use App\Telegram\Structures\UserState;
use Exception;
use Telegram\Bot\Objects\Update;

class UpdateProcessor
{
    public const string CREATE_STORE_FLOW_KEY = 'create_store_flow';
    public const string MANAGE_STORES_FLOW_KEY = 'manage_stores_flow';
    public const string ADD_PRODUCT_TO_STORE_FLOW_KEY = 'add_product_to_store_flow';

    public const string COMMAND_CREATE_STORE = '/create_store';
    public const string COMMAND_MANAGE_STORE = '/manage_stores';

    public const array FLOW_KEY_PROCESSOR_CLASS_MAP = [
        null => EmptyFlowProcessor::class,
        self::ADD_PRODUCT_TO_STORE_FLOW_KEY => AddProductToStoreFlowProcessor::class,
        self::CREATE_STORE_FLOW_KEY => CreateStoreFlowProcessor::class,
        self::MANAGE_STORES_FLOW_KEY => ManageStoresFlowProcessor::class
    ];

    public const array FLOW_START_COMMAND_FLOW_KEY_MAP = [
        self::COMMAND_CREATE_STORE => self::CREATE_STORE_FLOW_KEY,
        self::COMMAND_MANAGE_STORE => self::MANAGE_STORES_FLOW_KEY,
    ];

    public function __construct(private UserStateService $userStateService) {}

    /**
     * @throws Exception
     */
    public function processUpdate(Update $update): UserState
    {
        $state = $this->getCurrentState($update);

        //User sends command or update determines that we need to start new flow - and exist current flow.
        $startFlowKey = $this->getStartFlowKeyFromUpdate($update);
        if ($startFlowKey) {
            //Clear state and start new flow
            $state = $this->createEmptyState($this->getUpdateUserId($update));
            $state->setFlow($startFlowKey);
        }

        $processor = $this->getProcessor($state->getStartedFlowKey());

        echo "Current state:" . PHP_EOL;
        var_dump($state);
        echo "Processing using: " . $processor::class . PHP_EOL;

        $newState = $processor->processUserState($state, $update);
        $this->userStateService->setUserState($newState);

        return $newState;
    }


    private function getCurrentState(Update $update): UserState
    {
        $userId = $this->getUpdateUserId($update);

        $currentState = $this->userStateService->getUserState($userId);
        if ($currentState) {
            return $currentState;
        }

        return $this->createEmptyState($userId);
    }

    public function getStartFlowKeyFromUpdate(Update $update): ?string
    {
        $message = $update->getMessage()->text ?? '';

        var_dump($message);

        return self::FLOW_START_COMMAND_FLOW_KEY_MAP[$message] ?? null;
    }


    //TODO EXTRACT
    private function createEmptyState(int $userId): UserState
    {
        return new UserState($userId);
    }

    //TODO EXTRACT
    private function getUpdateUserId(Update $update): int
    {
        //For Callbacks ->getMessage() returns initial message defining callback actions
        if ($update->callbackQuery) {
            return $update->callbackQuery->from->id;
        }

        return $update->getMessage()->from->id;
    }

    /**
     * @throws Exception
     */
    private function getProcessor(?string $flowKey): FlowProcessorInterface
    {
        if ($flowKey) {
            return $this->getMappedProcessor($flowKey);
        }

        return app(EmptyFlowProcessor::class);
    }

    private function getMappedProcessor(?string $flowKey): FlowProcessorInterface
    {
        $processor = self::FLOW_KEY_PROCESSOR_CLASS_MAP[$flowKey];

        if (!$processor) {
            throw new Exception("Processor not found");
        }

        return app($processor);
    }
}

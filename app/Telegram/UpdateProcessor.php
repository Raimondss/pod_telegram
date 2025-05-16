<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\FlowProcessors\BrowseProductsProcessors;
use App\Telegram\FlowProcessors\BuyProductFlowProcessor;
use App\Telegram\FlowProcessors\CreateProductFlowProcessor;
use App\Telegram\FlowProcessors\EmptyFlowProcessor;
use App\Telegram\FlowProcessors\FlowProcessorInterface;
use App\Telegram\FlowProcessors\PreCheckoutQueryProcessor;
use App\Telegram\FlowProcessors\ShowHelpFlowProcessor;
use App\Telegram\FlowProcessors\SuccessfulPaymentProcessor;
use App\Telegram\Structures\UserState;
use App\Users\Services\TelegramUserService;
use Exception;
use Telegram\Bot\Objects\Update;

class UpdateProcessor
{
    public const string BROWSE_PRODUCTS_FLOW = 'browse_products_flow';
    public const string BUY_PRODUCT_FLOW = 'buy_product_flow';

    public const string CHECKOUT_COMPLETE_FLOW = 'checkout_complete_flow';
    public const string SUCCESSFUL_PAYMENT_FLOW = 'successfull_payment_flow';
    public const string ADD_PRODUCT_FLOW_KEY = 'create_product_flow';
    public const string SHOW_HELP_FLOW = 'show_help_flow';

    public const string COMMAND_MANAGE_STORE = '/my_products';

    public const string COMMAND_CREATE_PRODUCT = '/create_product';

    public const string COMMAND_HELP = '/help';


    public const array FLOW_KEY_PROCESSOR_CLASS_MAP = [
        null => ShowHelpFlowProcessor::class,
//        self::ADD_PRODUCT_TO_STORE_FLOW_KEY => AddProductToStoreFlowProcessor::class,
//        self::CREATE_STORE_FLOW_KEY => CreateStoreFlowProcessor::class,
//        self::MANAGE_STORES_FLOW_KEY => ManageStoresFlowProcessor::class,
        self::ADD_PRODUCT_FLOW_KEY => CreateProductFlowProcessor::class,
        self::CHECKOUT_COMPLETE_FLOW => PreCheckoutQueryProcessor::class,
        self::SUCCESSFUL_PAYMENT_FLOW => SuccessfulPaymentProcessor::class,
        self::BUY_PRODUCT_FLOW => BuyProductFlowProcessor::class,
        self::BROWSE_PRODUCTS_FLOW => BrowseProductsProcessors::class,
        self::SHOW_HELP_FLOW => ShowHelpFlowProcessor::class,
    ];

    public const array FLOW_START_COMMAND_FLOW_KEY_MAP = [
//        self::COMMAND_CREATE_STORE => self::CREATE_STORE_FLOW_KEY,
//        self::COMMAND_MANAGE_STORE => self::MANAGE_STORES_FLOW_KEY,
        self::COMMAND_CREATE_PRODUCT => self::ADD_PRODUCT_FLOW_KEY,
        self::COMMAND_HELP => self::SHOW_HELP_FLOW,
    ];

    public function __construct(private UserStateService $userStateService, private TelegramUserService $telegramUserService) {}

    /**
     * @throws Exception
     */
    public function processUpdate(Update $update): UserState
    {
        //Just so we create user in DB
        $this->telegramUserService->findOrCreateUserFromUpdate($update);
        dump($update);
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
        if ($update->isType('pre_checkout_query')) {
            return self::CHECKOUT_COMPLETE_FLOW;
        }

        if ($update->getMessage()->successful_payment) {
            return self::SUCCESSFUL_PAYMENT_FLOW;
        }

        $message = $update->getMessage()->text ?? '';

        if ($message === '/start') {
            return self::SHOW_HELP_FLOW;
        }

        if (str_contains($message, 'buy_product')) {
            return self::BUY_PRODUCT_FLOW;
        }

        if (str_contains($message, 'browse_products')) {
            return self::BROWSE_PRODUCTS_FLOW;
        }

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
        dump($flowKey);
        if ($flowKey) {
            return $this->getMappedProcessor($flowKey);
        }

        return app(ShowHelpFlowProcessor::class);
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

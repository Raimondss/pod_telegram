<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Models\TelegramUserOrder;
use App\Services\OrderService;
use App\Telegram\Structures\UserState;
use Telegram\Bot\Laravel\Facades\Telegram;
use Telegram\Bot\Objects\Update;

class SuccessfulPaymentProcessor implements FlowProcessorInterface
{
    public function __construct(private OrderService $orderService)
    {
    }

    public function processUserState(UserState $previousState, Update $update): UserState
    {
        $data = $update->getMessage()->successful_payment ?? null;

        if (!$data) {
            //Shouldnt happen
        }

        $order = new TelegramUserOrder();
        $order->telegram_user_variant_id = $data->invoice_payload;
        $order->telegram_user_id = $previousState->userId;

        $order->currency = $data->currency;
        $order->total_amount = $data->total_amount;
        $order->name = $update->getChat()->first_name . ' ' . $update->getChat()->last_name;
        $order->email = $data->orderInfo->email;

        // address
        $address = $data->orderInfo->shipping_address;

        $order->country_code = $address->country_code;
        $order->state = $address->state;
        $order->city = $address->city;
        $order->street_line1 = $address->street_line1;
        $order->street_line2 = $address->street_line2;
        $order->post_code = $address->post_code;

        $order->save();

        $this->orderService->sendOrderToPrintful($order);

        Telegram::sendMessage([
            'chat_id' => $previousState->userId,
            'text' => "Your order is successfully paid and sent to fulfillment",
        ]);

        return UserState::getFreshState($previousState->userId, null);
    }
}

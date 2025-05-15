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
        //UPDATE EXAMPLE
//        Telegram\Bot\Objects\Update^ {#745
//        #items: array:2 [
//        "update_id" => 290544543
//    "message" => array:5 [
//            "message_id" => 895
//      "from" => array:6 [
//            "id" => 8027961731
//        "is_bot" => false
//        "first_name" => "Johny"
//        "last_name" => "Dep"
//        "username" => "JohnyDeepR"
//        "language_code" => "en"
//      ]
//      "chat" => array:5 [
//            "id" => 8027961731
//        "first_name" => "Johny"
//        "last_name" => "Dep"
//        "username" => "JohnyDeepR"
//        "type" => "private"
//      ]
//      "date" => 1747319188
//      "successful_payment" => array:6 [
//            "currency" => "USD"
//        "total_amount" => 31500
//        "invoice_payload" => "10" //Telegram User->variantId
//        "order_info" => array:2 [
//            "email" => "Agsgsh@gmail.com"
//          "shipping_address" => array:6 [
//            "country_code" => "LV"
//            "state" => "Latvia"
//            "city" => "Priekuli"
//            "street_line1" => "Darz iela 8"
//            "street_line2" => ""
//            "post_code" => "Lv4126"
//          ]
//        ]
//        "telegram_payment_charge_id" => "7631847905_8027961731_3743_7504678752459481088"
//        "provider_payment_charge_id" => "7631847905_8027961731_3743_7504678752459481088"
//      ]
//    ]
//  ]
//  #escapeWhenCastingToString: false
//  #updateType: "message"
//}

        //TODO MAKE PF ORDER - SAVE TO DB AND SHIT

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
            'chat_id' => $userId,
            'text' => "Your order is successfully paid and sent to fulfillment",
        ]);

        return UserState::getFreshState($previousState->userId, null);
    }
}

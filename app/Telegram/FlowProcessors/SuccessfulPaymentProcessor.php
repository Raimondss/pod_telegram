<?php

declare(strict_types=1);

namespace App\Telegram\FlowProcessors;

use App\Telegram\Structures\UserState;
use Telegram\Bot\Objects\Update;

class SuccessfulPaymentProcessor implements FlowProcessorInterface
{
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

        return UserState::getFreshState($previousState->userId, null);
    }
}

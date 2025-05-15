<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $telegram_user_id
 * @property int $telegram_user_variant_id
 * @property string $currency
 * @property int $total_amount
 * @property string|null $email
 * @property string|null $name
 * @property string|null $country_code
 * @property string|null $state
 * @property string|null $city
 * @property string|null $street_line1
 * @property string|null $street_line2
 * @property string|null $post_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereCountryCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder wherePostCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereStreetLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereStreetLine2($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereTelegramUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereTelegramUserVariantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserOrder whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TelegramUserOrder extends Model
{
    use HasFactory;
}

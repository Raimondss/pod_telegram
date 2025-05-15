<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 *
 *
 * @property int $id
 * @property int $telegram_user_product_id
 * @property string $status
 * @property int $variant_id
 * @property string $color
 * @property string $size
 * @property int $price
 * @property string|null $mockup_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\TelegramUserProduct|null $product
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereColor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereMockupUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereTelegramUserProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserVariant whereVariantId($value)
 * @mixin \Eloquent
 */
class TelegramUserVariant extends Model
{
    protected $fillable = [
        'color',
        'size',
        'telegram_user_product_id',
        'status',
        'variant_id',
        'price',
        'mockup_url',
        'created_at',
        'updated_at',
    ];

    public const string STATUS_PENDING = 'pending';
    public const string STATUS_PROCESSING = 'processing';
    public const string STATUS_READY = 'ready';
    public const string STATUS_ERROR = 'error';

    use HasFactory;

    public function product(): HasOne
    {
        return $this->hasOne(TelegramUserProduct::class, 'id', 'telegram_user_product_id');
    }

    public function getDisplayTitle(): string
    {
        //TODO we need "product name also"
        return $this->product->design_name . ' - ' . $this->color . ' - ' . $this->size;
    }

    public function getDescription(): string
    {
        //TODO add Description
        return "Very nice description";
    }
}

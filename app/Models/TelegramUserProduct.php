<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $telegram_user_id
 * @property string $status
 * @property string $uploaded_file_url
 * @property string $design_name
 * @property int $product_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereDesignName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereTelegramUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUserProduct whereUploadedFileUrl($value)
 * @mixin \Eloquent
 */
class TelegramUserProduct extends Model
{
    protected $fillable = [
        'status',
        'telegram_user_id',
        'status',
        'uploaded_file_url',
        'design_name',
        'product_id',
        'category',
    ];

    public const string STATUS_PENDING = 'pending';
    public const string STATUS_PROCESSING = 'processing';
    public const string STATUS_READY = 'ready';
    public const string STATUS_ERROR = 'error';

    use HasFactory;

    public function variants(): HasMany
    {
        return $this->hasMany(TelegramUserVariant::class, 'telegram_user_product_id');
    }
}

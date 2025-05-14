<?php

namespace App\Users\Models;

use App\Stores\Models\Store;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 *
 *
 * @property int $id
 * @property int $telegram_user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Store> $stores
 * @property-read int|null $stores_count
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser query()
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser whereTelegramUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|TelegramUser whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class TelegramUser extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_user_id',
    ];

    public function stores(): HasMany
    {
        return $this->hasMany(Store::class);
    }
}

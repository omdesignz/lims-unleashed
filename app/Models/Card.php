<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Card extends Model
{
    use HasFactory;
    public CONST MENU_NAME = 'boardcards';

    const POSITION_GAP = 60000;
    const POSITION_MIN = 0.00002;
    protected $guarded = [];

    protected $with = ['members'];

    public static function booted()
    {
        static::creating(function ($model) {
            $model->position = self::query()->where('card_list_id', $model->card_list_id)->orderByDesc('position')->first()?->position + self::POSITION_GAP;
        });

        static::saved(function ($model) {
            if ($model->position < self::POSITION_MIN) {
                DB::statement("SET @previousPosition := 0");
                DB::statement("
                    UPDATE cards
                    SET position = (@previousPosition := @previousPosition + ?)
                    WHERE card_list_id = ?
                    ORDER BY position
                ", [
                        self::POSITION_GAP,
                        $model->card_list_id
                    ]
                );
            }
        });
    }

    public function members(): HasMany
    {
        return $this->hasMany(CardMember::class, 'card_id');
    }
}
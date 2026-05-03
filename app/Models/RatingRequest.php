<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use App\Filters\GlobalFilter;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RatingRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'rating_requests';

    public const MENU_NAME = 'rating_requests';

    protected $dates = ['created_at', 'updated_at'];

    //
    protected $fillable = [
        'user_id',
        'rateable_id',
        'rateable_type',
        'status'
    ];

    public function rateable(): MorphTo
    {
        return $this->morphTo();
    }
}

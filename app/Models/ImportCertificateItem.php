<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportCertificateItem extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'import_certificate_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'certificate_id',
        'product_id',
        'qty',
        'origin',
        'validity',
        'lot',
        'bl_no',
    ];

    protected $table = 'import_certificate_items';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];

    /**
     * Import Certificate
     *
     * @return Relationship
     */
    public function certificate()
    {
        return $this->belongsTo(ImportCertificate::class, 'certificate_id')->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(PhytosanitaryProduct::class, 'product_id')->withTrashed();
    }  
}

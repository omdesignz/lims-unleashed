<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportCertificateItem extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'export_certificate_items';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'qty',
        'obs',
    ];

    protected $table = 'export_certificate_items';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];

    /**
     * Export Certificate
     *
     * @return Relationship
     */

    public function certificate()
    {
        return $this->belongsTo(ExportCertificate::class, 'certificate_id');
    } 
    
    public function product()
    {
        return $this->belongsTo(PhytosanitaryProduct::class, 'product_id');
    }
}

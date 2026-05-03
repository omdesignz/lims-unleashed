<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExportCertificate extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'export_certificates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'exporter_id',
        'trans_type_id',
        'exporter_warehouse_id',        
        'user_id',
        'authorized_personnel',
        'cert_no',
        'country_origin_id',
        'country_destination_id',
        'origin_city',
        'destination_city',
        'expedition_date',
        'expedition_location',
        'obs',
        'file',
        'date',
        'invoiced',
        'invoice_id',
        'extra_data',
    ];

    protected $table = 'export_certificates';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'invoiced' => 'boolean',
        'extra_data' => 'array',
    ];

    /**
     * Customer
     *
     * @return Relationship
     */
    public function exporter()
    {
        return $this->belongsTo(Customer::class, 'exporter_id')->withTrashed();
    }

    public function trans_type()
    {
        return $this->belongsTo(TransportCategory::class, 'trans_type_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function exporter_warehouse()
    {
        return $this->belongsTo(Warehouse::class)->withTrashed();
    }

    public function country_origin()
    {
        return $this->belongsTo(Country::class, 'country_origin_id')->withTrashed();
    }

    public function country_destination()
    {
        return $this->belongsTo(Country::class, 'country_destination_id')->withTrashed();
    }

    /**
     * Export Certificate Items
     *
     * @return Relationship
     */
    public function items()
    {
        return $this->hasMany(ExportCertificateItem::class, 'certificate_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($exportCertificate) {
           
            $exportCertificate->cert_no = now()->format('YmHis');
            
        });

    }
}

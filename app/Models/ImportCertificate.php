<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportCertificate extends Model
{
    use HasFactory, SoftDeletes;

    public const MENU_NAME = 'import_certificates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'importer_id',
        'currency_id',
        'vat',
        'vat_cost',
        'importer_warehouse_id',
        'user_id',
        'exporter_id',
        'exporter_warehouse_id',        
        'cert_no',
        'trans_type_id',
        'port_exit',
        'port_entry',
        'destination_country_id',
        'cost_freight',
        'cost_insurance',
        'cost_final',
        'authorized_personnel',
        'date',
        'obs',
        'file',
        'invoiced',
        'invoice_id',
    ];

    protected $table = 'import_certificates';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        
    ];

    public function importer()
    {
        return $this->belongsTo(Customer::class, 'importer_id')->withTrashed();
    }

    public function trans()
    {
        return $this->belongsTo(TransportCategory::class, 'trans_type_id')->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function importer_warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'importer_warehouse_id')->withTrashed();
    }

    public function exporter()
    {
        return $this->belongsTo(Customer::class, 'exporter_id')->withTrashed();
    }

    public function exporter_warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'exporter_warehouse_id')->withTrashed();
    }

    public function destination_country()
    {
        return $this->belongsTo(Country::class, 'destination_country_id')->withTrashed();
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class, 'currency_id')->withTrashed();
    }

    /**
     * Export Certificate Items
     *
     * @return Relationship
     */
    public function items()
    {
        return $this->hasMany(ImportCertificateItem::class, 'certificate_id');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($importCertificate) {
           
            $importCertificate->cert_no = now()->format('YmHis');
            
        });

    }
}

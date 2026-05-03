<?php

namespace App\Models;

use App\Traits\ISO17025Revisionable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Spatie\Activitylog\Traits\CausesActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class QualityCertificate extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia, ISO17025Revisionable, CausesActivity;

    public CONST MENU_NAME = 'quality_certificates';

     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'collection_id',
        'cl_id',
        'warehouse_id',
        'customer_id',
        'invoice_id',
        'product_id',
        'code',
        'status',
        'obs',
        'file_path',
        'extra_data',
        'validated_by',
        'validated_by_id',
        'validated_at',
        'validated_on_behalf_of',
        'validated_on_behalf_of_id'
    ];


    protected $table = 'quality_certificates';
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];


     /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        // 'verdict' => 'boolean',
    ];


    /**
     * Customer
     *
     * @return Relationship
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    /**
     * Collection
     *
     * @return Relationship
     */
    public function collection()
    {
        return $this->belongsTo(CollectionProduct::class, 'collection_id');
    }

    // Results

    public function results()
    {
        return $this->hasManyThrough(Result::class, LabCode::class, 'id', 'code_id', 'cl_id', 'id');
    }

    /**
     * Collection
     *
     * @return Relationship
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id')->withTrashed();
    }

    /**
     * Laboratory Codes
     *
     * @return Relationship
     */
    public function lab_code()
    {
        return $this->belongsTo(LabCode::class, 'cl_id');
    }


    public function invoice_item()
    {
        return $this->morphOne(InvoiceItem::class, 'itemable');
    }

    public function quote_item()
    {
        return $this->morphOne(QuoteItem::class, 'itemable');
    }

    public function credit_note_item()
    {
        return $this->morphOne(CreditNoteItem::class, 'itemable');
    }

     /**
     * User
     *
     * @return Relationship
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

     /**
     * Product
     *
     * @return Relationship
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function getHasMicrobiologyParametersAttribute()
    {
        return $this?->lab_code?->samples()?->whereRelation('analysis', 'type_id', 2)?->pluck('id')?->count() > 0;
    }

    public function getVerdictAttribute()
    {
        $sampleIDs = $this?->lab_code?->samples()?->whereRelation('analysis', 'type_id', 2)?->pluck('id')?->toArray();

        return Result::whereIn('sample_id', $sampleIDs)->count() == Result::whereIn('sample_id', $sampleIDs)->where('approved_value', '<1')->count();
    }

    public function revisions()
    {
        return $this->hasMany(QualityCertificateRevision::class);
    }

    public function currentRevision()
    {
        return $this->hasOne(QualityCertificateRevision::class)->where('is_current', true);
    }

    public function getCurrentRevisionAttribute()
    {
        return $this->currentRevision()->first();
    }

    public function getCurrentVersionAttribute()
    {
        $revision = $this->currentRevision;
        return $revision ? $revision->version : '1.0';
    }

    // Helper method for ISO-compliant updates
    public function updateWithIsoCompliance(array $attributes, string $changeReason, array $approvalData = null)
    {
        return $this->logIsoChange($attributes, $changeReason, $approvalData);
    }

    public function getValidationSignatureUrlAttribute(): string
    {
        return $this?->getMedia('validation_signature')->count() ? $this?->getFirstMediaPath('validation_signature') : '';
    }

    public function validated_by_user()
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function validated_on_behalf_of_user()
    {
        return $this->belongsTo(User::class, 'validated_on_behalf_of_id');
    }

    public function registerMediaCollections(): void
    {
            
            $this->addMediaCollection('validation_signature')
            ->acceptsMimeTypes([
                'image/jpeg',
                'image/png'
                ])
            ->singleFile();   
    }

}

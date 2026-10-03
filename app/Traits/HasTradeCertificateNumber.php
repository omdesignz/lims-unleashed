<?php

namespace App\Traits;

use App\Models\ImportCertificate;
use App\Services\ScopedSequenceAllocator;
use Illuminate\Database\Eloquent\Model;
use LogicException;

trait HasTradeCertificateNumber
{
    /** @var array{cert_no: string, certificate_year: int, seq: int}|null */
    private ?array $tradeCertificateNumberIntent = null;

    /** @return array{cert_no: string, certificate_year: int, seq: int}|null */
    public function issuedTradeCertificateNumber(): ?array
    {
        return $this->tradeCertificateNumberIntent;
    }

    public static function bootHasTradeCertificateNumber(): void
    {
        static::creating(function (Model $record): void {
            $record->certificate_year = (int) now()->format('Y');
            $record->seq = null;
            app(ScopedSequenceAllocator::class)->assign($record, ['group' => ['lab_id', 'certificate_year'], 'fieldName' => 'seq']);
            $prefix = $record instanceof ImportCertificate ? 'IMP' : 'EXP';
            $record->cert_no = $prefix.'-'.$record->certificate_year.'-L'.$record->lab_id.'-'.str_pad((string) $record->seq, 5, '0', STR_PAD_LEFT);
            $record->tradeCertificateNumberIntent = ['cert_no' => $record->cert_no, 'certificate_year' => (int) $record->certificate_year, 'seq' => (int) $record->seq];
        });
        static::updating(function (Model $record): void {
            if ($record->isDirty(['cert_no', 'certificate_year', 'seq'])) {
                throw new LogicException('Issued trade certificate identifiers cannot be changed.');
            }
        });
    }
}

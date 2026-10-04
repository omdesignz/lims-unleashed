<?php

namespace App\Actions;

use App\Models\QualityCertificate;
use App\Models\Result;
use App\Models\User;
use App\Services\LaboratoryResultSignatures;
use App\Services\LaboratoryWorkflowMutationAccess;
use App\Services\LaboratoryWorkflowOwnership;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use LogicException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Releases a quality certificate once, with the validator's signature. A certificate is
 * validated only while every result behind it is approved, and a released certificate
 * is never re-signed: corrections go through the ISO revision workflow (approved
 * 2026-10-03). Replaying the same validator's request is a no-op.
 *
 * When the responsible validator is absent, a colleague may sign on their behalf: the
 * signer stays accountable (their identity and signature are recorded) and the absent
 * validator is recorded alongside, provided they could have validated it themselves.
 */
class ValidateQualityCertificate
{
    public function __construct(
        private readonly LaboratoryWorkflowOwnership $ownership,
        private readonly LaboratoryResultSignatures $signatures,
        private readonly LaboratoryWorkflowMutationAccess $access,
    ) {}

    public function execute(int $labId, int $userId, int $certificateId, ?string $signature, ?int $onBehalfOfId = null): QualityCertificate
    {
        $staged = null;

        try {
            return DB::transaction(function () use ($labId, $userId, $certificateId, $signature, $onBehalfOfId, &$staged): QualityCertificate {
                $validator = $this->access->operator($userId, $labId, 'validate_quality_certificates');
                $absent = $onBehalfOfId === null ? null : $this->absentValidator($labId, $validator, $onBehalfOfId);

                $certificate = QualityCertificate::query()
                    ->whereIn('collection_id', $this->ownership->collectionProductsForLaboratory($labId)->select('collection_product.id'))
                    ->lockForUpdate()
                    ->findOrFail($certificateId);

                if ($certificate->validated_at !== null) {
                    if ((int) $certificate->validated_by_id === $validator->id) {
                        return $certificate;
                    }

                    throw ValidationException::withMessages(['signature' => 'Este boletim já foi validado. Correcções seguem o fluxo de revisão ISO.']);
                }

                $results = $this->resultsBehind($labId, $certificate)->lockForUpdate()->get(['id', 'approved_date']);

                if ($results->isEmpty() || $results->contains(fn (Result $result): bool => blank($result->approved_date))) {
                    throw ValidationException::withMessages(['signature' => 'O boletim só pode ser validado quando todos os resultados estiverem aprovados.']);
                }

                $signed = $this->signatures->prepare($validator, $signature);

                if (! $certificate->forceFill([
                    'validated_by_id' => $validator->id,
                    'validated_by' => $validator->name,
                    'validated_at' => now(),
                    'validated_on_behalf_of' => $absent !== null,
                    'validated_on_behalf_of_id' => $absent?->id,
                ])->save()) {
                    throw new LogicException('Certificate release evidence was not persisted.');
                }

                $staged = $certificate->addMediaFromString($signed['bytes'])
                    ->usingFileName($validator->id.'-'.Str::uuid().($signed['mime'] === 'image/png' ? '.png' : '.jpg'))
                    ->withCustomProperties(['signature_sha256' => $signed['hash']])
                    ->toMediaCollection('validation_signature');

                activity()
                    ->by($validator)
                    ->performedOn($certificate)
                    ->withProperties(['on_behalf_of_id' => $absent?->id])
                    ->log('Validou o Boletim de Resultados Nº '.$certificate->code.($absent ? ' em nome de '.$absent->name : ''));

                $this->access->operator($userId, $labId, 'validate_quality_certificates');

                return $certificate;
            });
        } catch (\Throwable $exception) {
            if ($staged instanceof Media) {
                $this->signatures->discard([$staged]);
            }

            throw $exception;
        }
    }

    /**
     * Where the results behind a certificate stand, so the dossier shows the same rule
     * the validation enforces.
     *
     * @return array{results: int, inserted: int, verified: int, approved: int, ready: bool}
     */
    public function readiness(int $labId, QualityCertificate $certificate): array
    {
        $counts = $this->resultsBehind($labId, $certificate)
            ->selectRaw('count(*) as results')
            ->selectRaw('count(inserted_date) as inserted')
            ->selectRaw('count(verified_date) as verified')
            ->selectRaw('count(approved_date) as approved')
            ->toBase()
            ->first();
        $totals = [
            'results' => (int) $counts->results,
            'inserted' => (int) $counts->inserted,
            'verified' => (int) $counts->verified,
            'approved' => (int) $counts->approved,
        ];

        return [...$totals, 'ready' => $totals['results'] > 0 && $totals['approved'] === $totals['results']];
    }

    /**
     * The results the validator signs for, with who entered, verified and approved each
     * one and when.
     *
     * @return list<array{id: int, parameter: ?string, value: ?string, unit: ?string, uncertainty: ?string, inserted: array{by: ?string, at: mixed}, verified: array{by: ?string, at: mixed}, approved: array{by: ?string, at: mixed}}>
     */
    public function resultsForRelease(int $labId, QualityCertificate $certificate): array
    {
        return $this->resultsBehind($labId, $certificate)
            ->with('parameter:id,name')
            ->orderBy('sample_id')
            ->orderBy('id')
            ->get()
            ->map(fn (Result $result): array => [
                'id' => $result->id,
                'parameter' => $result->parameter_label ?: $result->parameter?->name,
                'value' => $result->approved_value ?? $result->verified_value ?? $result->inserted_value,
                'unit' => $result->unit_label,
                'uncertainty' => $result->uncertainty_value,
                'inserted' => ['by' => $result->inserted_by, 'at' => $result->inserted_date],
                'verified' => ['by' => $result->verified_by, 'at' => $result->verified_date],
                'approved' => ['by' => $result->approved_by, 'at' => $result->approved_date],
            ])
            ->all();
    }

    private function absentValidator(int $labId, User $validator, int $onBehalfOfId): User
    {
        $absent = User::query()
            ->whereKey($onBehalfOfId)
            ->where('is_active', true)
            ->whereIn('id', DB::table('lab_user')->where('lab_id', $labId)->select('user_id'))
            ->first();

        if ($absent === null || $absent->is($validator) || ! $absent->can('validate_quality_certificates')) {
            throw ValidationException::withMessages(['signed_by_user_id' => 'Só pode assinar em nome de outro validador activo deste laboratório.']);
        }

        return $absent;
    }

    /** @return Builder<Result> */
    private function resultsBehind(int $labId, QualityCertificate $certificate): Builder
    {
        return Result::query()->whereIn('sample_id', $this->ownership->samplesForLaboratory($labId)
            ->where('samples.cl_id', $certificate->cl_id)->select('samples.id'));
    }
}

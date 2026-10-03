<?php

namespace App\Actions;

use App\Jobs\DeliverIntegrationWebhook;
use App\Models\IntegrationDelivery;
use Illuminate\Support\Facades\DB;

class RetryIntegrationDelivery
{
    public function execute(int $deliveryId, int $labId): bool
    {
        return DB::transaction(function () use ($deliveryId, $labId): bool {
            $delivery = IntegrationDelivery::query()
                ->with('connector')
                ->lockForUpdate()
                ->findOrFail($deliveryId);
            abort_unless((int) $delivery->connector?->lab_id === $labId, 404);

            if (! in_array($delivery->status, ['failed', 'retrying'], true)) {
                return false;
            }

            $delivery->forceFill([
                'status' => 'pending',
                'last_error' => null,
                'next_attempt_at' => null,
            ])->save();

            DeliverIntegrationWebhook::dispatch($delivery)->afterCommit();

            return true;
        }, 3);
    }
}

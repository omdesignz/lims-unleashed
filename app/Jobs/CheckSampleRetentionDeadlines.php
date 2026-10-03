<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Support\NotificationTemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckSampleRetentionDeadlines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(NotificationTemplateService $templates): void
    {
        $recipients = User::query()->where('is_active', true)->whereNotNull('email_verified_at')
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'admin')->where('guard_name', 'web'))
            ->get();

        $samples = VAPSampleEntry::query()
            ->with(['warehouse', 'receivedBy'])
            ->whereIn('retention_status', ['active', 'due_soon', 'overdue'])
            ->whereNotNull('retention_due_at')
            ->whereDate('retention_due_at', '<=', now()->addDays(7))
            ->lazyById(100);

        foreach ($samples as $sample) {
            $status = $sample->retention_due_at && $sample->retention_due_at->isPast() ? 'overdue' : 'due_soon';

            $sample->forceFill([
                'retention_status' => $status,
            ])->save();

            $targets = $recipients
                ->merge(collect([$sample->receivedBy]))
                ->filter()
                ->unique('id');

            $templates->notify($targets, 'lab.sample.retention_due', [
                'sample_entry_id' => $sample->id,
                'sample_code' => $sample->code ?: $sample->name,
                'retention_status' => $status === 'overdue' ? 'com o prazo vencido' : 'próxima do vencimento',
                'due_date' => $sample->retention_due_at?->format('d/m/Y'),
                'document_url' => route('vap_samples.show', $sample),
            ]);
        }
    }
}

<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\VAPSampleEntry;
use App\Notifications\SampleRetentionDeadlineNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Notification;

class CheckSampleRetentionDeadlines implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $recipients = User::role('admin')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        $samples = VAPSampleEntry::query()
            ->with(['warehouse', 'receivedBy'])
            ->whereIn('retention_status', ['active', 'due_soon', 'overdue'])
            ->whereNotNull('retention_due_at')
            ->whereDate('retention_due_at', '<=', now()->addDays(7))
            ->get();

        foreach ($samples as $sample) {
            $status = $sample->retention_due_at && $sample->retention_due_at->isPast() ? 'overdue' : 'due_soon';

            $sample->forceFill([
                'retention_status' => $status,
            ])->save();

            $targets = $recipients
                ->merge(collect([$sample->receivedBy]))
                ->filter()
                ->unique('id');

            Notification::send(
                $targets,
                new SampleRetentionDeadlineNotification(
                    $sample,
                    $status === 'overdue' ? 'Retenção de amostra vencida' : 'Retenção de amostra próxima do vencimento',
                    $status === 'overdue'
                        ? sprintf('A amostra %s ultrapassou o prazo de retenção definido.', $sample->code ?: $sample->name)
                        : sprintf('A amostra %s atinge o prazo de retenção nos próximos 7 dias.', $sample->code ?: $sample->name),
                    $targets->first()
                )
            );
        }
    }
}

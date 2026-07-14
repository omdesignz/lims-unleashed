<?php

namespace App\Console\Commands;

use App\Models\ISOActivityLog;
use App\Models\QualityCertificate;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ISOComplianceAudit extends Command
{
    protected $signature = 'iso:audit 
        {--certificate= : Identificador específico do certificado}
        {--days=30 : Período retrospectivo em dias}';

    protected $description = 'Auditar a conformidade dos certificados de qualidade com a ISO/IEC 17025';

    public function handle()
    {
        $certificateId = $this->option('certificate');
        $days = $this->option('days');

        $query = QualityCertificate::query();

        if ($certificateId) {
            $query->where('id', $certificateId);
        }

        $certificates = $query->with(['revisions', 'currentRevision'])->get();

        $this->info("A auditar a conformidade de {$certificates->count()} certificados com a ISO/IEC 17025...");

        foreach ($certificates as $certificate) {
            $this->auditCertificate($certificate, $days);
        }

        $this->info('Auditoria concluída.');
    }

    private function auditCertificate(QualityCertificate $certificate, int $days)
    {
        $logs = ISOActivityLog::where('subject_type', QualityCertificate::class)
            ->where('subject_id', $certificate->id)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->get();

        $compliantLogs = $logs->filter(fn ($log) => $log->iso_compliant);

        $complianceRate = $logs->count() > 0
            ? ($compliantLogs->count() / $logs->count()) * 100
            : 100;

        $this->line("Certificado n.º {$certificate->id} ({$certificate->code}):");
        $this->line("  Total de revisões: {$certificate->revisions->count()}");
        $this->line("  Registos de actividade: {$logs->count()}");
        $this->line('  Taxa de conformidade: '.number_format($complianceRate, 2).'%');

        if ($complianceRate < 100) {
            $nonCompliant = $logs->reject(fn ($log) => $log->iso_compliant);
            $this->warn('  Foram encontrados registos não conformes:');

            foreach ($nonCompliant as $log) {
                $this->warn("    - Registo n.º {$log->id}: {$log->description}");
            }
        }

        $this->line('');
    }
}

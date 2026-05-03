<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\QualityCertificate;
use App\Models\ISOActivityLog;
use Carbon\Carbon;

class ISOComplianceAudit extends Command
{
    protected $signature = 'iso:audit 
        {--certificate= : Specific certificate ID}
        {--days=30 : Lookback period in days}';
    
    protected $description = 'Audit ISO 17025 compliance for Quality Certificates';
    
    public function handle()
    {
        $certificateId = $this->option('certificate');
        $days = $this->option('days');
        
        $query = QualityCertificate::query();
        
        if ($certificateId) {
            $query->where('id', $certificateId);
        }
        
        $certificates = $query->with(['revisions', 'currentRevision'])->get();
        
        $this->info("Auditing ISO 17025 compliance for {$certificates->count()} certificates...");
        
        foreach ($certificates as $certificate) {
            $this->auditCertificate($certificate, $days);
        }
        
        $this->info('Audit complete.');
    }
    
    private function auditCertificate(QualityCertificate $certificate, int $days)
    {
        $logs = IsoActivityLog::where('subject_type', QualityCertificate::class)
            ->where('subject_id', $certificate->id)
            ->where('created_at', '>=', Carbon::now()->subDays($days))
            ->get();
        
        $compliantLogs = $logs->filter(fn($log) => $log->iso_compliant);
        
        $complianceRate = $logs->count() > 0 
            ? ($compliantLogs->count() / $logs->count()) * 100 
            : 100;
        
        $this->line("Certificate #{$certificate->id} ({$certificate->code}):");
        $this->line("  Total revisions: {$certificate->revisions->count()}");
        $this->line("  Activity logs: {$logs->count()}");
        $this->line("  Compliance rate: " . number_format($complianceRate, 2) . "%");
        
        if ($complianceRate < 100) {
            $nonCompliant = $logs->reject(fn($log) => $log->iso_compliant);
            $this->warn("  Non-compliant logs found:");
            
            foreach ($nonCompliant as $log) {
                $this->warn("    - Log #{$log->id}: {$log->description}");
            }
        }
        
        $this->line('');
    }
}
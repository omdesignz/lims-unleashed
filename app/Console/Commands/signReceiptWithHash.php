<?php

namespace App\Console\Commands;

use App\Models\InvoiceReceipt;
use App\Models\Receipt;
use App\Support\DocumentSignature;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use LogicException;

class signReceiptWithHash extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sign-receipt-with-hash {receipt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assinar o recibo com um resumo criptográfico e guardá-lo na base de dados';

    /**
     * Execute the console command.
     */
    public function handle(DocumentSignature $documentSignature): int
    {
        return DB::transaction(function () use ($documentSignature): int {
            $receipt = Receipt::with('items')->lockForUpdate()->findOrFail($this->argument('receipt'));
            if (filled($receipt->unique_hash)) {
                return self::SUCCESS;
            }
            $lines = $receipt->items()->orderBy('id')->lockForUpdate()->get();
            $receipt->setRelation('items', $lines);
            $lineSnapshot = $lines->map(fn (InvoiceReceipt $line): array => $line->getAttributes())->all();
            $previousHash = Receipt::withoutGlobalScope('financial_laboratory')->withTrashed()
                ->where('rec_month', $receipt->rec_month)->where('id', '<', $receipt->id)
                ->whereNotNull('unique_hash')->where('unique_hash', '!=', '')->orderByDesc('id')->value('unique_hash');
            $data = $receipt->date.';'.$receipt->created_at->toDateTimeLocalString().';'.$receipt->rec_no.';'.$receipt->items->sum('paid_amount').';'.$previousHash;
            $signature = $documentSignature->sign($data);
            if (blank($signature)) {
                throw new LogicException('Receipt signing did not produce a signature.');
            }
            $expected = [...Arr::except($receipt->getAttributes(), ['updated_at']), 'unique_hash' => $signature];
            $receipt->unique_hash = $signature;

            if (! $receipt->save()) {
                throw new LogicException('Receipt signature was not persisted.');
            }
            if (Arr::except($receipt->fresh()->getAttributes(), ['updated_at']) !== $expected
                || $receipt->items()->withTrashed()->orderBy('id')->get()->map(fn (InvoiceReceipt $line): array => $line->getAttributes())->all() !== $lineSnapshot) {
                throw new LogicException('Receipt content, allocations or signature changed during signing.');
            }

            return self::SUCCESS;
        }, 3);
    }
}

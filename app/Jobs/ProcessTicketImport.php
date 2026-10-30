<?php

namespace App\Jobs;

use App\Enums\ImportStatus;
use App\Models\ImportBatch;
use App\Services\Import\TicketCsvImporter;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessTicketImport implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public int $timeout = 3600;

    public function __construct(public int $importBatchId) {}

    public function handle(TicketCsvImporter $importer): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }
        $importer->process(ImportBatch::findOrFail($this->importBatchId));
    }

    public function failed(Throwable $exception): void
    {
        $batch = ImportBatch::find($this->importBatchId);
        if ($batch !== null) {
            $batch->update(['status' => ImportStatus::Failed, 'finished_at' => now()]);
            Storage::disk(config('imports.disk'))->delete($batch->file_path);
        }

        Log::error('Ticket import failed.', [
            'import_batch_id' => $this->importBatchId,
            'exception' => $exception,
        ]);
    }
}

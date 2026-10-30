<?php

namespace App\Http\Controllers;

use App\Enums\ImportStatus;
use App\Http\Requests\StoreImportRequest;
use App\Jobs\ProcessTicketImport;
use App\Models\ImportBatch;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use League\Csv\Writer;

class ImportController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ImportBatch::class);

        $query = ImportBatch::query()->latest();
        if (! $request->user()->hasRole('admin')) {
            $query->where('user_id', $request->user()->id);
        }

        return view('imports.index', ['batches' => $query->paginate(15)]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', ImportBatch::class);

        return view('imports.create');
    }

    public function store(StoreImportRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $required = config('imports.required_columns');
        $stream = fopen($file->getRealPath(), 'rb');
        $headers = fgetcsv($stream) ?: [];
        fclose($stream);
        $headers = array_map(fn (string $header): string => strtolower(trim(ltrim($header, "\xEF\xBB\xBF"))), $headers);
        $missing = array_values(array_diff($required, $headers));

        if ($missing !== []) {
            return back()
                ->withInput()
                ->withErrors(['file' => 'Missing required columns: '.implode(', ', $missing)]);
        }

        $path = $file->store('imports', config('imports.disk'));
        $batch = ImportBatch::create([
            'user_id' => $request->user()->id,
            'original_name' => $file->getClientOriginalName(),
            'file_path' => $path,
            'status' => ImportStatus::Pending,
        ]);

        $pending = Bus::batch([new ProcessTicketImport($batch->id)])
            ->name('ticket-import-'.$batch->id)
            ->dispatch();
        $batch->update(['job_batch_id' => $pending->id]);

        return to_route('imports.show', $batch);
    }

    public function template(Request $request)
    {
        Gate::authorize('create', ImportBatch::class);

        return response()->streamDownload(function (): void {
            $writer = Writer::createFromStream(fopen('php://output', 'w'));
            $writer->insertOne(array_merge(config('imports.required_columns'), config('imports.optional_columns')));
            $writer->insertOne(['Example subject', 'Example description', 'medium', 'general-inquiry', 'customer@example.com', 'open', 'EXT-001', now()->subDay()->toDateString()]);
            $writer->insertOne(['Another subject', 'Another description', 'low', 'general-inquiry', 'customer@example.com', 'resolved', 'EXT-002', now()->subDays(2)->toDateString()]);
        }, 'tickets-import-template.csv', ['Content-Type' => 'text/csv']);
    }

    public function show(Request $request, ImportBatch $importBatch): View
    {
        Gate::authorize('view', $importBatch);

        return view('imports.show', [
            'batch' => $importBatch,
            'errors' => $importBatch->errors()->latest('row_number')->paginate(25),
        ]);
    }

    public function errors(Request $request, ImportBatch $importBatch)
    {
        Gate::authorize('view', $importBatch);

        return response()->streamDownload(function () use ($importBatch): void {
            $writer = Writer::createFromStream(fopen('php://output', 'w'));
            $first = $importBatch->errors()->cursor()->first();
            $headers = array_keys($first?->raw_data ?? []);
            $writer->insertOne(array_merge(['row_number', 'error_message'], $headers));

            foreach ($importBatch->errors()->cursor() as $error) {
                $row = [];
                foreach ($headers as $header) {
                    $value = $error->raw_data[$header] ?? '';
                    $row[] = $this->escapeFormula((string) $value);
                }
                $writer->insertOne(array_merge([$error->row_number, $this->escapeFormula($error->error_message)], $row));
            }
        }, 'import-errors-'.$importBatch->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    private function escapeFormula(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}

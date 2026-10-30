<?php

namespace App\Services\Import;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\ImportBatch;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Sla\SlaCalculator;
use App\Services\TicketNumberGenerator;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use League\Csv\Reader;

class TicketCsvImporter
{
    public function __construct(
        private readonly SlaCalculator $calculator,
        private readonly TicketNumberGenerator $numbers,
    ) {}

    public function process(ImportBatch $batch): void
    {
        DB::disableQueryLog();

        $disk = Storage::disk(config('imports.disk'));
        $path = $disk->path($batch->file_path);
        $reader = Reader::createFromPath($path);
        $reader->setHeaderOffset(0);
        $headers = array_map(fn (string $header): string => $this->normalizeHeader($header), $reader->getHeader());

        $total = 0;
        foreach ($reader->getRecords() as $record) {
            $total++;
        }

        $batch->update([
            'status' => 'processing',
            'started_at' => now(),
            'total_rows' => $total,
        ]);

        $seenReferences = [];
        $chunk = [];
        $line = 1;

        foreach ($reader->getRecords() as $record) {
            $line++;
            $chunk[] = ['line' => $line, 'row' => $this->normalizeRow($record, $headers)];

            if (count($chunk) >= (int) config('imports.chunk_size', 500)) {
                $this->processChunk($batch, $chunk, $seenReferences);
                $chunk = [];
            }
        }

        if ($chunk !== []) {
            $this->processChunk($batch, $chunk, $seenReferences);
        }

        $batch->update([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
        $disk->delete($batch->file_path);
    }

    /**
     * @param  array<int, array{line: int, row: array<string, string|null>}>  $chunk
     * @param  array<string, bool>  $seenReferences
     */
    private function processChunk(ImportBatch $batch, array $chunk, array &$seenReferences): void
    {
        $references = collect($chunk)
            ->map(fn (array $item): ?string => $item['row']['external_reference'] ?? null)
            ->filter(fn (?string $value): bool => $value !== null && $value !== '')
            ->values();

        $existing = $references->isEmpty()
            ? collect()
            : Ticket::withTrashed()->whereIn('external_reference', $references)->pluck('external_reference');

        $categories = DB::table('categories')
            ->where('is_active', true)
            ->whereIn('slug', collect($chunk)->pluck('row.category_slug')->filter()->unique())
            ->pluck('id', 'slug');
        $customers = User::query()
            ->role('customer')
            ->whereIn('email', collect($chunk)->pluck('row.customer_email')->filter()->unique())
            ->get()
            ->keyBy('email');

        $errors = [];
        $valid = [];
        $duplicates = 0;

        foreach ($chunk as $item) {
            $row = $item['row'];
            $reference = $row['external_reference'] ?? null;

            if ($reference !== null && $reference !== ''
                && ($existing->contains($reference) || isset($seenReferences[$reference]))) {
                $duplicates++;

                continue;
            }

            $row['priority'] = strtolower((string) ($row['priority'] ?? ''));
            $row['status'] = strtolower((string) ($row['status'] ?? TicketStatus::Open->value));
            $validator = Validator::make($row, [
                'subject' => ['required', 'string', 'max:255'],
                'description' => ['required', 'string', 'max:10000'],
                'priority' => ['required', Rule::in(TicketPriority::values())],
                'category_slug' => ['required', Rule::in($categories->keys()->all())],
                'customer_email' => ['required', 'email', Rule::in($customers->keys()->all())],
                'status' => [Rule::in(TicketStatus::values())],
                'external_reference' => ['nullable', 'string', 'max:100'],
                'created_at' => ['nullable', 'date'],
            ]);

            $createdAt = null;
            if ($validator->passes()) {
                $createdAt = $row['created_at'] === null || $row['created_at'] === ''
                    ? now(config('sla.timezone'))
                    : Carbon::parse($row['created_at'], config('sla.timezone'));

                if ($createdAt->isFuture()) {
                    $validator->errors()->add('created_at', 'The created_at must not be in the future.');
                }
            }

            if ($validator->fails()) {
                $errors[] = [
                    'import_batch_id' => $batch->id,
                    'row_number' => $item['line'],
                    'raw_data' => json_encode($row),
                    'error_message' => implode('; ', $validator->errors()->all()),
                    'created_at' => now(),
                ];

                continue;
            }

            $valid[] = [
                'row' => $row,
                'created_at' => $createdAt,
                'category_id' => $categories[$row['category_slug']],
                'customer_id' => $customers[$row['customer_email']]->id,
            ];
            if ($reference !== null && $reference !== '') {
                $seenReferences[$reference] = true;
            }
        }

        $imported = count($valid);
        if ($valid !== []) {
            try {
                DB::transaction(function () use ($batch, $valid): void {
                    $this->insertValid($batch, $valid);
                });
            } catch (QueryException $exception) {
                if (! str_contains(strtolower($exception->getMessage()), 'unique')) {
                    throw $exception;
                }

                $imported = $this->insertValidIndividually($batch, $valid);
            }
        }

        if ($errors !== []) {
            DB::table('import_errors')->insert($errors);
        }

        $batch->increment('processed_rows', count($chunk));
        $batch->increment('imported_rows', $imported);
        $batch->increment('duplicate_rows', $duplicates);
        $batch->increment('failed_rows', count($errors));
    }

    /**
     * @param  array<int, array{row: array<string, string|null>, created_at: Carbon, category_id: int, customer_id: int}>  $valid
     */
    private function insertValid(ImportBatch $batch, array $valid): void
    {
        $numbers = [];
        foreach (collect($valid)->groupBy(fn (array $item): int => $item['created_at']->year) as $year => $rows) {
            $numbers[$year] = $this->numbers->reserveBlock((int) $year, $rows->count());
        }

        $payload = [];
        $numberIndex = [];
        foreach ($valid as $item) {
            $year = $item['created_at']->year;
            $number = array_shift($numbers[$year]);
            $status = TicketStatus::from($item['row']['status']);
            $sla = $this->calculator->forPriority(TicketPriority::from($item['row']['priority']), $item['created_at']);
            $timestamp = now();
            $payload[] = [
                'ticket_number' => $number,
                'subject' => $item['row']['subject'],
                'description' => $item['row']['description'],
                'priority' => $item['row']['priority'],
                'status' => $status->value,
                'category_id' => $item['category_id'],
                'created_by' => $item['customer_id'],
                'assigned_agent_id' => null,
                'sla_policy_id' => $sla['policy_id'],
                'sla_minutes' => $sla['minutes'],
                'sla_due_at' => $sla['due_at']->format('Y-m-d H:i:s'),
                'sla_warning_at' => $sla['warning_at']->format('Y-m-d H:i:s'),
                'resolved_at' => $status === TicketStatus::Resolved ? $timestamp : null,
                'closed_at' => $status === TicketStatus::Closed ? $timestamp : null,
                'external_reference' => $item['row']['external_reference'] ?: null,
                'import_batch_id' => $batch->id,
                'created_at' => $item['created_at']->utc()->format('Y-m-d H:i:s'),
                'updated_at' => $timestamp,
            ];
            $numberIndex[] = [$number, $item['row']['external_reference'] ?? null];
        }

        DB::table('tickets')->insert($payload);
        $this->insertActivities($batch, $numberIndex);
    }

    /**
     * @param  array<int, array{row: array<string, string|null>, created_at: Carbon, category_id: int, customer_id: int}>  $valid
     */
    private function insertValidIndividually(ImportBatch $batch, array $valid): int
    {
        $imported = 0;
        foreach ($valid as $item) {
            try {
                DB::transaction(function () use ($batch, $item): void {
                    $this->insertValid($batch, [$item]);
                });
                $imported++;
            } catch (QueryException $exception) {
                if (! str_contains(strtolower($exception->getMessage()), 'unique')) {
                    throw $exception;
                }
            }
        }

        return $imported;
    }

    /**
     * @param  array<int, array{0: string, 1: string|null}>  $numbers
     */
    private function insertActivities(ImportBatch $batch, array $numbers): void
    {
        $tickets = Ticket::query()->whereIn('ticket_number', array_column($numbers, 0))->get()->keyBy('ticket_number');
        $timestamp = now();
        $rows = [];
        foreach ($numbers as [$number, $reference]) {
            $ticket = $tickets[$number];
            $rows[] = [
                'log_name' => 'ticket',
                'description' => "Ticket imported (batch #{$batch->id})",
                'subject_type' => Ticket::class,
                'subject_id' => $ticket->id,
                'event' => 'created',
                'causer_type' => User::class,
                'causer_id' => $batch->user_id,
                'properties' => json_encode(['import_batch_id' => $batch->id, 'external_reference' => $reference]),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }
        DB::table('activity_log')->insert($rows);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string|null>
     */
    private function normalizeRow(array $record, array $headers): array
    {
        $normalized = [];
        foreach ($record as $header => $value) {
            $normalized[$this->normalizeHeader((string) $header)] = $value === '' ? null : $value;
        }

        return array_replace(array_fill_keys($headers, null), $normalized);
    }

    private function normalizeHeader(string $header): string
    {
        return strtolower(trim(ltrim($header, "\xEF\xBB\xBF")));
    }
}

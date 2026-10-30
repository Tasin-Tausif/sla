<?php

namespace App\Console\Commands;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Category;
use App\Models\User;
use Illuminate\Console\Command;
use League\Csv\Writer;

class GenerateImportSampleCommand extends Command
{
    protected $signature = 'imports:generate-sample {rows=10000} {--invalid=5 : percent of invalid rows} {--duplicates=2 : percent of duplicate external references}';

    protected $description = 'Generate a streaming CSV sample for ticket imports';

    public function handle(): int
    {
        $path = storage_path('app/samples/tickets-sample.csv');
        $directory = dirname($path);
        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        $customers = User::query()->role('customer')->pluck('email')->all();
        $categories = Category::query()->where('is_active', true)->pluck('slug')->all();
        if ($customers === [] || $categories === []) {
            $this->error('Seed customers and categories before generating a sample.');

            return self::FAILURE;
        }

        $writer = Writer::createFromPath($path, 'w');
        $writer->insertOne(array_merge(config('imports.required_columns'), config('imports.optional_columns')));
        $duplicateReference = null;
        $rows = (int) $this->argument('rows');

        for ($i = 1; $i <= $rows; $i++) {
            $invalid = random_int(1, 100) <= (int) $this->option('invalid');
            $duplicate = random_int(1, 100) <= (int) $this->option('duplicates');
            $reference = $duplicate && $duplicateReference !== null
                ? $duplicateReference
                : 'SAMPLE-'.str_pad((string) $i, 8, '0', STR_PAD_LEFT);
            $duplicateReference ??= $reference;
            $writer->insertOne([
                $invalid ? '' : 'Sample ticket '.$i,
                'Generated sample ticket description.',
                $invalid ? 'invalid' : TicketPriority::cases()[$i % 4]->value,
                $categories[$i % count($categories)],
                $customers[$i % count($customers)],
                TicketStatus::cases()[$i % count(TicketStatus::cases())]->value,
                $reference,
                now()->subDays(random_int(0, 60))->toDateString(),
            ]);
        }

        $this->info($path);

        return self::SUCCESS;
    }
}

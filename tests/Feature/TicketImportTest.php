<?php

use App\Enums\ImportStatus;
use App\Events\TicketCreated;
use App\Models\ImportBatch;
use App\Models\Ticket;
use App\Models\User;
use App\Services\Import\TicketCsvImporter;
use Database\Seeders\BusinessHoursSeeder;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SlaPolicySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    $this->seed([CategorySeeder::class, SlaPolicySeeder::class, BusinessHoursSeeder::class]);
    config(['imports.chunk_size' => 3]);
});

function importCustomer(): User
{
    $user = User::factory()->create(['email' => 'import-customer@example.com']);
    $user->assignRole('customer');

    return $user;
}

function importAgent(): User
{
    $user = User::factory()->create();
    $user->assignRole('agent');

    return $user;
}

function importBatch(User $user, string $contents): ImportBatch
{
    Storage::disk('local')->put('imports/test.csv', $contents);

    return ImportBatch::create([
        'user_id' => $user->id,
        'original_name' => 'test.csv',
        'file_path' => 'imports/test.csv',
        'status' => ImportStatus::Pending,
    ]);
}

it('processes valid rows in chunks without ticket side effects', function () {
    Event::fake();
    Notification::fake();
    $customer = importCustomer();
    $csv = implode("\n", [
        'subject,description,priority,category_slug,customer_email,status,external_reference,created_at',
        'First,Description,medium,general-inquiry,'.$customer->email.',open,EXT-1,2026-10-01 10:00:00',
        'Second,Description,high,general-inquiry,'.$customer->email.',resolved,EXT-2,2026-10-02 10:00:00',
    ]);
    $batch = importBatch($customer, $csv);

    app(TicketCsvImporter::class)->process($batch);

    expect(Ticket::where('import_batch_id', $batch->id)->count())->toBe(2)
        ->and($batch->fresh()->status)->toBe(ImportStatus::Completed)
        ->and($batch->fresh()->processed_rows)->toBe(2)
        ->and($batch->fresh()->imported_rows)->toBe(2)
        ->and($batch->fresh()->failed_rows)->toBe(0);
    Event::assertNotDispatched(TicketCreated::class);
    Notification::assertNothingSent();
});

it('counts duplicates and stores invalid rows with CSV line numbers', function () {
    $customer = importCustomer();
    Ticket::factory()->create(['external_reference' => 'EXISTING']);
    $csv = implode("\n", [
        'subject,description,priority,category_slug,customer_email,external_reference',
        'Duplicate,Description,medium,general-inquiry,'.$customer->email.',EXISTING',
        'Duplicate again,Description,medium,general-inquiry,'.$customer->email.',EXISTING',
        ',Description,not-a-priority,unknown,not-an-email,',
    ]);
    $batch = importBatch($customer, $csv);

    app(TicketCsvImporter::class)->process($batch);

    $fresh = $batch->fresh();
    expect($fresh->duplicate_rows)->toBe(2)
        ->and($fresh->failed_rows)->toBe(1)
        ->and($fresh->errors()->first()->row_number)->toBe(4)
        ->and($fresh->processed_rows)->toBe(3);
});

it('allows authorized upload and rejects customers', function () {
    $customer = importCustomer();
    $this->actingAs($customer)->get(route('imports.index'))->assertForbidden();

    $agent = importAgent();
    Bus::fake();
    $file = UploadedFile::fake()->createWithContent('tickets.csv', implode("\n", [
        'subject,description,priority,category_slug,customer_email',
        'Subject,Description,medium,general-inquiry,'.$customer->email,
    ]));

    $this->actingAs($agent)
        ->post(route('imports.store'), ['file' => $file])
        ->assertRedirect();
    expect(ImportBatch::count())->toBe(1);
});

<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

it('renders reusable form and button components', function () {
    view()->share('errors', new ViewErrorBag);
    request()->setLaravelSession(app('session')->driver());
    request()->session()->flashInput([
        'subject' => 'Remembered subject',
        'status' => 'closed',
        'description' => 'Remembered description',
    ]);

    $html = Blade::render(<<<'BLADE'
        <x-button>Save</x-button>
        <x-button href="/tickets" variant="danger" disabled>Delete</x-button>
        <x-form.input name="subject" />
        <x-form.input name="password" type="password" />
        <x-form.select name="status" :options="['open' => 'Open', 'closed' => 'Closed']" selected="open" />
        <x-form.textarea name="description" />
        <x-form.checkbox name="enabled" label="Enabled" :checked="true" />
        <x-form.field name="attachments" label="Attachments"><x-form.input name="attachments" /></x-form.field>
    BLADE, ['errors' => new ViewErrorBag]);

    expect($html)
        ->toContain('<button type="submit"')
        ->toContain('<a href="/tickets"')
        ->toContain('bg-red-600')
        ->toContain('disabled')
        ->toContain('value="Remembered subject"')
        ->toContain('value="closed" selected')
        ->toContain('Remembered description')
        ->toContain('name="enabled"')
        ->toContain('Attachments');
    expect($html)->not->toContain('Remembered subject" type="password"');
});

it('shows nested field errors and error styling', function () {
    request()->setLaravelSession(app('session')->driver());
    view()->share('errors', (new ViewErrorBag)->put('default', new MessageBag(['attachments.0' => 'Invalid attachment'])));

    $view = Blade::render(
        '<x-form.field name="attachments" label="Attachments"><x-form.input name="attachments" /></x-form.field>',
    );

    expect($view)->toContain('Invalid attachment')->toContain('form-control-error');
});

it('contains no raw non-hidden controls outside components', function () {
    $root = resource_path('views');
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
    $violations = [];

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php' || str_contains($file->getPath(), DIRECTORY_SEPARATOR.'components')) {
            continue;
        }

        $lines = file($file->getPathname());
        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/<\s*(button|select|textarea)\b/i', $line) === 1
                || (preg_match('/<\s*input\b/i', $line) === 1 && preg_match('/type\s*=\s*[\'"]hidden[\'"]/i', $line) !== 1)) {
                $violations[] = $file->getPathname().':'.($lineNumber + 1);
            }
        }
    }

    expect($violations)->toBe([]);
});

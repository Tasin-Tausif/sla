<?php

it('keeps Blade colours behind semantic theme tokens', function () {
    $pattern = '/(?:bg|text|border|ring|divide|placeholder|from|to|via|fill|stroke|outline|decoration|accent|caret|shadow)-(?:slate|gray|zinc|neutral|stone|red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose)-\d{2,3}\b|(?:bg-white|bg-black|text-white|text-black)/';
    $whitelist = [];
    $violations = [];

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php' || ($whitelist[$file->getFilename()] ?? false)) {
            continue;
        }

        foreach (file($file->getPathname()) as $line => $contents) {
            if (preg_match($pattern, $contents) === 1 || preg_match('/style\s*=\s*["\'][^"\']*(?:#|rgb\s*\(|hsl\s*\()/i', $contents) === 1) {
                $violations[] = $file->getPathname().':'.($line + 1);
            }
        }
    }

    expect($violations)->toBe([]);
});

it('keeps CSS colour literals inside the token block', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $start = strpos($css, '/* TOKENS:START */');
    $end = strpos($css, '/* TOKENS:END */');
    $outside = substr($css, 0, $start).substr($css, $end + strlen('/* TOKENS:END */'));

    expect($outside)->not->toMatch('/#[0-9a-f]{3,8}\b|rgb\s*\(|hsl\s*\(/i');
});

it('defines every required theme token', function () {
    $css = file_get_contents(resource_path('css/app.css'));
    $tokens = [
        'bg', 'surface', 'surface-muted', 'border', 'text', 'text-muted', 'text-inverse',
        'primary', 'primary-hover', 'primary-soft', 'on-primary', 'secondary', 'danger',
        'danger-soft', 'success', 'success-soft', 'warning', 'warning-soft', 'info',
        'info-soft', 'focus-ring', 'overlay', 'radius-sm', 'radius-md', 'radius-lg',
        'shadow-card', 'shadow-pop', 'font-sans', 'container-max', 'page-padding', 'control-height',
        'status-open', 'status-open-soft', 'status-in-progress', 'status-in-progress-soft',
        'status-waiting', 'status-waiting-soft', 'status-resolved', 'status-resolved-soft',
        'status-closed', 'status-closed-soft', 'priority-low', 'priority-low-soft',
        'priority-medium', 'priority-medium-soft', 'priority-high', 'priority-high-soft',
        'priority-critical', 'priority-critical-soft', 'sla-within', 'sla-within-soft',
        'sla-approaching', 'sla-approaching-soft', 'sla-breached', 'sla-breached-soft',
        'sla-resolved', 'sla-resolved-soft',
    ];

    foreach ($tokens as $token) {
        $prefix = in_array($token, [
            'radius-sm', 'radius-md', 'radius-lg', 'shadow-card', 'shadow-pop',
            'font-sans', 'container-max', 'page-padding', 'control-height',
            'status-open', 'status-open-soft', 'status-in-progress', 'status-in-progress-soft',
            'status-waiting', 'status-waiting-soft', 'status-resolved', 'status-resolved-soft',
            'status-closed', 'status-closed-soft', 'priority-low', 'priority-low-soft',
            'priority-medium', 'priority-medium-soft', 'priority-high', 'priority-high-soft',
            'priority-critical', 'priority-critical-soft', 'sla-within', 'sla-within-soft',
            'sla-approaching', 'sla-approaching-soft', 'sla-breached', 'sla-breached-soft',
            'sla-resolved', 'sla-resolved-soft',
        ], true) ? '--' : '--color-';

        expect($css)->toContain($prefix.$token);
    }
});

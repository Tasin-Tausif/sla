<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckUploadsCommand extends Command
{
    protected $signature = 'uploads:check';

    protected $description = 'Check PHP and application upload settings';

    public function handle(): int
    {
        $uploadMax = $this->toKilobytes(ini_get('upload_max_filesize'));
        $postMax = $this->toKilobytes(ini_get('post_max_size'));
        $tmp = sys_get_temp_dir();

        $this->line('file_uploads: '.ini_get('file_uploads'));
        $this->line('upload_max_filesize: '.ini_get('upload_max_filesize'));
        $this->line('post_max_size: '.ini_get('post_max_size'));
        $this->line('max_file_uploads: '.ini_get('max_file_uploads'));
        $this->line('upload_tmp_dir: '.(ini_get('upload_tmp_dir') ?: '(default)'));
        $this->line('sys_get_temp_dir(): '.$tmp);
        $this->line('temporary directory writable: '.(is_writable($tmp) ? 'yes' : 'no'));
        $this->line('imports.max_upload_kb: '.config('imports.max_upload_kb'));
        $this->line('tickets.attachments.max_kb: '.config('tickets.attachments.max_kb'));

        $configuredLimit = max(
            (int) config('imports.max_upload_kb'),
            (int) config('tickets.attachments.max_kb'),
        );

        if ($uploadMax < $configuredLimit || $postMax < $configuredLimit) {
            $this->warn('upload_max_filesize or post_max_size is smaller than a configured upload limit.');
        }

        $this->line("CLI values can differ from the web server's php.ini; confirm via phpinfo() in the web SAPI if uploads still fail.");

        return self::SUCCESS;
    }

    private function toKilobytes(string|false $value): int
    {
        if ($value === false || $value === '') {
            return 0;
        }

        $number = (float) $value;
        $unit = strtolower(substr(trim($value), -1));

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024,
            'm' => $number * 1024,
            'k' => $number,
            default => $number / 1024,
        };
    }
}

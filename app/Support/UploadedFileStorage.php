<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final class UploadedFileStorage
{
    public static function store(UploadedFile $file, string $directory, string $disk, string $errorKey): string
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                $errorKey => 'Upload failed: '.$file->getErrorMessage(),
            ]);
        }

        $path = $file->getPathname();
        $error = 'The uploaded file could not be read from PHP\'s temporary folder. Check upload_tmp_dir and folder permissions.';

        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            Log::warning('Uploaded file temporary path is unavailable.', [
                'pathname' => $path,
                'error_code' => $file->getError(),
                'size' => $file->getSize(),
            ]);

            throw ValidationException::withMessages([
                $errorKey => config('app.debug') ? $error.' Path: '.$path : $error,
            ]);
        }

        $stream = fopen($path, 'rb');

        if ($stream === false) {
            Log::warning('Uploaded file temporary path could not be opened.', [
                'pathname' => $path,
                'error_code' => $file->getError(),
                'size' => $file->getSize(),
            ]);

            throw ValidationException::withMessages([
                $errorKey => config('app.debug') ? $error.' Path: '.$path : $error,
            ]);
        }

        try {
            $extension = $file->guessExtension() ?: strtolower($file->getClientOriginalExtension());
            $storedPath = trim($directory, '/').'/'.Str::random(40).($extension !== '' ? '.'.$extension : '');

            if (! Storage::disk($disk)->put($storedPath, $stream)) {
                throw ValidationException::withMessages([
                    $errorKey => 'The uploaded file could not be stored.',
                ]);
            }

            return $storedPath;
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param  array<int, string>  $paths
     */
    public static function delete(string $disk, array $paths): void
    {
        foreach ($paths as $path) {
            try {
                Storage::disk($disk)->delete($path);
            } catch (Throwable) {
                // Cleanup must not hide the original upload or database error.
            }
        }
    }
}

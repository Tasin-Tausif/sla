<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function download(Attachment $attachment): StreamedResponse
    {
        $attachment->loadMissing(['reply', 'ticket']);

        Gate::authorize('downloadAttachment', [$attachment->ticket, $attachment]);

        return Storage::disk($attachment->disk)->download(
            $attachment->stored_path,
            $attachment->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }
}

<?php

return [

    'search' => [
        'fulltext' => (bool) env('TICKETS_FULLTEXT_SEARCH', true),
    ],

    'attachments' => [
        'disk' => env('TICKETS_ATTACHMENTS_DISK', 'local'),
        'max_kb' => 5120,
        'max_files' => 5,
        'mimes' => ['pdf', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'csv', 'docx', 'xlsx'],
    ],

];

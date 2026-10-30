<?php

return [
    'chunk_size' => 500,
    'max_upload_kb' => 20480,
    'disk' => 'local',
    'required_columns' => ['subject', 'description', 'priority', 'category_slug', 'customer_email'],
    'optional_columns' => ['status', 'external_reference', 'created_at'],
];

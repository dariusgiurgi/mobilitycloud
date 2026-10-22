<?php

return [
    'temporary_file_upload' => [
        // Per-file ceiling matches the largest existing mobility upload.
        'rules' => ['required', 'file', 'max:51200'],
        'max_upload_time' => 10,
    ],
];

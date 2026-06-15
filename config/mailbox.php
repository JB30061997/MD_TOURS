<?php

return [
    'imap' => [
        'host' => env('IMAP_HOST', 'mail.md-tours.com'),
        'port' => (int) env('IMAP_PORT', 993),
        'encryption' => env('IMAP_ENCRYPTION', 'ssl'),
        'validate_cert' => filter_var(env('IMAP_VALIDATE_CERT', false), FILTER_VALIDATE_BOOLEAN),
    ],

    'smtp' => [
        'host' => env('MAIL_HOST', 'mail.md-tours.com'),
        'port' => (int) env('MAIL_PORT', 465),
        'encryption' => env('MAIL_ENCRYPTION', 'ssl'),
    ],
];

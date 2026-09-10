<?php

return [
    'encryption_key' => env('MESSAGE_ENCRYPTION_KEY'),
    'encryption_key_version' => (int) env('MESSAGE_ENCRYPTION_KEY_VERSION', 1),
    'previous_encryption_keys' => env('MESSAGE_ENCRYPTION_PREVIOUS_KEYS', ''),
];

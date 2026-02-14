<?php
// =======================
// promptAllChat (private chat) - config
// =======================

return [
  'project' => [
    'name' => 'promptAllChat',
    'designer' => 'امید قدسی زاده',
  ],
  'db' => [
    'dsn'  => 'mysql:host=localhost;dbname=YOUR_DB_NAME;charset=utf8mb4',
    'user' => 'YOUR_DB_USER',
    'pass' => 'YOUR_DB_PASS',
    'options' => [
      PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES   => false,
    ],
  ],
  'app_key' => 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET',
  'limits' => [
    'message_max_len' => 2000,
    'username_max_len' => 32,
    'upload_max_bytes' => 15 * 1024 * 1024, // 15MB
  ],
  'uploads' => [
    'dir' => __DIR__ . '/../uploads',
    'public_path' => 'uploads',
    'allowed_mimes' => [
      'image/jpeg', 'image/png', 'image/webp', 'image/gif',
      'application/pdf',
      'text/plain',
      'application/zip',
      'application/octet-stream',
    ],
  ],
];

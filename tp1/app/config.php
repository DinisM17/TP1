<?php
declare(strict_types=1);

return [
  'db' => [
    'host' => '127.0.0.1',
    'name' => 'iaedu_academicos',
    'user' => 'root',
    'pass' => '',         // XAMPP default
    'charset' => 'utf8mb4',
  ],
  'session' => [
    'name' => 'IAEDU_SESSID',
    'idle_timeout' => 1800, // 30 min
  ],
  'upload' => [
    'photos_dir' => __DIR__ . '/../public/uploads/photos',
    'max_bytes' => 2 * 1024 * 1024, // 2MB
    'allowed_mime' => ['image/jpeg' => 'jpg', 'image/png' => 'png'],
  ],
];
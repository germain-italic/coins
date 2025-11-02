#!/usr/bin/env php
<?php
// Mini serveur PHP built-in pour debug

$host = '127.0.0.1';
$port = 8000;
$docroot = __DIR__;

echo "Starting PHP built-in server...\n";
echo "URL: http://{$host}:{$port}/gallery/\n";
echo "Document root: {$docroot}\n";
echo "Press Ctrl+C to stop\n\n";

passthru("php -S {$host}:{$port} -t {$docroot}");

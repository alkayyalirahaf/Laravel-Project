<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$db = storage_path('app/browser-route-qa.sqlite');
if (file_exists($db)) {
    throw new RuntimeException('The QA database already exists; refusing to overwrite it.');
}
touch($db);
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $db]);
Illuminate\Support\Facades\DB::purge('sqlite');
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
$fixtures = Tests\Support\RouteFixtures::create();
echo json_encode(['database' => $db, 'flight' => $fixtures['flights'][0]->id,
    'depart' => $fixtures['flights'][0]->departure_at->toDateString(), 'booking' => $fixtures['booking']->code,
    'conversation' => $fixtures['conversation']->id, 'segment' => $fixtures['segment']->id], JSON_PRETTY_PRINT);

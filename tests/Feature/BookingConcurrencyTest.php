<?php

namespace Tests\Feature;

use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BookingConcurrencyTest extends TestCase
{
    public function test_two_processes_competing_for_the_last_seat_create_only_one_booking(): void
    {
        $database = tempnam(sys_get_temp_dir(), 'tourism-booking-');
        $barrier = $database.'.start';
        $workers = [];
        $readyFiles = [];
        $environment = ['APP_ENV' => 'testing', 'APP_KEY' => config('app.key'), 'DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => $database, 'DB_URL' => false, 'CACHE_STORE' => 'array', 'SESSION_DRIVER' => 'array', 'QUEUE_CONNECTION' => 'sync'];
        $workerPath = base_path('tests/Support/booking-concurrency-worker.php');
        try {
            (new Process([PHP_BINARY, 'artisan', 'migrate', '--force', '--no-interaction'], base_path(), $environment))->mustRun();
            $seed = new Process([PHP_BINARY, $workerPath, 'seed'], base_path(), $environment);
            $seed->mustRun();
            $fixture = json_decode($seed->getOutput(), true, flags: JSON_THROW_ON_ERROR);
            foreach ($fixture['users'] as $userId) {
                $worker = new Process([PHP_BINARY, $workerPath, 'reserve', (string) $userId, (string) $fixture['departure'], Str::uuid()->toString(), $barrier], base_path(), $environment);
                $worker->start();
                $workers[] = $worker;
                $readyFiles[] = $barrier.'.'.$userId.'.ready';
            }
            $deadline = microtime(true) + 5;
            while ((! file_exists($readyFiles[0]) || ! file_exists($readyFiles[1])) && microtime(true) < $deadline) {
                usleep(1000);
            }
            $this->assertFileExists($readyFiles[0]);
            $this->assertFileExists($readyFiles[1]);
            file_put_contents($barrier, 'start');
            $results = [];
            foreach ($workers as $worker) {
                $worker->wait();
                $this->assertTrue($worker->isSuccessful(), $worker->getErrorOutput().$worker->getOutput());
                $results[] = trim($worker->getOutput());
            }
            sort($results);
            $this->assertSame(['reserved', 'sold_out'], $results);
            $inspect = new Process([PHP_BINARY, $workerPath, 'inspect'], base_path(), $environment);
            $inspect->mustRun();
            $this->assertSame(['reserved' => 1, 'bookings' => 1, 'travelers' => 1], json_decode($inspect->getOutput(), true, flags: JSON_THROW_ON_ERROR));
        } finally {
            foreach ($workers as $worker) {
                if ($worker->isRunning()) {
                    $worker->stop();
                }
            }
            foreach ([$database, $database.'-journal', $database.'-wal', $database.'-shm', $barrier, ...$readyFiles] as $file) {
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }
    }
}

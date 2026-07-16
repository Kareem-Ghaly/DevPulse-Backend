<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class FirebaseTestCommand extends Command
{
    protected $signature = 'firebase:test';

    protected $description = 'Verify Firebase Messaging can be initialized from configuration.';

    public function handle(): int
    {
        $project = config('firebase.default', 'app');
        $credentialsPath = config("firebase.projects.{$project}.credentials");

        if (! is_string($credentialsPath) || trim($credentialsPath) === '') {
            $this->error('Firebase credentials path is missing. Set FIREBASE_CREDENTIALS to the service account JSON path.');

            return self::FAILURE;
        }

        if (! file_exists($credentialsPath) || ! is_file($credentialsPath)) {
            $this->error('Firebase credentials path is missing or invalid. Check FIREBASE_CREDENTIALS.');

            return self::FAILURE;
        }

        if (! is_readable($credentialsPath)) {
            $this->error('Firebase credentials file is not readable. Check file permissions for FIREBASE_CREDENTIALS.');

            return self::FAILURE;
        }

        try {
            Firebase::messaging();
        } catch (Throwable $e) {
            $this->error('Firebase connection configuration failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Firebase connection configured successfully');

        return self::SUCCESS;
    }
}
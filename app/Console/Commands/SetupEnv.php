<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupEnv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'env:setup {--force : Force overwrite existing .env file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup environment file from .env.example';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        // Check if .env.example exists
        if (!file_exists($envExamplePath)) {
            $this->error('.env.example file not found!');
            return 1;
        }

        // Check if .env already exists
        if (file_exists($envPath) && !$this->option('force')) {
            if (!$this->confirm('.env file already exists. Do you want to overwrite it?')) {
                $this->info('Setup cancelled.');
                return 0;
            }
        }

        // Copy .env.example to .env
        if (copy($envExamplePath, $envPath)) {
            $this->info('.env file created successfully!');

            // Ask if user wants to generate app key
            if ($this->confirm('Do you want to generate a new application key?', true)) {
                $this->call('key:generate');
            }

            $this->line('');
            $this->info('Next steps:');
            $this->line('1. Edit .env file and configure your settings');
            $this->line('2. Set DISCORD_WEBHOOK_URL');
            $this->line('3. Set FOURTHWALL_WEBHOOK_SECRET (optional)');

            return 0;
        } else {
            $this->error('Failed to create .env file!');
            return 1;
        }
    }
}

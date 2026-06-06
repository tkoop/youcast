<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;

class UpdateYtDlp extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'youcast:update-bin';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Download and update the latest yt-dlp binary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting yt-dlp update...');

        $binDir = base_path('bin');
        $binPath = $binDir . '/yt-dlp';

        if (!File::exists($binDir)) {
            File::makeDirectory($binDir, 0755, true);
            $this->info('Created bin directory.');
        }

        $url = 'https://github.com/yt-dlp/yt-dlp/releases/latest/download/yt-dlp';

        $this->info('Downloading latest binary from GitHub...');

        try {
            $response = Http::withOptions([
                'sink' => $binPath,
                'follow_redirects' => true,
            ])->get($url);

            if ($response->successful()) {
                chmod($binPath, 0755);
                $this->success('yt-dlp updated successfully at ' . $binPath);
                
                // Show version
                $version = shell_exec($binPath . ' --version');
                $this->line('New version: ' . trim($version));
            } else {
                $this->error('Failed to download binary. Status: ' . $response->status());
            }
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper to show success message (Laravel 11 compatibility)
     */
    protected function success($message)
    {
        $this->line("<info>SUCCESS</info> $message");
    }
}

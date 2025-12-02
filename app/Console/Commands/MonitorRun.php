<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonitoredSite;
use App\Services\DefacementDetectionService;

class MonitorRun extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:run';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run defacement checks for monitored sites (status UP)';

    protected DefacementDetectionService $service;

    public function __construct(DefacementDetectionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $this->info('Starting automated defacement monitoring (monitor:run)');

        $sites = MonitoredSite::where('status', 'UP')->get();
        if ($sites->isEmpty()) {
            $this->info('No sites with status UP to check.');
            return 0;
        }

        foreach ($sites as $site) {
            $this->line("Checking {$site->id_site} - {$site->site_url}...");
            try {
                $this->service->checkSite($site);
            } catch (\Exception $e) {
                $this->error("Error checking site {$site->site_url}: " . $e->getMessage());
            }
        }

        $this->info('Defacement monitoring run completed.');
        return 0;
    }
}

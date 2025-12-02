<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\MonitoredSite;
use App\Services\DefacementDetectionService;

class MonitorSites extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monitor:sites';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run defacement integrity checks for all monitored sites';

    protected DefacementDetectionService $service;

    public function __construct(DefacementDetectionService $service)
    {
        parent::__construct();
        $this->service = $service;
    }

    public function handle(): int
    {
        $this->info('Starting site monitoring...');

        $sites = MonitoredSite::all();
        foreach ($sites as $site) {
            $this->line("Checking site: {$site->id_site} -> {$site->site_url}");
            // Use new service method that accepts a MonitoredSite instance
            $this->service->checkSite($site);
        }

        $this->info('Site monitoring completed.');
        return 0;
    }
}

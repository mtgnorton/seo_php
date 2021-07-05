<?php

namespace App\Console\Commands;

use App\Services\Gather\CrawlService;
use App\Services\OnlineUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'test';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        OnlineUpdateService::update('http://seoweb.grayvip.com//storage/zip/20210702/1d8c31f6bef11b13ae78fcf81935d61f.zip', './storage/update/patch.zip');
    }


}

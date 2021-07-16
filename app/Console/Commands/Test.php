<?php

namespace App\Console\Commands;

use App\Services\Gather\CrawlService;
use App\Services\ImportAndExportService;
use App\Services\SystemUpdateService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use \Goose\Client as GooseClient;

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
        ImportAndExportService::zip(['storage/app/public/template', 'storage/app/public/mirror'], 'b.zip');
        echo 'finish';
    }


}

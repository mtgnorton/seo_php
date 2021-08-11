<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DeleteCacheFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '定时删除有实效的模板文件';

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
        // 获取缓存中的数据信息
        $cacheKey = 'cacheFileData';
        $data = Cache::store('file')->get($cacheKey);

        if (!empty($data)) {
            foreach ($data as $path => $time) {
                // 删除时间小于当前时间的文件
                if (time() > $time) {
                    if (Storage::disk('local')->exists($path)) {
                        Storage::disk('local')->delete($path);
                    }

                    unset($data[$path]);
                }
            }

            Cache::store('file')->put($cacheKey, $data);
        }
    }
}

<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessDeleteCache implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $groupId;

    /**
     * 最大执行秒数
     *
     * @var integer
     */
    public $timeout = 3000;

    /**
     * 任务可以尝试的最大次数
     *
     * @var integer
     */
    public $tries = 3;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($groupId)
    {
        $this->groupId = $groupId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // 判断当前分类下是否有文件
        try {
            $path = 'cache/templates/'.$this->groupId;
            if (Storage::disk('local')->directories($path)) {
                Storage::disk('local')->deleteDirectory($path.'/list');
                Storage::disk('local')->deleteDirectory($path.'/detail');
            }
        } catch (Exception $e) {
            common_log('删除缓存文件异常', $e);
        }
    }
}

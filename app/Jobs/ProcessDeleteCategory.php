<?php

namespace App\Jobs;

use App\Services\CategoryService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDeleteCategory implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $categoryId;

    /**
     * 最大执行秒数
     *
     * @var integer
     */
    public $timeout = 3000;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        common_log('开始删除分类: '.$this->categoryId);
        $result = CategoryService::delete($this->categoryId);
        common_log('删除分类完成: '.json_encode($result, JSON_UNESCAPED_UNICODE));
    }

    /**
     * 任务失败的处理过程
     *
     * @param exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        common_log('删除分类失败', $exception);
    }
}

<?php

namespace App\Jobs;

use App\Models\TemplateGroup;
use App\Services\CategoryService;
use App\Services\TemplateService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDeleteGroup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 最大执行秒数
     *
     * @var integer
     */
    public $timeout = 3000;

    public $groupId;

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
        set_time_limit(0);
        ini_set('memory_limit', '-1');

        common_log('开始删除分组: '.$this->groupId);
        $group = TemplateGroup::find($this->groupId);
        $group->delete();
        common_log('删除分组完成: '.$this->groupId);
    }

    /**
     * 任务失败的处理过程
     *
     * @param exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        common_log('删除分组失败', $exception);
    }
}

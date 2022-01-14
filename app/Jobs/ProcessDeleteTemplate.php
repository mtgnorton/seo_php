<?php

namespace App\Jobs;

use App\Models\Template;
use App\Services\CategoryService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDeleteTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $templateId;

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
    public function __construct($templateId)
    {
        $this->templateId = $templateId;
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

        common_log('开始删除分类: '.$this->templateId);
        $template = Template::find($this->templateId);
        $template->delete();
        common_log('删除分类完成: '.$this->templateId);
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

<?php

namespace App\Jobs;

use App\Services\TemplateService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessCopyTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $templateId;

    protected $params;

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
    public function __construct($templateId=0, $params=[])
    {
        $this->templateId = $templateId;
        $this->params = $params;
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
        common_log('开始复制模板');
        $result = TemplateService::copyTemplate($this->templateId, $this->params);

        if ($result['code'] != 0) {
            common_log('复制模板失败, 失败原因为: '.$result['message']??'');
        } else {
            common_log('复制模板成功');
        }
    }

    /**
     * 任务失败的处理过程
     *
     * @param exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {        
        common_log('复制模板失败', $exception);
    }
}

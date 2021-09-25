<?php

namespace App\Jobs;

use App\Models\TemplateExport;
use App\Services\TemplateService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessExportTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $templateId;

    protected $export;

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
    public function __construct($templateId, TemplateExport $export)
    {
        $this->templateId = $templateId;
        $this->export = $export;
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
        $templateId = $this->templateId ?? 0;
        $export = $this->export ?? [];

        if (empty($templateId) || empty($export)) {
            common_log('数据残缺, 无法进行模板导出');

            return [];
        }
        
        common_log('开始进行模板的导出: '.$templateId);

        $result = TemplateService::exportTemplate($templateId, $export);

        if ($result['code'] != 0) {
            common_log('模板导出失败: '.$templateId.', 失败原因为: '.$result['data']);
        } else {
            common_log('模板导出成功: '.$templateId);
        }
    }
}

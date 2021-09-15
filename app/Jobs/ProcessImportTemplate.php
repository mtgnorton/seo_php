<?php

namespace App\Jobs;

use App\Services\TemplateService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\UploadedFile;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessImportTemplate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * 文件数据
     *
     * @var array
     */
    private $fileData;

    /**
     * 最大执行秒数
     *
     * @var integer
     */
    public $timeout = 3000;

    /**
     * 参数
     *
     * @var array
     */
    private $params;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $fileData, array $params)
    {
        $this->fileData = $fileData;
        $this->params = $params;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(3000);
        ini_set('memory_limit', '512M');

        $fileData = $this->fileData;
        $params = $this->params;
        
        $newFile = new UploadedFile(storage_path('app/public').$fileData['path'], $fileData['name'], $fileData['mimeType']);

        $params['import_data'] = $newFile;

        common_log('开始进行模板的导入');
        TemplateService::importTemplate($params);

        // 删除预存的模板zip文件
        Storage::disk('public')->delete($fileData['path']);
        common_log('删除临时zip包成功');

        common_log('模板导入完成');
    }

    /**
     * 任务失败的处理过程
     *
     * @param exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        $message = '导入模板失败';
        
        common_log($message, $exception);
    }
}

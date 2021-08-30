<?php

namespace App\Jobs;

use App\Models\File;
use App\Services\ContentService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAddContent implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $data;

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
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $data = $this->data;

        ContentService::insertContentByFile(
            $data['categoryId'],
            $data['type'],
            $data['fileObj']
        ); 
    }

    /**
     * 任务失败的处理过程
     *
     * @param exception $exception
     * @return void
     */
    public function failed(Exception $exception)
    {
        $fileId = $this->data['fileObj']['id'] ?? 0;
        $message = '文件上传失败, 请删除后重新上传, 错误信息为: '.$exception->getMessage();

        File::where('id', $fileId)->update([
            'message' => $message
        ]);
    }
}

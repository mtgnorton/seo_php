<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DeleteExportTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:delete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '删除每日生成的导出模板文件';

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
        common_log('开始清空导出模板存留文件');
        // 获取exportTemplate文件夹中数据
        $dirPath = 'exportTemplate';
        $files = Storage::disk('public')->allFiles($dirPath);

        Storage::disk('public')->delete($files);
        
        common_log('清空导出模板存留文件成功: '.json_encode($files, JSON_UNESCAPED_UNICODE));
    }
}

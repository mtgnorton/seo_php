<?php

namespace App\Services;

use App\Services\Gather\CrawlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ZipArchive;

class SystemUpdateService
{
    static protected $srcFileAmount = 0;

    static protected $finishAmount = 0;


    static protected $phpFiles = [];

    /**
     * author: mtg
     * time: 2021/7/3   14:57
     * function description: 在线更新
     * @param string $src
     * @param string $dst
     */
    static public function update($patchURL)
    {


        exec(' chown -R  nginx:nginx  /data/wwwroot/seo_php/app  /data/wwwroot/seo_php/database /data/wwwroot/seo_php/routes /data/wwwroot/seo_php/public /data/wwwroot/seo_php/config /data/wwwroot/seo_php/bootstrap', $output);


        system_update_log('开始更改项目的部分权限');

        $zipFullPath = storage_path('update/patch.zip');

        $temp          = pathinfo($zipFullPath);
        $parentZipPath = $temp['dirname'];
        $unZipFullPath = $temp['dirname'] . DIRECTORY_SEPARATOR . $temp['filename'];


        if (!is_dir($parentZipPath) && !mkdir($parentZipPath, 0777, true)) {

            system_update_log('压缩文件父目录创建失败');

            return [
                'state'   => 0,
                'message' => '压缩文件父目录创建失败'
            ];
        }

        $fullZipPath = CrawlService::download($patchURL, $zipFullPath);

        $rs = ZipService::unzip($fullZipPath, $unZipFullPath);


        if ($rs !== true) {
            system_update_log('解压失败' . $rs);

            return [
                'state'   => 0,
                'message' => $rs
            ];
        }
        $src = $unZipFullPath;


        /*sql更新*/
        $sqlFile = $src . '/' . 'modify.sql';

        $sqlAmount   = 0;
        $errorAmount = 0;
        if (file_exists($sqlFile)) {
            list($sqlAmount, $errorAmount) = DatabaseService::sqlExecute(file_get_contents($sqlFile));
            @unlink($sqlFile);
        }


        /*配置文件更新*/
        $configFile = $src . '/' . 'modify_config.php';

        if (is_file($configFile)) {

            system_update_log('存在修改服务器配置文件准备更新');
            $configs = include $configFile;
            foreach ($configs as $item) {
                $filepath = data_get($item, 'filepath');
                if (empty($filepath) || !is_file($filepath)) {
                    continue;
                }
                $old = data_get($item, 'old');
                $new = data_get($item, 'new');
                if (empty($old) && empty($new)) {
                    continue;
                }
                $configContent = file_get_contents($filepath);
                if (!empty($old)) { //替换
                    $newConfigContent = str_replace($old, $new, $configContent);

                } else {//新增
                    $newConfigContent = $configContent . "\n" . $new;
                }
                exec('sudo chown  nginx:nginx ' . $filepath, $output);

                system_update_log(sprintf('修改%s配置文件权限,修改权限结果为', $filepath), $output);
                $writeRs = file_put_contents($filepath, $newConfigContent);
                system_update_log(sprintf('修改%s配置文件,已将%s修改为%s,结果为:%s', $filepath, $old, $new, $writeRs));

            }
            @unlink($configFile);
        }


        /*代码文件合并更新*/
        $dst = realpath('../');

        self::$srcFileAmount = FileService::calculateFileAmount($src, ['execute_command.php']);
        system_update_log('系统待更新文件数量为:' . self::$srcFileAmount);

        if (!is_numeric(self::$srcFileAmount)) {
            return [
                'state'   => 0,
                'message' => $rs
            ];
        }

        $rs = FileService::mergeDir($src, $dst, function ($finishAmount, $filepath = "") {

            if (Str::endsWith($filepath, '.php')) {
                array_push(self::$phpFiles, $filepath);
            }

            self::$finishAmount++;
        }, ['execute_command.php']);
        system_update_log('系统合并文件数量为:' . self::$srcFileAmount);

        if ($rs !== true) {
            return [
                'state'   => 0,
                'message' => $rs
            ];
        }


        /*命令执行*/
        $commandFile = $src . '/' . 'execute_command.php';

        if (is_file($commandFile)) {
            $commands = include $commandFile;
            system_update_log('存在命令执行文件准备执行');
            foreach ($commands as $command) {
                exec($command, $output);
                system_update_log(sprintf("命令%s执行结果为:", $command), $output);
            }

        }


        $deleteRs = FileService::deleteDir(storage_path('update/'));

        system_update_log(sprintf('删除更新文件夹%s,结果为%s', storage_path('update/'), $deleteRs));

        return [
            'state'              => 1,
            'amount'             => self::$finishAmount,
            'sql_success_amount' => $sqlAmount,
            'sql_error_amount'   => $errorAmount,
            'php_files'          => self::$phpFiles
        ];
    }


    static public function info(string $message)
    {
        system_update_log($message . "\r\n");
    }


}

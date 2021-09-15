<?php

namespace App\Console\Commands;

use App\Services\FileService;
use App\Services\ZipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UpdateEncryptCompress extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'encrypt:compress';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '将更新的文件加密后形成更新包';

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


        $type = $this->choice('打包哪些文件', ['修改过的', '上一次提交的文件', '某次提交,需要输入提交hash'], 0);


        switch ($type) {
            case '修改过的':
                $command = "git ls-files -m";
                break;

            case '上一次提交的文件':
                $command = "git diff --name-only HEAD~ HEAD";
                break;
            case '某次提交,需要输入提交hash':
                $command = "git diff-tree -r --no-commit-id --name-only ";
                $hash    = $this->ask('输入hash');

                $command .= $hash;
                break;
        }

        exec($command, $output);

        $this->info('变动文件如下所示:');

        foreach ($output as $changeFilePath) {
            $this->info($changeFilePath);
        }


        if (!$output) {
            $this->line('变动文件为空');
        }


        $confirm = $this->confirm('确认打包?');
        if (!$confirm) {
            $this->info('结束');
            return;
        }


        $rootPath = base_path();
        $toPath   = base_path('encrypt-compress') . DIRECTORY_SEPARATOR;

        if (is_dir($toPath)) {
            FileService::deleteDir($toPath);
        }

        mkdir($toPath);

        $encryptPaths = ['app/Admin', 'app/Constants', 'app/Http', 'app/Services', 'app/Helper']; //加密的文件路径


        foreach ($output as $changeFilePath) {

            $info = pathinfo($changeFilePath);


            $this->createDir($this->joinPath($toPath, $info['dirname']));

            $targetPath = $this->joinPath($toPath, $changeFilePath);
            copy($this->joinPath($rootPath, $changeFilePath), $targetPath);


            $this->info(sprintf('文件%s拷贝到目标路径完成', $changeFilePath));

            if (!function_exists('beast_encode_file')) {
                $this->error('加密beast扩展未安装,无法加密');
                return;
            }
            if (Str::contains($changeFilePath, $encryptPaths)) { //进行加密
                $rs        = \beast_encode_file($targetPath, $targetPath, 0, BEAST_ENCRYPT_TYPE_DES);
                $isSuccess = false;
                if ($rs) {
                    $isSuccess = true;
                }
                $this->info(sprintf('文件%s加密完成,结果为:%s', $changeFilePath, $isSuccess));
            }


        }

        ZipService::zip($toPath, '', 'encrypt-compress.zip');

        $this->info('创建压缩包完成');

    }


    /**
     * author: mtg
     * time: 2021/9/14   17:12
     * function description:将传递过来的路径按照顺序拼接起来
     * @param mixed ...$paths
     */
    public function joinPath(...$paths)
    {
        return array_reduce($paths, function ($carry, $path) {
            return $carry . $this->tailFormat($path);
        });

    }


    /**
     * author: mtg
     * time: 2021/9/14   17:30
     * function description:尾部路径加分隔符
     * @param $path
     * @return string
     */
    public function tailFormat($path)
    {
        $path = $this->toDs($path);

        if (strpos($path, '.php') !== false || ($path == "")) {
            return $path;
        }
        return rtrim($path, '/\\') . DIRECTORY_SEPARATOR;
    }

    /**
     * author: mtg
     * time: 2021/9/14   17:06
     * function description:转成当前系统的文件分隔符
     * @param $path
     * @return string|string[]
     */
    public function toDs($path)
    {
        return str_replace('/', DIRECTORY_SEPARATOR, $path);
    }


    /**
     * author: mtg
     * time: 2021/9/14   17:06
     * function description:递归创建文件夹
     * @param $path
     * @return bool
     */
    public function createDir($path)
    {
        // 判断传过来的$path是否已是目录，若是，则直接返回true
        if (is_dir($path)) {
            return true;
        }

// 走到这步，说明传过来的$path不是目录
// 判断其上级是否为目录，是，则直接创建$path目录
        if (is_dir(dirname($path))) {
            return mkdir($path);
        }

// 走到这说明其上级目录也不是目录,则继续判断其上上...级目录
        $this->createDir(dirname($path));

// 走到这步，说明上级目录已创建成功，则直接接着创建当前目录，并把创建的结果返回
        return mkdir($path);
    }


}

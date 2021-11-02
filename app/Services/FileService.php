<?php

namespace App\Services;


class FileService
{


    /**
     * author: mtg
     * time: 2021/7/3   14:35
     * function description 获取目录中php文件的数量
     * @param $dir
     * @return false
     */
    static public function calculateFileAmount(string $dir, $exclude = [])
    {
        static $fileAmount = 0;
        $dir = rtrim($dir, '/');

        $handle = opendir($dir);

        if (!$handle) {
            system_update_log('计算文件数量时打开文件夹失败');

            return "计算文件数量时打开文件夹失败";
        }
        while (($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            if (in_array($file, $exclude)) {
                system_update_log('计算文件数量时,因为在exclude数组中,跳过' . $dir . '/' . $file);
                continue;
            }

            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                self::calculateFileAmount($path);

            } else {
                $fileAmount++;
            }
        }
        closedir($handle);

        return $fileAmount;

    }

    /**
     * author: mtg
     * time: 2021/7/3   14:58
     * function description:将$src中的文件递归合并到$dst中
     * @param string $src
     * @param string $dst
     * @return false
     */
    static public function mergeDir(string $src, string $dst, $callback = null, $exclude = [])
    {
        $src = rtrim($src, '/');
        $dst = rtrim($dst, '/');
        static $finishAmount = 0;

        if (!is_dir($src)) {
            return true;
        }
        clearstatcache();

        $handle = opendir($src);
        if (!$handle) {
            system_update_log('合并文件时,打开源文件夹失败');
            return "合并文件时,打开源文件夹失败";
        }

        while (($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }


            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if (in_array($file, $exclude)) {
                system_update_log('合并文件时,因为在exclude数组中,跳过' . $srcPath);
                continue;
            }

            if (is_dir($srcPath)) {
                if (!is_dir($dstPath)) {

                    mkdir($dstPath, 0777, true);
                }

                self::mergeDir($srcPath, $dstPath, $callback);

            } else {

                if (is_file($srcPath)) {
                    system_update_log(sprintf('由%s复制到%s', $srcPath, $dstPath));

                    try {
                        copy($srcPath, $dstPath);

                    } catch (\Exception $e) {
                        system_update_log('拷贝文件不存在' . full_error_msg($e));

                        continue;
                    }
                    $finishAmount++;
                    if ($callback) {
                        $callback($finishAmount, $dstPath);
                    }
                }

            }
        }

        closedir($handle);

        return true;
    }


    /**
     * author: mtg
     * time: 2021/7/15   15:38
     * function description:复制文件夹,如果目标文件夹存在,删除目标文件夹
     */
    static public function copyDir($src, $dst)
    {
        if (file_exists($dst)) {
            self::deleteDir($dst);
        }

        if (is_dir($src)) {
            mkdir($dst);
            $files = scandir($src);
            foreach ($files as $file) {
                if ($file != "." && $file != "..") {
                    self::copyDir("$src/$file", "$dst/$file");
                }
            }

        } else if (file_exists($src)) {
            copy($src, $dst);

        }
        return true;
    }


    /**
     * author: mtg
     * time: 2021/7/3   14:37
     * function description: 删除目录
     * @param $dir
     * @return bool
     */
    public static function deleteDir(string $dir)
    {
        $dir = rtrim($dir, '/') . '/';
        //如果是目录则继续
        if (is_dir($dir)) {
            //扫描一个文件夹内的所有文件夹和文件并返回数组
            $p = scandir($dir);
            //如果 $p 中有两个以上的元素则说明当前 $dir 不为空
            if (count($p) > 2) {
                foreach ($p as $val) {
                    //排除目录中的.和..
                    if ($val != "." && $val != "..") {
                        //如果是目录则递归子目录，继续操作
                        if (is_dir($dir . $val)) {
                            //子目录中操作删除文件夹和文件
                            self::deleteDir($dir . $val . '/');
                        } else {
                            //如果是文件直接删除
                            unlink($dir . $val);
                        }
                    }
                }
            }
        }
        //删除目录
        return rmdir($dir); //todo windows 为空无法删除的问题
    }


    /**
     * author: mtg
     * time: 2021/7/15   15:47
     * function description: 创建文件,如果父文件夹不存在,则创建父文件夹
     * @param string $file
     * @return bool
     */
    static public function createFile(string $file)
    {
        $info = pathinfo($file);
        if (!is_dir($info['dirname'])) {
            mkdir($info['dirname']);
        }
        if (!is_file($file)) {
            $f = fopen($file, 'w');
            fclose($f);
        }
        return true;
    }


    /**
     * author: mtg
     * time: 2021/9/14   17:06
     * function description:递归创建文件夹
     * @param $path
     * @return bool
     */
    static public function createDir($path)
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
        self::createDir(dirname($path));

// 走到这步，说明上级目录已创建成功，则直接接着创建当前目录，并把创建的结果返回
        return mkdir($path);
    }


    static public function info(string $message)
    {
        system_update_log($message . "\r\n");
    }


    static public function completePath($path)
    {
        return str_replace('\\', '/', $path);
    }


}

<?php

/**
 * Encode files by directory
 * @author: liexusong
 */

$nfiles = 0;
$finish = 0;

function calculate_directory_schedule($dir)
{
    global $nfiles;

    $dir = rtrim($dir, '/');

    $handle = opendir($dir);
    if (!$handle) {
        return false;
    }

    while (($file = readdir($handle))) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $path = $dir . '/' . $file;

        if (is_dir($path)) {
            calculate_directory_schedule($path);

        } else {
            $infos = explode('.', $file);

            if (strtolower($infos[count($infos) - 1]) == 'php') {
                $nfiles++;
            }
        }
    }

    closedir($handle);
}

function delete_dir($path)
{
    //如果是目录则继续
    if (is_dir($path)) {
        //扫描一个文件夹内的所有文件夹和文件并返回数组
        $p = scandir($path);
        //如果 $p 中有两个以上的元素则说明当前 $path 不为空
        if (count($p) > 2) {
            foreach ($p as $val) {
                //排除目录中的.和..
                if ($val != "." && $val != "..") {
                    //如果是目录则递归子目录，继续操作
                    if (is_dir($path . $val)) {
                        //子目录中操作删除文件夹和文件
                        delete_dir($path . $val . '/');
                    } else {
                        //如果是文件直接删除
                        unlink($path . $val);
                    }
                }
            }
        }
    }
    //删除目录
    return rmdir($path);
}

function encrypt_directory($dir, $new_dir, $expire, $type)
{
    global $nfiles, $finish;

    $dir     = rtrim($dir, '/');
    $new_dir = rtrim($new_dir, '/');

    $handle = opendir($dir);
    if (!$handle) {
        return false;
    }

    while (($file = readdir($handle))) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $path     = $dir . '/' . $file;
        $new_path = $new_dir . '/' . $file;

        if (is_dir($path)) {
            if (!is_dir($new_path)) {
                mkdir($new_path, 0777);
            }

            encrypt_directory($path, $new_path, $expire, $type);

        } else {
            $infos = explode('.', $file);

            if (strtolower($infos[count($infos) - 1]) == 'php'
                && filesize($path) > 0) {
                if ($expire > 0) {
                    $result = beast_encode_file($path, $new_path,
                        $expire, $type);
                } else {
                    $result = beast_encode_file($path, $new_path, 0, $type);
                }

                if (!$result) {
                    echo "Failed to encode file `{$path}'\n";
                }

                $finish++;

                $percent = intval($finish / $nfiles * 100);

                printf("\rProcessed encrypt files [%d%%] - 100%%", $percent);

            } else {
                copy($path, $new_path);
            }
        }
    }

    closedir($handle);
}

//////////////////////////////// run here ////////////////////////////////////


$src_path = './app';
$dst_path = "../seo_php_encrypt/app";

if (isset($argv[1])) {
    $src_path = trim($argv[1]);
}
if (isset($argc[2])) {
    $dst_path = trim($argv[2]);
}

delete_dir(rtrim($dst_path, '/') . '/');


$expire       = "";
$encrypt_type = "DES";

if (empty($src_path) || !is_dir($src_path)) {
    exit("Fatal: source path `{$src_path}' not exists\n\n");
}

if (empty($dst_path)
    || (!is_dir($dst_path)
        && !mkdir($dst_path, 0777))) {
    exit("Fatal: can not create directory `{$dst_path}'\n\n");
}

switch ($encrypt_type) {
    case 'AES':
        $entype = BEAST_ENCRYPT_TYPE_AES;
        break;
    case 'BASE64':
        $entype = BEAST_ENCRYPT_TYPE_BASE64;
        break;
    case 'DES':
    default:
        $entype = BEAST_ENCRYPT_TYPE_DES;
        break;
}

printf("Source code path: %s\n", $src_path);
printf("Destination code path: %s\n", $dst_path);
printf("Expire time: %s\n", $expire);
printf("------------- start process -------------\n");

$expire_time = 0;
if ($expire) {
    $expire_time = strtotime($expire);
}

$time = microtime(TRUE);

calculate_directory_schedule($src_path);
encrypt_directory($src_path, $dst_path, $expire_time, $entype);

$used = microtime(TRUE) - $time;

printf("\nFinish processed encrypt files, used %f seconds\n", $used);

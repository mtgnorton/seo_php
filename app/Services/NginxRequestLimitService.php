<?php

namespace App\Services;

use phpDocumentor\Reflection\Types\Self_;

class NginxRequestLimitService
{
    static public $subNginxConfigPath = '/etc/nginx/conf.d/a_seo.conf';

    static public $mainNginxConfigPath = '/etc/nginx/nginx.conf';


    static public function getConcurrent()
    {
        if (is_win()) {
            return 10;
        }
        $configContent = self::readConfig(self::$subNginxConfigPath);
        preg_match('#zone=perserver burst=(.*?) nodelay#', $configContent, $matches);

        return data_get($matches, 1, 0);
    }

    static public function setConcurrent($concurrentAmount)
    {
        if (is_win()) {
            return;
        }

        $newContent = preg_replace('#burst=(.*?) nodelay#', "burst=$concurrentAmount  nodelay", self::readConfig(self::$subNginxConfigPath));
        self::writeConfig(self::$subNginxConfigPath, $newContent);
        $newContent = preg_replace('#rate=(.*?)r/s#', "rate={$concurrentAmount}r/s", self::readConfig(self::$mainNginxConfigPath));
        self::writeConfig(self::$mainNginxConfigPath, $newContent);
        self::reloadNginx();
    }


    static public function getCpuPerformance()
    {
        if (is_win()) {
            return 0;
        }

        exec('sysbench cpu --threads=10 run', $output);

        if (!$scoreStr = data_get($output, 14)) {
            return 0;
        }
        list(, $score) = explode(":", $scoreStr);
        return floatval($score);
    }


    static public function reloadNginx()
    {
        $rs = exec("nginx -s reload", $output, $returnVal);
    }

    static public function writeConfig($path, $content)
    {
        file_put_contents($path, $content);
    }

    static public function readConfig($path)
    {
        return file_get_contents($path);

    }
}

<?php

namespace App\Contracts;

/**
 * 翻译接口
 */
interface Translate
{
    /**
     * 翻译
     *
     * @param mixed $content   待翻译的内容
     * @param string $from      来源语言
     * @param string $to        目标语言
     * @return mixed
     */
    public function get($content='', $to='en', $from='cn');

    /**
     * 翻译两次(伪原创)
     *
     * @param mixed $content   待翻译的内容
     * @return mixed
     */
    public function twice($content='');
}

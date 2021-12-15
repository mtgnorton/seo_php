<?php

namespace App\Contracts;

/**
 * 智能内容接口
 */
interface AiContent
{
    public function get($keyword='', $page=1);
}

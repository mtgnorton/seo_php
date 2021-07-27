<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 蜘蛛头部标识模型
 */
class SpiderUserAgent extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];
    
    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
   public $timestamps = false;
}

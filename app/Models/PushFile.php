<?php

namespace App\Models;

use App\Events\PushFileDeletingEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 推送文件模型
 */
class PushFile extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => PushFileDeletingEvent::class
    ];
}

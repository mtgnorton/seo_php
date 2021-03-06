<?php

namespace App\Models;

use App\Events\FileDeletingEvent;
use App\Services\ContentService;
use Illuminate\Database\Eloquent\Model;

/**
 * 文件模型
 */
class File extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => FileDeletingEvent::class
    ];

    /**
     * 内容类型获取器
     *
     * @param string $type  内容类型
     * @return string
     */
    public function getTypeAttribute($type)
    {
        $typeData = ContentService::CONTENT_TYPE;

        return $typeData[$type] ?? '未知';
    }
}

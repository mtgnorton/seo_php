<?php

namespace App\Models;

use App\Events\TemplateExportDeletingEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 模板导出模型
 */
class TemplateExport extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => TemplateExportDeletingEvent::class
    ];
}

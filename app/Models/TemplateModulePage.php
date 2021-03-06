<?php

namespace App\Models;

use App\Events\PageDeletingEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 模板类型模型
 */
class TemplateModulePage extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => PageDeletingEvent::class
    ];

    /**
     * 和模板多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo('App\Models\Template');
    }

    /**
     * 和模块多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function module()
    {
        return $this->belongsTo('App\Models\TemplateModule', 'module_id');
    }
}

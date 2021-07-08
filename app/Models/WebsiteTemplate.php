<?php

namespace App\Models;

use App\Events\WebsiteTemplateDeletingEvent;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 网站模板模型
 */
class WebsiteTemplate extends Pivot
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => WebsiteTemplateDeletingEvent::class
    ];

    /**
     * 和网站多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function website()
    {
        return $this->belongsTo('App\Models\Website');
    }

    /**
     * 和网站多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function template()
    {
        return $this->belongsTo('App\Models\Template');
    }
}

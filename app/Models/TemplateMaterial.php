<?php

namespace App\Models;

use App\Events\MaterialDeletingEvent;
use Illuminate\Database\Eloquent\Model;

class TemplateMaterial extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => MaterialDeletingEvent::class
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
}

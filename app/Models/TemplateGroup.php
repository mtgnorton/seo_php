<?php

namespace App\Models;

use App\Events\TemplateGroupDeletingEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 模板分组模型
 */
class TemplateGroup extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];
    
    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => TemplateGroupDeletingEvent::class
    ];

    /**
     * 和分类多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    /**
     * 和模板一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function templates()
    {
        return $this->hasMany('App\Models\Template', 'group_id');
    }

    /**
     * 和模板一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function websites()
    {
        return $this->hasMany('App\Models\Website', 'group_id', 'id');
    }

    /**
     * 和模板一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function contentCategories()
    {
        return $this->hasMany('App\Models\ContentCategory', 'group_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 蜘蛛强引记录
 */
class SpiderAttractionRecord extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

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
     * 和模板多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo('App\Models\TemplateGroup');
    }

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

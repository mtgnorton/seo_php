<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 网站模型
 */
class Website extends Model
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

    // /**
    //  * 和分类多对一的关系
    //  *
    //  * @return Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function templates()
    // {
    //     return $this->belongsToMany(
    //         'App\Models\Template',
    //         'website_template',
    //         'website_id',
    //         'template_id'
    //     )->using('App\Models\WebsiteTemplate')
    //     ->withTimestamps();
    // }

    public function template()
    {
        return $this->belongsTo('App\Models\Template');
    }
}

<?php

namespace App\Models;

use App\Events\TemplateDeletingEvent;
use Illuminate\Database\Eloquent\Model;

/**
 * 模板模型
 */
class Template extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 模块是json格式
     *
     * @var array
     */
    protected $casts = [
        'module' => 'json',
    ];
    
    /**
     * 绑定删除事件
     */
    protected $dispatchesEvents = [
        'deleting' => TemplateDeletingEvent::class
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
     * 和模板类型多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function type()
    {
        return $this->belongsTo('App\Models\TemplateType', 'type_id');
    }

    /**
     * 和模板模块一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function modules()
    {
        return $this->hasMany('App\Models\TemplateModule');
    }

    /**
     * 和模板模块一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function materials()
    {
        return $this->hasMany('App\Models\TemplateMaterial');
    }

    /**
     * 和模块页面一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pages()
    {
        return $this->hasMany('App\Models\TemplateModulePage');
    }

    /**
     * 模板获取器
     *
     * @param string $value 
     * @return array
     */
    public function getModuleAttribute($value)
    {
        return json_decode($value, true) ?: [];
    }

    /**
     * 模板
     *
     * @param array $value
     * @return void
     */
    public function setModuleAttribute($value)
    {
        $this->attributes['module'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // /**
    //  * 和分类多对一的关系
    //  *
    //  * @return Illuminate\Database\Eloquent\Relations\BelongsTo
    //  */
    // public function websites()
    // {
    //     return $this->belongsToMany(
    //         'App\Models\Website',
    //         'website_template',
    //         'template_id',
    //         'website_id'
    //     )->using('App\Models\WebsiteTemplate')
    //     ->withTimestamps();
    // }

    /**
     * 和绑定域名一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function websites()
    {
        return $this->hasMany('App\Models\Website');
    }

    /**
     * 和绑定域名模板一对多的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function websiteTemplates()
    {
        return $this->hasMany('App\Models\WebsiteTemplate');
    }

    /**
     * 和模板分组多对一的关系
     *
     * @return Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function group()
    {
        return $this->belongsTo('App\Models\TemplateGroup');
    }
}

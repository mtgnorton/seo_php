<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Traits\ModelTree;

/**
 * 分类模型 
 */
class Category extends Model
{
    // use ModelTree;
    
    /**
     * 黑名单
     */
    protected $guarded = [];

    protected $casts = [
        'other_template' => 'json',
    ];

    /**
     * 和文章一对多的关系 
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function articles()
    {
        return $this->hasMany('App\Models\Article');
    }

    /**
     * 和栏目一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function columns()
    {
        return $this->hasMany('App\Models\Column');
    }

    /**
     * 和自定义标签一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function diys()
    {
        return $this->hasMany('App\Models\Diy');
    }

    /**
     * 和图片一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function images()
    {
        return $this->hasMany('App\Models\Image');
    }

    /**
     * 和句子一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function sentences()
    {
        return $this->hasMany('App\Models\Sentence');
    }

    /**
     * 和标题一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function titles()
    {
        return $this->hasMany('App\Models\Title');
    }

    /**
     * 和视频一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function videos()
    {
        return $this->hasMany('App\Models\Video');
    }

    /**
     * 和网站名称一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function websites()
    {
        return $this->hasMany('App\Models\Website');
    }

    /**
     * 和网站名称一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function websiteNames()
    {
        return $this->hasMany('App\Models\WebsiteName');
    }

    /**
     * 和分类url规则一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function rules()
    {
        return $this->hasMany('App\Models\CategoryRule');
    }

    /**
     * 和分类url规则一对多的关系
     *
     * @return Illuminate\Databases\Eloquent\Relations\HasMany
     */
    public function contentCategories()
    {
        return $this->hasMany('App\Models\ContentCategory');
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
}

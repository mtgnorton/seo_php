<?php

namespace App\Models;

use App\Models\GatherCrontabLog;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Gather
 *
 * @property int $id
 * @property string $name 采集名称
 * @property string $category_id 分类id
 * @property string $tag 标签名称
 * @property int $day_max_limit 每日采集上限,为0不限制
 * @property string $type 采集类型:sentence 分隔成句子,title 采集标题,full 完整内容,image 图片
 * @property string $storage_type 存储类型
 * @property string $user_agent
 * @property int $is_auto 是否自动采集: 0否,1是
 * @property string $begin_url 开始采集的url
 * @property string|null $regular_url 匹配网址,一行一条
 * @property string|null $test_url 测试地址
 * @property string|null $regular_content 匹配内容,一行一条
 * @property int|null $filter_length_limit 内容最小长度,小于该值过滤
 * @property string|null $filter_regular 正则过滤,一行一条
 * @property string|null $filter_content 内容过滤
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|Gather newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gather newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|Gather query()
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereBeginUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereDayMaxLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereFilterContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereFilterLengthLimit($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereFilterRegular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereIsAuto($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereRegularContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereRegularUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereStorageType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereTestUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Gather whereUserAgent($value)
 * @mixin \Eloquent 
 */
class Gather extends Model
{
    protected $guarded = [];


    public function getDelimiterAttribute($value)
    {
        return explode('|', $value);
    }

    public function setDelimiterAttribute($value)
    {
        $this->attributes['delimiter'] = implode('|', $value);
    }


    public function crontabLogs()
    {
        return $this->hasMany(GatherCrontabLog::class);
    }


    public function contentCategory()
    {
        return $this->belongsTo(ContentCategory::class, 'category_id');
    }

    public function setCategoryIdAttribute($value)
    {
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        
        $this->attributes['category_id'] = $value;
    }
}

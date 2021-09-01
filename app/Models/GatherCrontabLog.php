<?php

namespace App\Models;

use App\Models\Gather;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\GatherCrontabLog
 *
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog query()
 * @mixin \Eloquent
 * @property int $id
 * @property int $gather_id 关联采集规则id
 * @property int $setting_content_amount 采集设定内容数量
 * @property int $setting_url_amount 采集设定链接数量
 * @property int $setting_interval_time 采集设定时间间隔
 * @property int $setting_timeout_time 采集设定超时时间
 * @property int $gather_url_amount 采集链接数量
 * @property int $gather_content_amount 采集内容数量
 * @property string|null $error_log 错误日志
 * @property string|null $gather_log 采集日志
 * @property string|null $end_time 采集结束时间
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereEndTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereErrorLog($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereGatherContentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereGatherId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereGatherLog($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereGatherUrlAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereSettingContentAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereSettingIntervalTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereSettingTimeoutTime($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereSettingUrlAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|GatherCrontabLog whereUpdatedAt($value)
 */
class GatherCrontabLog extends Model
{
    protected $guarded = [];

    public function gather()
    {
        return $this->belongsTo(Gather::class);
    }
}

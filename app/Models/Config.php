<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 配置模型
 */
class Config extends Model
{
    /**
     * 黑名单
     */
    protected $guarded = [];

    /**
     * 模板获取器
     *
     * @param string $value 
     * @return array
     */
    public function getValueAttribute($value)
    {
        if ($this->is_json == 1) {
            return json_decode($value, true);
        } else {
            return $value;
        }
    }

    /**
     * 模板修改器
     *
     * @param array $value
     * @return void
     */
    public function setValueAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['value'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            $this->attributes['is_json'] = 1;
        } else {
            $this->attributes['value'] = $value;
        }
    }
}

<?php

namespace App\Admin\Forms;

use App\Constants\RedisCacheKeyConstant;
use App\Models\Config;
use Encore\Admin\Widgets\Form;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

abstract class Base extends Form
{
    /**
     * 标题
     *
     * @var string
     */
    public $title;

    /**
     * 模块
     *
     * @var string
     */
    private $module;

    public function __construct($data = [])
    {
        parent::__construct($data);

        $this->title = $this->tabTitle();
    }

    abstract public function tabTitle();

    /**
     * Handle the form request.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        collect($data)->map(function ($v, $k) {
            Config::updateOrCreate([
                'module' => $this->getModule(),
                'key'    => $k
            ], [
                'value' => $v
            ]);
        });
        // 清空缓存
        $cacheKey = RedisCacheKeyConstant::CACHE_CONFIGS_KEY . '_' . $this->getModule() . '_0_0_0';
        Cache::delete($cacheKey);

        admin_success(ll('Update success'));

        return back();
    }

    /**
     * 设置模块
     *
     * @param string $module
     * @return void
     */
    public function setModule($module = '')
    {
        $this->module = $module;
    }

    /**
     * 获取模块
     *
     * @return string
     */
    public function getModule()
    {
        return $this->module ?: strtolower(basename(str_replace('\\', '/', get_called_class())));
    }

    /**
     * The data of the form.
     *
     * @return array $data
     */
    public function data()
    {
        return Config::where('module', $this->getModule())->pluck('value', 'key');
    }
}

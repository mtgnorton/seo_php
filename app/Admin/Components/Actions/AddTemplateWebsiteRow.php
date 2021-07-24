<?php

namespace App\Admin\Components\Actions;

use App\Models\Template;
use App\Models\Website;
use App\Services\CommonService;
use App\Services\TemplateService;
use Encore\Admin\Actions\RowAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AddTemplateWebsiteRow extends RowAction
{
    public $name;

    public function __construct()
    {
        $this->name = lp('Bind host');

        parent::__construct();
    }

    public function handle(Model $model, Request $request)
    {
        $urls = $request->input('url', '');

        $urlData = CommonService::linefeedStringToArray($urls);
        $baseData = [
            'category_id' => $model->category_id,
            'group_id' => $model->id,
            'is_enabled' => 1,
        ];
        $sum = count($urlData);
        $successCount = 0;
        $repeatCount = 0;

        foreach ($urlData as $url) {
            // 去除地址的http://和https://
            $url = str_replace("https://", "", $url);
            $url = str_replace("http://", "", $url);
            
            $condition = [
                'group_id' => $model->id,
                'url' => $url
            ];

            if (Website::where($condition)->count() > 0) {
                $repeatCount++;
            } else {
                $insertData = array_merge($baseData, compact('url'));
                $model->websites()->create($insertData);

                $successCount++;
            }
        }
        $message = "新增成功, 插入的域名的总数为: {$sum}, 成功的数量为: {$successCount}, 重复的数量为: {$repeatCount}";

        return $this->response()->success($message)->refresh();
    }

    /**
     * 表单内容
     *
     * @return void
     */
    public function form()
    {
        $this->textarea('url', ll('Host'))
                ->help(lp('One line one'))
                ->rules('required', [
                    'required' => ll('Url cannot be empty')
                ]);
    }

}

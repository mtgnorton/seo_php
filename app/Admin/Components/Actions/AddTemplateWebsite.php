<?php

namespace App\Admin\Components\Actions;

use App\Models\TemplateGroup;
use App\Models\Website;
use App\Services\CommonService;
use App\Services\TemplateService;
use Encore\Admin\Actions\Action;
use Illuminate\Http\Request;

class AddTemplateWebsite extends Action
{
    public $name = '绑定域名';

    protected $selector = '.add-templated-website';

    protected $groupId;

    public function __construct($groupId=0)
    {
        parent::__construct();

        $this->groupId = $groupId;
    }

    public function handle(Request $request)
    {
        $urls = $request->input('url', '');
        $groupId = $request->input('group_id', 0);
        $group = TemplateGroup::find($groupId);

        $urlData = CommonService::linefeedStringToArray($urls);
        $baseData = [
            'category_id' => $group->category_id,
            'group_id' => $group->id,
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
                'group_id' => $group->id,
                'url' => $url
            ];

            if (Website::where($condition)->count() > 0) {
                $repeatCount++;
            } else {
                $insertData = array_merge($baseData, compact('url'));
                $group->websites()->create($insertData);

                $successCount++;
            }
        }
        $message = "新增成功, 插入的域名的总数为: {$sum}, 成功的数量为: {$successCount}, 重复的数量为: {$repeatCount}";

        return $this->response()->success($message)->refresh();
    }

    public function form()
    {
        $this->hidden('group_id')->default($this->groupId);
        $this->textarea('url', ll('Host'))
                ->help(lp('One line one'))
                ->rules('required', [
                    'required' => ll('Url cannot be empty')
                ]);
    }

    public function html()
    {
        return <<<HTML
        <a class="btn btn-sm btn-success add-templated-website"><i class="fa fa-upload"></i> 绑定域名</a>
HTML;
    }
}

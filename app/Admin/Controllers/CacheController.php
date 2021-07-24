<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Cache;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CacheController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        // $this->title = lp("Cache", 'Setting');
    }

    public function index(Content $content)
    {
        $form = [
            'cache' => Cache::class,
        ];
        $html =  $content
            // ->title(lp("Ad", 'Setting'))
            // ->body(Tab::forms($form));
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $userResult = <<<HTML
                        <h1 class="home-title">缓存配置</h1>
HTML;
                    $column->append($userResult);
                    $column->append(new Cache());
                });
            });
            $html = $html->render();
            $html = str_replace('<section class="content">','<section class="content content_default">',$html);
            return $html;
        // return $content
            // ->title(lp("Cache", 'Setting'))
            // ->body(Tab::forms($form));
            // ->body(new Cache());
    }

    /**
     * 清空换缓存
     *
     * @param Request $request
     * @return void
     */
    public function clear(Request $request)
    {
        $groupId = $request->input('group_id');
        $type = $request->input('type', 'index');

        $path = 'cache/templates/'.$groupId.'/'.$type;
        if (Storage::disk('local')->files($path)) {
            Storage::disk('local')->deleteDirectory($path);
        }

        return json_encode([
            'code' => 0,
            'message' => '清空成功'
        ]);
    }
}

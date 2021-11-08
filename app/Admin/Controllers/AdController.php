<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Ad;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Layout\Row;
use Encore\Admin\Layout\Column;
use Encore\Admin\Widgets\Box;
use Encore\Admin\Widgets\Tab;

class AdController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = lp("Ad", 'Setting');
    }

    public function index(Content $content)
    {
        $form = [
            'ad' => Ad::class, 
        ];
        $html =  $content
            // ->title(lp("Ad", 'Setting'))
            // ->body(Tab::forms($form));
            ->row(function (Row $row) {
                $row->column(12, function (Column $column) {
                    $userResult = <<<HTML
                        <h1 class="home-title">广告管理</h1>
HTML;
                    $column->append($userResult);
                    $column->append(new Ad());
                });
            });
            $html = $html->render();
            $html = str_replace('<section class="content">','<section class="content content_default">',$html);
            return $html;
    }
}

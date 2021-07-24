<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Spider;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SpiderSettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = lp("Spider", 'Setting');
    }

    public function index(Content $content)
    {
        $form = [
            'spider' => Spider::class,
        ];
        $html = $content
            ->title(lp("Spider", 'Setting'))
            ->body(new Spider());
        $html = $html->render();
        $html = str_replace('<section class="content">','<section class="content content_default">',$html);
        return $html;
    }
}
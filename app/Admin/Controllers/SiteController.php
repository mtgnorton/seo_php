<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Cache;
use App\Admin\Forms\Site;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = lp("Site", 'Setting');
    }

    public function index(Content $content)
    {
        $form = [
            'site' => Site::class,
        ];

        $content
            ->title(lp("Site", 'Setting'))
            // ->body(Tab::forms($form));
            ->body(new Site());
        $content = $content->render();
        $content = str_replace('<section class="content">','<section class="content content_site_default">',$content);
        return $content;
    }
}

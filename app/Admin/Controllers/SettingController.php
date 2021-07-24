<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Ad;
use App\Admin\Forms\Cache;
use App\Admin\Forms\ServerExport;
use App\Admin\Forms\SystemMigration;
use App\Admin\Forms\Push;
use App\Admin\Forms\Site;
use App\Admin\Forms\Spider;
use App\Admin\Forms\SystemUpdate;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;

class SettingController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = lp("System", 'Setting');
    }

    public function index(Content $content)
    {
        $form = [
            // 'site'   => Site::class,
            // 'spider' => Spider::class,
            // 'ad'     => Ad::class,
            'push'          => Push::class,
            // 'cache'  => Cache::class,

        ];

        $html = $content
            ->title(lp("", ''))
            ->body(Tab::forms($form));
        $html = $html->render();
        $html = str_replace('<section class="content">','<section class="content content_nav_default">',$html);
        return $html;
    }
}

<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Ad;
use App\Admin\Forms\Cache;
use App\Admin\Forms\ClearCache;
use App\Admin\Forms\ServerExport;
use App\Admin\Forms\SystemMigration;
use App\Admin\Forms\Push;
use App\Admin\Forms\Site;
use App\Admin\Forms\Spider;
use App\Admin\Forms\SystemUpdate;
use App\Http\Controllers\Controller;
use App\Services\CommonService;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;

class ClearCacheController extends AdminController
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
            'cache'        => ClearCache::class,
        ];

        $html = $content
            ->title(lp("", ''))
            ->body(Tab::forms($form));
        $html = $html->render();
        $html = str_replace('<section class="content">','<section class="content content_nav_default">',$html);
        return $html;
    }

    public function clear()
    {
        $result = CommonService::clearCacheFiles();

        return json_encode($result);
    }
}

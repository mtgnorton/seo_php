<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Ad;
use App\Admin\Forms\Cache;
use App\Admin\Forms\FakeOrigin;
use App\Admin\Forms\ServerExport;
use App\Admin\Forms\SouGouPush;
use App\Admin\Forms\SystemMigration;
use App\Admin\Forms\Push;
use App\Admin\Forms\QihooPush;
use App\Admin\Forms\Site;
use App\Admin\Forms\Spider;
use App\Admin\Forms\SystemUpdate;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;

class FakeOriginController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = ll('伪原创设置');
    }

    public function index(Content $content)
    {
        $form = [
            'fake_origin' => FakeOrigin::class,
        ];

        $html = $content
            ->title(lp("", ''))
            ->body(Tab::forms($form));

        return $html;
    }
}

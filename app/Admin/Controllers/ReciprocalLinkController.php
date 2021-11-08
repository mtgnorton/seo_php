<?php

namespace App\Admin\Controllers;

use App\Admin\Forms\Ad;
use App\Admin\Forms\Cache;
use App\Admin\Forms\ServerExport;
use App\Admin\Forms\SystemMigration;
use App\Admin\Forms\Push;
use App\Admin\Forms\ReciprocalLink;
use App\Admin\Forms\Spider;
use App\Admin\Forms\SystemUpdate;
use App\Http\Controllers\Controller;
use Encore\Admin\Layout\Content;
use Encore\Admin\Widgets\Tab;

class ReciprocalLinkController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title;

    public function __construct()
    {
        $this->title = lp("Reciprocal link", "Setting");
    }

    public function index(Content $content)
    {
        $form = [
            'reciprocal_link' => ReciprocalLink::class,
        ];

        $content
            ->title(lp("Reciprocal link", 'Setting'))
            ->body(new ReciprocalLink());
        $content = $content->render();

        return $content;
    }
}

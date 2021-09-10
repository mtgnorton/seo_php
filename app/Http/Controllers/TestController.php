<?php

namespace App\Http\Controllers;

use App\Constants\RedisCacheKeyConstant;
use App\Models\Category;
use App\Models\Sentence;
use App\Models\SpiderRecord;
use App\Models\SpiderUserAgent;
use App\Models\TemplateModule;
use App\Models\TemplateModulePage;
use App\Models\Title;
use App\Models\Website;
use App\Services\CommonService;
use App\Services\ContentService;
use App\Services\IndexService;
use App\Services\SpiderService;
use App\Services\TemplateService;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class TestController extends Controller
{
    public function index(Request $request)
    {
        
    }
}

<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AiContent extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'aiContent';
    }
}

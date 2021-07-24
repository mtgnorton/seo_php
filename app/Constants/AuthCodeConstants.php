<?php

namespace App\Constants;

class AuthCodeConstants
{
    const EFFECTIVE_TYPE_ONE_YEAR = 'one_year';
    const EFFECTIVE_TYPE_ALL = 'all';


    static public function effectiveTypeText()
    {
        return [
            self::EFFECTIVE_TYPE_ONE_YEAR => "1年",
            self::EFFECTIVE_TYPE_ALL      => '永久'
        ];
    }
}

<?php

namespace App\Constants;

class MirrorConstant
{

    const CONVERSION_NO = 'no_conversion';
    const CONVERSION_TO_COMPLEX = 'to_complex';
    const CONVERSION_TO_ENGLISH = 'to_english';

    static public function conversionText()
    {
        return [
            self::CONVERSION_NO         => ll('No conversion'),
            self::CONVERSION_TO_COMPLEX => ll('To complex'),
//            self::CONVERSION_TO_ENGLISH => ll('To English'),
        ];
    }
}

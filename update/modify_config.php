<?php
// filepath 配置文件路径,new将会替换old,如果old为空,则会附加到配置文件的末尾
return [
    [
        'filepath' => '/etc/opt/remi/php73/php.d/10-opcache.ini',
        'old'      => 'opcache.validate_timestamps=60',
        'new'      => 'opcache.validate_timestamps=100'
    ]
];



<?php

use BetaKiller\Error\PhpExceptionStorageInterface;
use BetaKiller\Error\PhpExceptionStorage;

return [

    'definitions' => [

        MultiSite::class => DI\factory(function() {
            return MultiSite::instance();
        }),

    ],

];

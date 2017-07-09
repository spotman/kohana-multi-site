<?php

return [

    'definitions' => [

        MultiSite::class => DI\factory(function() {
            return MultiSite::instance();
        }),

    ],

];

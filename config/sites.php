<?php defined('SYSPATH') OR die('No direct script access.');

return array(

    'path'  =>  realpath(APPPATH.'..'.DIRECTORY_SEPARATOR.'sites'),

    'sites' =>  array(

        'example.com'   =>  array(
            'urls'      =>  array('example.com', '*.example.com')
        ),

    ),

);
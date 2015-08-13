<?php defined('SYSPATH') OR die('No direct script access.');

MultiSite::instance()->process();

Kohana::$log->attach(
    new Log_File(MultiSite::instance()->site_path().DIRECTORY_SEPARATOR.'logs'),
    Log::INFO
);

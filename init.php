<?php defined('SYSPATH') OR die('No direct script access.');

$ms = MultiSite::instance();

if ($ms->process())
{
    Kohana::$log->attach(
        new Log_File(MultiSite::instance()->site_path().DIRECTORY_SEPARATOR.'logs'),
        Log::INFO
    );
}

unset($ms);

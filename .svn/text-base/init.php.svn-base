<?php defined('SYSPATH') OR die('No direct script access.');

$init_multi_site = function()
{
    // Getting current site
    $full_site_url = URL::site(NULL, 'http');
    $domain = parse_url($full_site_url, PHP_URL_HOST);

    // Getting full config
    $config = Kohana::$config->load('sites');

    // Getting config for current domain
    $site_config = $config->get($domain);

    // Does per-site directory exists?
    if ( $site_config !== NULL )
    {
        $path = isset($site_config['path'])
            ? $site_config['path']
            : $domain;

        $site_directory = APPPATH . '../sites' . DIRECTORY_SEPARATOR . $path;

        if ( ! file_exists($site_directory) OR ! is_dir($site_directory) )
            throw new HTTP_Exception_500('Domain [:domain] defined, but per-site directory [:directory] does not exists',
                array(':domain' => $domain, ':directory' => $site_directory)
            );

        // Connecting per-site directory
        Kohana::prepend_path($site_directory);
    }
};

$init_multi_site();

unset($init_multi_site);
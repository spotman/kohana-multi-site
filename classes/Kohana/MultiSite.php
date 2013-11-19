<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_MultiSite {

    /**
     * @var string
     */
    protected static $_domain;

    /**
     * @var string Per-site directory
     */
    protected static $_site_dir;

    /**
     * @var Kohana_Config_Group
     */
    protected static $_config;

    public static function init()
    {
        // Getting current site
        $domain = static::domain();

        // Getting config for current domain
        $site = static::search($domain);

        // If no config for current site, exit
        if ( ! $site )
            return;

//            throw new HTTP_Exception_500('Domain [:domain] does not defined in config/sites.php',
//                array(':domain' => $domain)
//            );

        $site_directory = static::config('path') . DIRECTORY_SEPARATOR . $site['path'];

        // Does per-site directory exists?
        if ( ! file_exists($site_directory) OR ! is_dir($site_directory) )
            throw new HTTP_Exception_500('Domain [:domain] defined, but per-site directory [:directory] does not exists',
                array(':domain' => $domain, ':directory' => $site_directory)
            );

        // Saving per-site dir for later use
        static::site_directory($site_directory);

        // Connecting per-site directory to CFS so it becomes top level path (which overrides /application/ path)
        Kohana::prepend_path($site_directory);

        // Loading custom init.php file for current site if exists
        $init_file = $site_directory.DIRECTORY_SEPARATOR.'init.php';

        if ( file_exists($init_file) )
        {
            Kohana::load($init_file);
        }
    }

    /**
     * Getter/setter for current per-site directory
     * @param string|null $path
     * @return string
     */
    public static function site_directory($path = NULL)
    {
        if ( $path )
        {
            static::$_site_dir = $path;
        }

        return static::$_site_dir;
    }

    public static function domain()
    {
        if ( ! static::$_domain )
        {
            $full_site_url = URL::site(NULL, 'http');
            static::$_domain = parse_url($full_site_url, PHP_URL_HOST);
        }

        return static::$_domain;
    }

    protected static function config($key = NULL)
    {
        if ( ! static::$_config )
        {
            static::$_config = Kohana::$config->load('sites');
        }

        return $key ? self::$_config->get($key) : self::$_config;
    }

    protected static function search($domain)
    {
        $sites = static::config('sites');

        foreach ( $sites as $key => $config )
        {
            // Allow short listing of sites (like 'sites' => array('example.com', 'another-example.com') )
            if ( is_string($config) )
            {
                $key = $config;
                $config = NULL;
            }

            // Allow empty per-site config
            if ( ! $config )
            {
                $config = array(
                    'urls' => array($key)
                );
            }

            // Search for matching domain
            foreach ( $config['urls'] as $url )
            {
                if ( static::check($domain, $url) )
                {
                    // Optional directory name (the config key would used instead)
                    if ( ! isset($config['path']) )
                    {
                        $config['path'] = $key;
                    }

                    return $config;
                }
            }
        }

        return NULL;
    }

    protected static function check($domain, $wildcard)
    {
        return fnmatch($wildcard, $domain);
    }

}
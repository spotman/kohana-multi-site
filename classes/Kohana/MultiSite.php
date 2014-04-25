<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_MultiSite {

    /**
     * @var MultiSite
     */
    protected static $_instance;

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


    /**
     * Returns instance of current class
     *
     * @return MultiSite
     */
    public static function instance()
    {
        if ( ! static::$_instance )
        {
            static::$_instance = static::factory();
        }

        return static::$_instance;
    }

    /**
     * Performs search for per-site directory derived to current request domain and adds it to CFS
     *
     * @return $this
     */
    public function process()
    {
        // Getting current domain
        $domain = $this->domain();

        $sites_path = realpath(static::config('path'));

        // Getting per-site directory name for current domain
        $site_name = $this->search($sites_path, $domain);

        // If no config for current site, exit
        if ( ! $site_name )
        {
            $this->missing_domain($domain);
            return $this;
        }

        $site_path = realpath($sites_path.DIRECTORY_SEPARATOR.$site_name);

        // Saving per-site dir for later use
        $this->site_path($site_path);

        // Connecting per-site directory to CFS so it becomes top level path (which overrides /application/ path)
        Kohana::prepend_path($site_path);

        return $this;
    }

    /**
     * Init site if init.php exists
     */
    public function init_site()
    {
        // Loading custom init.php file for current site if exists
        $init_file = $this->site_path().DIRECTORY_SEPARATOR.'init.php';

        if ( file_exists($init_file) )
        {
            Kohana::load($init_file);
        }
    }

    /**
     * Getter/setter for current per-site directory
     *
     * @param string|null $path
     * @return string
     */
    public function site_path($path = NULL)
    {
        if ( $path )
        {
            static::$_site_dir = $path;
        }

        return static::$_site_dir;
    }

    /**
     * Returns the current request domain name
     * @return string
     * @throws HTTP_Exception_400
     */
    public function domain()
    {
        if ( ! static::$_domain )
        {
            // Attempt to use HTTP_HOST and fallback to SERVER_NAME
            static::$_domain = getenv('HTTP_HOST') ?: getenv('SERVER_NAME');

            if ( ! static::$_domain  )
                throw new HTTP_Exception_400('Can not determine domain name, possible bad request');
        }

        return static::$_domain;
    }

    /**
     * Restrict direct *new* call
     */
    protected function __construct()
    {
    }

    /**
     * Creates instance of current class
     *
     * @return static
     */
    protected static function factory()
    {
        return new static;
    }

    /**
     * Returns whole config or concrete group data
     *
     * @param null $key
     * @return Kohana_Config_Group|array
     */
    protected function config($key = NULL)
    {
        if ( ! static::$_config )
        {
            static::$_config = Kohana::$config->load('sites');
        }

        return $key ? self::$_config->get($key) : self::$_config;
    }

    /**
     * Performs search for per-site directory for provided domain
     *
     * @param $base_path string
     * @param $domain string
     * @return string|null
     */
    protected function search($base_path, $domain)
    {
        $directories = $this->get_subdirectories($base_path);

        if ( ! $directories )
        {
            $this->missing_paths($base_path);
            return NULL;
        }

        foreach ( $directories as $site_name )
        {
            $config_file = $base_path.DIRECTORY_SEPARATOR.$site_name.DIRECTORY_SEPARATOR.'config.php';

            if ( file_exists($config_file) )
            {
                // Route by wildcards from per-site config
                $wildcards = Kohana::load($config_file);
            }
            else
            {
                // Route directory name
                $wildcards = array($site_name);
            }

            // Search for matching domain
            foreach ( $wildcards as $url )
            {
                if ( $this->check($domain, $url) )
                    return $site_name;
            }
        }

        return NULL;
    }

    /**
     * Returns list of per-site directories
     *
     * @param $base_path
     * @return array
     * @throws HTTP_Exception_500
     */
    protected function get_subdirectories($base_path)
    {
        $paths = array();

        /** @var DirectoryIterator|DirectoryIterator[] $dir */
        $dir = new DirectoryIterator($base_path);

        if ( ! $dir->isDir() )
            throw new HTTP_Exception_500('The path is not a directory: :path', array(':path' => Debug::path($base_path)));

        foreach ($dir as $item)
        {
            if ( ! $item->isDir() OR $item->isDot() )
                continue;

            $paths[] = $item->getFilename();
        }

        return $paths;
    }

    /**
     * Checks the domain for provided wildcard
     *
     * @param $domain string
     * @param $wildcard string Wildcard like "*.example.com"
     * @return bool
     */
    protected function check($domain, $wildcard)
    {
        return fnmatch($wildcard, $domain);
    }

    /**
     * Behavior for unknown domain
     *
     * @param string $domain Requested domain
     * @throws HTTP_Exception_404
     */
    protected function missing_domain($domain)
    {
        throw new HTTP_Exception_404('Unknown domain name requested: :domain', array(':domain' => $domain));
    }

    /**
     * Behavior for empty base directory
     *
     * @param string $base_path Base directory path
     * @throws HTTP_Exception_500
     */
    protected function missing_paths($base_path)
    {
        throw new HTTP_Exception_500('No paths found for MultiSite module in :path;', array(':path' => $base_path));
    }

}
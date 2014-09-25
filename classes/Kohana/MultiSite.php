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
     * Performs search for per-site directory and adds it to CFS
     *
     * @return $this
     * @throws Kohana_Exception
     */
    public function process()
    {
        // Base script directory
        $doc_root = $this->doc_root();

        $sites_path = realpath(static::config('path'));

        if ( strpos($doc_root, $sites_path) === FALSE )
            throw new Kohana_Exception('Request must be initiated from per-site directory');

        // Getting site name
        $site_name = dirname(str_replace($sites_path.DIRECTORY_SEPARATOR, '', $doc_root));

        $site_path = realpath($sites_path.DIRECTORY_SEPARATOR.$site_name);

        // Saving per-site dir for later use
        static::$_site_dir = $site_path;

        // Connecting per-site directory to CFS so it becomes top level path (which overrides /application/ path)
        Kohana::prepend_path($site_path);

        return $this;
    }

    public function doc_root()
    {
        static $path;

        if ( ! $path )
        {
            $path = ( php_sapi_name() == 'cli' )
                ? dirname(realpath($_SERVER['argv'][0]))
                : realpath(getenv('DOCUMENT_ROOT'));
        }

        if ( ! $path )
            throw new Kohana_Exception('Can not detect document root');

        return $path;
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
     * Getter for current per-site directory
     *
     * @return string
     */
    public function site_path()
    {
        return static::$_site_dir;
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

}

<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_MultiSite
{
    /**
     * @var MultiSite
     */
    protected static $_instance;

    /**
     * @var string Per-site directory
     */
    protected $_site_path;

    /**
     * @var Kohana_Config_Group
     */
    protected $_config;


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
     * Creates instance of current class
     *
     * @return static
     */
    protected static function factory()
    {
        return new static;
    }

    /**
     * Restrict direct *new* call
     */
    protected function __construct()
    {
        $this->detect_site();
    }

    protected function detect_site()
    {
        // Base script directory
        $doc_root = $this->doc_root();

        $sites_path = realpath($this->config('path'));

        if ( strpos($doc_root, $sites_path) === FALSE )
            throw new Kohana_Exception('Request must be initiated from per-site directory');

        // Getting site name
        $relative_path = str_replace($sites_path.DIRECTORY_SEPARATOR, '', $doc_root);

        // No processing needed if inside `core` path
        if ($relative_path == 'core')
            return FALSE;

        $site_name = dirname($relative_path);
        $site_path = realpath($sites_path.DIRECTORY_SEPARATOR.$site_name);

        // Saving per-site dir for later use
        $this->_site_path = $site_path;

        return TRUE;
    }

    protected function prepend_cfs_path($path)
    {
        Kohana::prepend_path($path);
    }

    protected function kohana_reinit()
    {
        Kohana::reinit();
    }

    /**
     * Performs search for per-site directory and adds it to CFS
     *
     * @return bool
     * @throws Kohana_Exception
     */
    public function process()
    {
        if (!$this->is_site_detected())
            return FALSE;

        // Add site-related log
        $this->enable_logs();

        // Include Composer dependencies first (they may be used in site-related modules)
        $this->include_composer_deps();

        $this->init_modules();

        // Connecting per-site directory to CFS so it becomes top level path (it overrides /application/ and all modules)
        $this->prepend_cfs_path($this->_site_path);

        // Repeat init
        $this->kohana_reinit();

        return TRUE;
    }

    protected function enable_logs()
    {
        Kohana::$log->attach(
            new Log_File($this->site_path().DIRECTORY_SEPARATOR.'logs'),
            Log::INFO
        );
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
        return $this->_site_path;
    }

    public function modules_path()
    {
        return $this->_site_path.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR;
    }

    public function is_site_detected()
    {
        return (bool) $this->_site_path;
    }

    protected function init_modules()
    {
        // Getting site-related modules
        $site_modules = $this->get_site_modules_config();

        if (!$site_modules)
            return;

        $loaded_modules = Kohana::modules();

        // Adding modules to CFS (they overrides /application/ and other core modules)
        foreach (array_reverse($site_modules) as $module_name => $module_path)
        {
            if (isset($loaded_modules[$module_name]))
                throw new BetaKiller\Exception('Module :name already loaded from :path', [
                    ':name' => $module_name,
                    ':path' => $module_path,
                ]);

            $this->prepend_cfs_path($module_path);
        }

        // Execute init.php in each module (if exists)
        foreach ($site_modules as $module_path)
        {
            $init = $module_path.DIRECTORY_SEPARATOR.'init'.EXT;

            if (file_exists($init))
            {
                // Include the module initialization file once
                require_once $init;
            }
        }
    }

    /**
     * Returns array of paths of site modules
     *
     * @return array
     */
    protected function get_site_modules_config()
    {
        $modules_config = $this->_site_path.DIRECTORY_SEPARATOR.'modules'.EXT;

        if (file_exists($modules_config))
        {
            return include_once $modules_config;
        }

        return NULL;
    }

    protected function get_existent_site_modules()
    {

        $modules = [];
        $modules_path = $this->_site_path.DIRECTORY_SEPARATOR.'modules';

        if (file_exists($modules_path))
        {
            foreach (new DirectoryIterator($modules_path) as $file)
            {
                if ($file->isDot() || !$file->isDir())
                    continue;

                $modules[ $file->getFilename() ] = $file->getRealPath();
            }
        }

        return $modules;
    }

    /**
     * Returns whole config or concrete group data
     *
     * @param string|null $key
     * @return Kohana_Config_Group|array
     */
    protected function config($key = NULL)
    {
        if ( ! $this->_config )
        {
            // TODO DI
            $this->_config = Kohana::$config->load('sites');
        }

        return $key ? $this->_config->get($key) : $this->_config;
    }

    /**
     * Initialize Composer dependencies
     */
    protected function include_composer_deps()
    {
        $vendor_autoload = implode(DIRECTORY_SEPARATOR, [$this->site_path(), 'vendor', 'autoload.php']);

        if ( file_exists($vendor_autoload) )
        {
            require_once $vendor_autoload;
        }
    }
}

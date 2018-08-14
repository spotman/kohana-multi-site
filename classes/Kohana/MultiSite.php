<?php defined('SYSPATH') OR die('No direct script access.');

abstract class Kohana_MultiSite
{
    /**
     * @var MultiSite
     */
    protected static $_instance;

    protected $initialized = false;

    protected $siteDetected = false;

    /**
     * @var string Per-site directory name
     */
    protected $siteName;

    /**
     * @var string Per-site directory full path
     */
    protected $sitePath;

    /**
     * @var Kohana_Config_Group
     */
    protected $config;


    /**
     * Returns instance of current class
     *
     * @return MultiSite
     */
    public static function instance()
    {
        if (!static::$_instance) {
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
        $this->siteDetected = $this->detectSite();
    }

    protected function detectSite()
    {
        // Base script directory
        $docRoot = $this->docRoot();

        $sitesPath = realpath($this->config('path'));

//        Kohana::$log->add(Log::DEBUG, 'Sites path is :path', [
//            ':path' => $sitesPath,
//        ]);

        if (strpos($docRoot, $sitesPath) === false) {
            throw new \Kohana_Exception('Request must be initiated from per-site directory, but [:path] given', [
                ':path' => $docRoot,
            ]);
        }

        // Getting site name
        $relativePath = str_replace($sitesPath.DIRECTORY_SEPARATOR, '', $docRoot);

        // No processing needed if inside BetaKiller `core` path
        if (in_array($relativePath, ['core', 'betakiller'], true)) {
            return false;
        }

        $siteName = explode(DIRECTORY_SEPARATOR, $relativePath)[0];
        $sitePath = realpath($sitesPath.DIRECTORY_SEPARATOR.$siteName);

        // Saving per-site dir for later use
        $this->siteName = $siteName;
        $this->sitePath = $sitePath;

//        Kohana::$log->add(Log::DEBUG, 'Site detected, name = :name, path = :path, root = :root', [
//            ':name' => $siteName,
//            ':path' => $sitePath,
//            ':root' => $docRoot,
//        ]);

        return true;
    }

    /**
     * @param string $path
     *
     * @return void
     * @throws \ReflectionException
     */
    protected function prependCfsPath($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $reflection = new \ReflectionProperty(Kohana::class, '_paths');
        $reflection->setAccessible(true);

        $paths = $reflection->getValue();

        array_unshift($paths, $path);

        $reflection->setValue(null, $paths);
    }

    /**
     * @return void
     */
    protected function patchKohana()
    {
        // Drop cache because of init file
        // Inject fake config reader for resetting config groups cache (add last so no performance impact produced)
        Kohana::$config->attach(new FakeConfigReader(), false);

        // Reload site-related config
        $config = Kohana::$config->load('init')->as_array();

        // Update the base URL for proper URL creation
        Kohana::$base_url = rtrim($config['base_url'], '/').'/';
    }

    public function initIfAllowed()
    {
        if ($this->config('init')) {
            $this->init();
        }
    }

    /**
     * Performs search for per-site directory and adds it to CFS
     *
     * @return bool
     * @throws Kohana_Exception
     */
    public function init()
    {
        if (!$this->isSiteDetected()) {
            return false;
        }

        if ($this->initialized) {
            throw new Kohana_Exception('MultiSite was already initialized');
        }

        // Getting site-related modules
        $siteModules = $this->getSiteModulesConfig();

        // Add site-related log
        $this->enableLogs();

        // Include Composer dependencies first (they may be used in site-related modules)
        $this->includeComposerDependencies();

        // Add site-related modules to CFS first so it would be placed on top of core but under site app
        if ($siteModules) {
            $this->addModulesToCFS($siteModules);
        }

        // Connecting per-site directory to CFS so it becomes top level path (it overrides /application/ and all modules)
        // Placing it after initializing modules so it would be placed first (prepended)
        $this->prependCfsPath($this->sitePath);

        // Repeat init after adding site-related config directory via CFS
        $this->patchKohana();

        // Init site-related modules if they exist
        if ($siteModules) {
            $this->initModules($siteModules);
        }

        // Final custom initialization
        $this->initSite();

        $this->initialized = true;

        return true;
    }

    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    protected function enableLogs()
    {
        if (!$this->config('logs')) {
            return;
        }

        $logsDir = $this->getWorkingPath().DIRECTORY_SEPARATOR.'logs';

        Kohana::$log->attach(new Log_File($logsDir), Log::INFO);
    }

    public function docRoot()
    {
        static $path;

        if (!$path) {
            $path = (PHP_SAPI === 'cli')
                ? realpath(getcwd() ?: DOCROOT)
                : realpath(getenv('DOCUMENT_ROOT'));
        }

        if (!$path) {
            throw new Kohana_Exception('Can not detect document root');
        }

        return $path;
    }

    /**
     * Init site if init.php exists
     */
    protected function initSite()
    {
        // Loading custom init.php file for current site if exists
        $initFile = $this->getSitePath().DIRECTORY_SEPARATOR.'init.php';

        if (file_exists($initFile)) {
            Kohana::load($initFile);
        }
    }

    /**
     * Getter for current per-site directory full path
     *
     * @return string
     */
    public function getSitePath()
    {
        return $this->sitePath;
    }

    public function getWorkingPath()
    {
        return $this->getSitePath() ?: APPPATH;
    }

    /**
     * Getter for current per-site directory name
     *
     * @return string
     */
    public function getSiteName()
    {
        return $this->siteName;
    }

    public function getWorkingName()
    {
        return $this->getSiteName() ?: 'core';
    }

    public function siteModulesPath()
    {
        return $this->sitePath.DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR;
    }

    public function isSiteDetected()
    {
        return $this->siteDetected;
    }

    protected function addModulesToCFS(array $siteModules)
    {
        $loadedModules = Kohana::modules();

        // Adding modules to CFS (they overrides /application/ and other core modules)
        foreach (array_reverse($siteModules) as $moduleName => $modulePath) {
            if (isset($loadedModules[$moduleName])) {
                throw new \BetaKiller\Exception('Module :name already loaded from :path', [
                    ':name' => $moduleName,
                    ':path' => $modulePath,
                ]);
            }

            $this->prependCfsPath($modulePath);
        }
    }

    protected function initModules(array $siteModules)
    {
        // Execute init.php in each module (if exists)
        foreach ($siteModules as $modulePath) {
            $init = $modulePath.DIRECTORY_SEPARATOR.'init'.EXT;

            if (file_exists($init)) {
                // Include the module initialization file once
                require_once $init;
            }
        }
    }

    /**
     * Returns array of paths of site modules
     *
     * @return string[]
     */
    protected function getSiteModulesConfig()
    {
        $modulesConfig = implode(DIRECTORY_SEPARATOR, [$this->sitePath, 'config', 'modules'.EXT]);

        if (file_exists($modulesConfig)) {
            /** @noinspection PhpIncludeInspection */
            return include $modulesConfig;
        }

        return null;
    }

    protected function getExistentSiteModules()
    {
        $modules     = [];
        $modulesPath = $this->sitePath.DIRECTORY_SEPARATOR.'modules';

        if (file_exists($modulesPath)) {
            foreach (new DirectoryIterator($modulesPath) as $file) {
                if ($file->isDot() || !$file->isDir()) {
                    continue;
                }

                $modules[$file->getFilename()] = $file->getRealPath();
            }
        }

        return $modules;
    }

    /**
     * Returns whole config or concrete group data
     *
     * @param string|null $key
     *
     * @return Kohana_Config_Group|array
     */
    protected function config($key = null)
    {
        if (!$this->config) {
            // TODO DI
            $this->config = Kohana::$config->load('sites');
        }

        return $key ? $this->config->get($key) : $this->config;
    }

    /**
     * Initialize Composer dependencies
     */
    protected function includeComposerDependencies()
    {
        $vendorAutoload = implode(DIRECTORY_SEPARATOR, [$this->getSitePath(), 'vendor', 'autoload.php']);

        if (file_exists($vendorAutoload)) {
            require_once $vendorAutoload;
        }
    }
}

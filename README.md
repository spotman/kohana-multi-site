kohana-multi-site
=================

Simple module for Kohana framework v3.3 for handling multiple sites upon one Kohana instance.
The module provides one directory for each site (it works exact like additional "application" directory in Kohana CFS).


Installation
------------

### Step 1

Place this module at the first position in list of Kohana modules

```php
<?php defined('SYSPATH') OR die('No direct script access.');

return array(

    // Place it first for correct initialization of per-site classes and configs
    'multi-site'    => MODPATH.'multi-site',    // Multiple apps on top of single engine

    // And here goes your normal list of modules
    'api'           => MODPATH.'api',           // API subsystem
    'auth'          => MODPATH.'auth',          // Basic authentication
    'cache'         => MODPATH.'cache',         // Caching with multiple backends
);

```
By doing this you`ll allow Kohana initialize other modules with classes and conigs from per-site directory 

### Step 2
Create base directory `sites` near your `application` directory (or in another place, which you can set in `config/sites.php`)

### Step 3
If on Step 2 you have not been satisfied with default base directory location, then copy `config/sites.php` to `/application/config` and change the 'path' config key.

### Step 4
Create subdirectories for your sites. My personal naming convention is to use domain name of the site (or wildcard like *.example.com).

### Step 5
If you use `composer`, you may set site dependencies in `composer.json` file so they would be installed in `vendor` or `modules` directory. Vendor libs are autoloaded automatically, but you need to set up modules list in `modules.php` file (it has default Kohana format).

### Step 6
Create standart Kohana directories (like config/classes/views) and put site-related files in them.

If your site needs custom initialization, you can put it in `init.php` in the per-site directory. This file would be called after initialization of all modules.

Example directory structure:

```
/application
/modules
    /first-common-module
    /second-common-module
/sites
    /example.com
        /classes
        /config
        /i18n
        /modules
            /site-module1
                /classes
                /views
            /site-module2
                /views
                init.php
        /views
        /vendor
            /vendor1
                /lib1
            /vendor2
                /lib2
        config.php
        init.php
        modules.php
        composer.json

    /another-example.com
        /classes
        /config
        /i18n
        /views
        config.php

/system
```

### Step 7
You need to create file `/application/classes/Kohana.php` with following content:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana extends Kohana_Core {

    public static function prepend_path($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        array_unshift(static::$_paths, $path);
    }
    
    public static function modules(array $modules = NULL)
    {
        $result = parent::modules($modules);

        if ( $modules !== NULL )
        {
            MultiSite::instance()->initSite();
        }

        return $result;
    }

    public static function reinit()
    {
        self::$_init = FALSE;

        // Drop cache because of init file content was cached
        self::$config->drop_cache();

        $config = Kohana::$config->load('init')->as_array();
        parent::init($config);
    }
}
```

because of `Kohana::$_paths` is protected member and weird Kohana init logic.

Also you need to create file `/application/classes/Config.php` with following content:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Config extends Kohana_Config
{
    public function drop_cache()
    {
        $this->_groups = array();
    }
}
```

because of internal caching inside of `Config`.

### Step 8
Enjoy :)
And feel free to create issues.

[MIT License](LICENSE)

kohana-multi-site
=================

Simple module for Kohana framework for handling multiple sites upon one Kohana instance.
Module provides one directory for each site (it works exact like additional "application" directory in Kohana CFS).

**This module is an early alpha version so everything may be changed later.**


Installation
------------

### Step 1

Place this module at the first position in list of Kohana modules

```php
<?php defined('SYSPATH') OR die('No direct script access.');

return array(

    // Place it first for correct initialization of per-site classes and configs
    'multi-site'            => MODPATH.'multi-site',            // Multiple apps on top of single engine

    'api'                   => MODPATH.'api',                   // API subsystem
    'auth'                  => MODPATH.'auth',                  // Basic authentication
    'cache'                 => MODPATH.'cache',                 // Caching with multiple backends
    
    ...

```
By doing this you`ll allow Kohana initialize other modules with classes and conigs from per-site directory 

### Step 2
Create directory `sites` near your `application` directory (or in another place, which you can set in `config/sites.php`)

### Step 3
Copy `config/sites.php` to `/application/config` and change it to something like this

```php
<?php defined('SYSPATH') OR die('No direct script access.');

return array(

    'sites' =>  array(

        // One concrete domain site mapped to directory called "example.com"
        'example.com'   =>  array(),
        
        // One concrete domain site mapped to directory called "another-site"
        'another-example.com'   =>  array(
            'path'  =>  'another-example'
        ),
        
        // All domains matching wildcard "*.example.com" mapped to directory called "*.example.com"
        '*.example.com' =>  array(),

        // All domains matching wildcard "*.another-example.com" mapped to directory called "another-site-subs"
        '*.another-example.com' =>  array(
            'path'  =>  'another-site-subs'
        ),
        
        // 3 concrete domain sites mapped to directory called "dev-zone"
        'dev-zone' =>  array(
            'urls'  =>  array('admin.dev', 'tests.dev', 'tools.dev')
        ),

    )
);
```
or this

```php
<?php defined('SYSPATH') OR die('No direct script access.');

return array(

    // This way you`ll have 3 concrete sites and 3 per-site directories named like a domain
    // But no wildcards and custom per-site directory name
    'sites' =>  array('example.com', 'dev.example.com', 'another-example.com')
);
```

### Step 4
Create subdirectories for your sites (according to your config)

### Step 5
Create standart Kohana directories (like config/classes/views) and put site-related files in them

Example directory structure:

```
/application
/modules
/sites
    /example.com
        /classes
        /config
        /i18n
        /views
    /another-example.com
        /classes
        /config
        /i18n
        /views
/system
```

### Step 6
Also you need to create file `/application/classes/Kohana.php` with following content:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana extends Kohana_Core {

    public static function prepend_path($path)
    {
        $path = rtrim($path, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        array_unshift(static::$_paths, $path);
    }

}
```

because of `Kohana::$_paths` is protected member.

### Step 7
Enjoy :)
And feel free to create issues.

[MIT License](LICENSE)

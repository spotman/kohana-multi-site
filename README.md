kohana-multi-site
=================

Simple module for Kohana framework for handling multiple sites upon one Kohana instance.
Module provides one directory for each site (it works exact like additional "application" directory in Kohana CFS).

**This module is an early alpha version so everything may be changed later.**


Installation
------------


1.  Create directory *sites* near your *application* directory (or in another place, which you can set in config/sites.php)
2.  Create subdirectories for your sites (one site => one directory)
3.  Put site-related directories and files (like config/classes/views)

Example directory structure:

    /application
    /modules
    /sites
        /example.com
            /classes
            /config
            /i18n
            /views
        /homepage.info
            /classes
            /config
            /i18n
            /views
    /system

Also you need to create file */application/classes/Kohana.php* with following content:

```php
<?php defined('SYSPATH') OR die('No direct script access.');

class Kohana extends MultiSite_Kohana {}
```

See file *classes/MultiSite/Kohana.php* for more info.

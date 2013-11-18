kohana-multi-site
=================

Simple module for Kohana framework for handling multiple sites upon one Kohana instance.
Module provides one directory for each site (it works exact like additional "application" directory in Kohana CFS).

**This module is an early alpha version so everything may be changed later.**


Installation
------------

1.  Create directory *sites* near your *application* directory (or in another place, which you can set in config/sites.php)
2.  Create subdirectories for your sites (one site => one directory)
3.  Place directories and files
4.  Enjoy :)

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
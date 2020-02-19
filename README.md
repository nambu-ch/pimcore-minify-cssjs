# Pimcore MinifyCssJsBundle
Pimcore 5.x/6.x package which automatically joins and minifies css and js files. Helpful when mod_pagespeed 
is not available.

## Install and Enable

```php
composer require nambu-ch/pimcore-minify-cssjs
php bin/console pimcore:bundle:enable MinifyCssJs
```

## Usage

Include your CSS and JS Files as you are used to it with the headLink and headScript Template Helpers

```php
$view->headLink()->appendStylesheet('/static/css/example.css');
echo $view->headLink();

$view->headScript()->appendFile('/static/js/example.js');
echo $view->headScript();
```

While in debug mode all Files are includes separatly, but via cache-buster.

In non-debug mode all css and js files will be combined and minified by the package `matthiasmullie/minify`
which is a dependency. The generated file will be placed in `web/var/tmp`. To regenerate the cached file 
either clear your tmp files via pimcore menu or switch to debug mode again.

While you are in debug mode the helpers will check if any of the used css or js files is newer than the
generated one. In that case the generated file is deleted and will be generated with a new timestamp
the next time someone in non-debug mode visits the site.
<?php
namespace MinifyCssJsBundle;

use Pimcore\Extension\Bundle\AbstractPimcoreBundle;
use Pimcore\Extension\Bundle\Traits\PackageVersionTrait;

class MinifyCssJsBundle extends AbstractPimcoreBundle {

    use PackageVersionTrait;

    const PACKAGE_NAME = 'nambu.ch/pimcore-minify-cssjs';

    protected function getComposerPackageName(): string
    {
        return self::PACKAGE_NAME;
    }

}

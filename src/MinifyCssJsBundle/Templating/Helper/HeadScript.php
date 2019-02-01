<?php

namespace MinifyCssJsBundle\Templating\Helper;

use MatthiasMullie\Minify\JS;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\GenericEvent;

class HeadScript extends \Pimcore\Templating\Helper\HeadScript {

    private $tmpPath = '/var/tmp';

    protected function prepareEntries() {
        foreach ($this as &$item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::VIEW_HELPER_HEAD_SCRIPT, new GenericEvent($this, [
                'item' => $item
            ]));
        }
    }

    public function toString($indent = null) {
        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        if ($this->view) {
            $useCdata = $this->view->doctype()->isXhtml() ? true : false;
        } else {
            $useCdata = $this->useCdata ? true : false;
        }
        $escapeStart = ($useCdata) ? '//<![CDATA[' : '//<!--';
        $escapeEnd = ($useCdata) ? '//]]>' : '//-->';

        $items = [];
        $combine = [];
        $this->getContainer()->ksort();

        foreach ($this as $item) {
            if (!$this->_isValid($item)) {
                continue;
            }

            if (strpos($item->href, 'http') !== 0) {
                $combine[] = $item->attributes["src"];
                if (\Pimcore::inDebugMode()) {
                    if ($this->isCacheBuster()) {
                        if (isset($item->attributes["src"])) {
                            $realFile = PIMCORE_WEB_ROOT.$item->attributes["src"];
                            if (file_exists($realFile)) {
                                $item->attributes["src"] = '/cache-buster-'.filemtime($realFile).$item->attributes["src"];
                            }
                        }
                    }
                    $items[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
                }
            } else {
                $items[] = $this->itemToString($item, $indent, $escapeStart, $escapeEnd);
            }
        }

        if (\Pimcore::inDebugMode()) {
            //delete all cached file in debug mode
            $timestamps = [];
            foreach ($combine as $src) {
                $timestamps[] = filemtime(PIMCORE_WEB_ROOT.$src);
            }
            $file = PIMCORE_WEB_ROOT.$this->tmpPath.'/fc_'.md5(json_encode($combine)).".js";
            if (is_file($file) && count($timestamps) > 0 && filemtime($file) < max($timestamps)) {
                unlink($file);
            }
        } else {
            $file = $this->tmpPath.'/fc_'.md5(json_encode($combine)).".js";
            if (!is_file(PIMCORE_WEB_ROOT.$file)) {
                $minifier = new JS();
                foreach ($combine as $src) {
                    $minifier->add(PIMCORE_WEB_ROOT.$src);
                }
                $minifier->minify(PIMCORE_WEB_ROOT.$file);
            }

            //append minified js
            $minJs = new \stdClass();
            $minJs->type = 'text/javascript';
            $minJs->attributes["src"] = '/cache-buster-'.filemtime(PIMCORE_WEB_ROOT.$file).$file;
            $items[] = $this->itemToString($minJs, '', '', '');
        }

        return implode($this->_escape($this->getSeparator()), $items);
    }

}

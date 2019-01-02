<?php

namespace MinifyCssJsBundle\Templating\Helper;

use MatthiasMullie\Minify\CSS;
use Pimcore\Event\FrontendEvents;
use Symfony\Component\EventDispatcher\GenericEvent;

class HeadLink extends \Pimcore\Templating\Helper\HeadLink {

    private $tmpPath = '/var/tmp';

    protected function prepareEntries() {
        foreach ($this as &$item) {
            \Pimcore::getEventDispatcher()->dispatch(FrontendEvents::VIEW_HELPER_HEAD_LINK, new GenericEvent($this, [
                'item' => $item
            ]));
        }
    }

    public function toString($indent = null) {
        $this->prepareEntries();

        $indent = (null !== $indent)
            ? $this->getWhitespace($indent)
            : $this->getIndent();

        $items = [];
        $combine = [];
        $this->getContainer()->ksort();
        foreach ($this as $item) {
            if ($item->type == 'text/css' && $item->conditionalStylesheet == false && strpos($item->href, 'http') !== 0) {
                $combine[$item->media][] = $item->href;
                if ($this->isCacheBuster()) {
                    if (isset($item->href)) {
                        $realFile = PIMCORE_WEB_ROOT.$item->href;
                        if (file_exists($realFile)) {
                            $item->href = '/cache-buster-'.filemtime($realFile).$item->href;
                        }
                    }
                }
                if (\Pimcore::inDebugMode()) {
                    $items[] = $this->itemToString($item);
                }
            } else {
                $items[] = $this->itemToString($item);
            }
        }

        // loop items and combine them
        foreach ($combine as $media => $styles) {
            //tmp filename
            if (\Pimcore::inDebugMode()) {
                $timestamps = [];
                foreach ($styles as $src) {
                    $timestamps[] = filemtime(PIMCORE_WEB_ROOT.$src);
                }
                $file = PIMCORE_WEB_ROOT.$this->tmpPath.'/fc_'.md5(json_encode($styles)).".css";
                if (is_file($file) && filemtime($file) < max($timestamps)) {
                    unlink($file);
                }
            } else {
                $file = $this->tmpPath.'/fc_'.md5(json_encode($styles)).".css";
                if (!is_file(PIMCORE_WEB_ROOT.$file)) {
                    $minifier = new CSS();
                    foreach ($styles as $src) {
                        $minifier->add(PIMCORE_WEB_ROOT.$src);
                    }
                    $minifier->minify(PIMCORE_WEB_ROOT.$file);
                }

                //append minified stylesheet
                $minStyles = new \stdClass();
                $minStyles->rel = 'stylesheet';
                $minStyles->type = 'text/css';
                $minStyles->href = '/cache-buster-'.filemtime(PIMCORE_WEB_ROOT.$file).$file;
                $minStyles->media = $media;
                $minStyles->conditionalStylesheet = false;
                $items[] = $this->itemToString($minStyles);
            }
        }

        return $indent.implode($this->_escape($this->getSeparator()).$indent, $items);
    }

}
<?php

namespace HauerHeinrich\HhHttp2Push\Hooks;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2019 Christian Hackl <web@hauer-heinrich.de>, www.hauer-heinrich.de
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use DOMDocument;
use DOMXPath;
// use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\TypoScript\TemplateService;

/**
 * HTTP/2 Push
 *
 * @author Christian Hackl <web@hauer-heinrich.de>
 * @package TYPO3
 * @subpackage hh_http2_push
 */
class ContentPostProcessor {
    /**
     * extensionKey
     *
     * @var string
     */
    protected $extensionKey = '';

    /**
     * cssSettings
     *
     * @var array
     */
    protected $cssSettings = [];

    /**
     * jsLibsSettings
     *
     * @var array
     */
    protected $jsLibsSettings = [];

    /**
     * jsSettings
     *
     * @var array
     */
    protected $jsSettings = [];

    /**
     * jsFooterLibsSettings
     *
     * @var array
     */
    protected $jsFooterLibsSettings = [];

    /**
     * versionNumberInFilename
     *
     * @var string
     */
    protected $versionNumberInFilename;

    /**
     * templateService
     *
     * @var \TYPO3\CMS\Core\TypoScript\TemplateService
     */
    protected $templateService;

    /**
     * preloadLinks
     *
     * @var array
     */
    protected $preloadLinks = [];

    /**
     * extensionConfiguration
     *
     * @var array
     */
    protected $extensionConfiguration = [];

    public function __construct() {
        $this->extensionKey = 'hh_http2_push';

        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $extbaseFrameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        $this->templateService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');

        $this->cssSettings = $extbaseFrameworkConfiguration['page.']['includeCSS.']; // includeJSLibs
        $this->jsLibsSettings = $extbaseFrameworkConfiguration['page.']['includeJSLibs.'];
        $this->jsSettings = $extbaseFrameworkConfiguration['page.']['includeJS.'];
        $this->jsFooterLibsSettings = $extbaseFrameworkConfiguration['page.']['includeJSFooterlibs.'];

        $this->versionNumberInFilename = $GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'];

        $version9 = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('9.3');
        if($version9) {
            // TYPO3 9:
            $this->extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extensionKey];
        } else {
            // TYPO3 =< 8
            // Typo3 extension manager gearwheel icon (ext_conf_template.txt)
            $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
        }
    }

    public function addPushHeader(&$params) {
        $cookie_name = "preload_push";
        $cookie_value = "true";

        $cookieLifeTime = $this->extensionConfiguration['cookieLifeTime'];
        $concatenateFiles = $this->extensionConfiguration['concatenateFiles'];
        $debug = $this->extensionConfiguration['debug'];

        // Set cookie and add ResposeHeader Link
        if(!isset($_COOKIE[$cookie_name]) && $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] != true || $debug == true) {
            setcookie($cookie_name, $cookie_value, time() + $cookieLifeTime, "/"); // 86400 = 1 day

            // Multi method call cause of file keys for example: cssFiles.file10 and cssLibs.file10 / duplicate key
            // CSS
            $this->setCssHeaderLink($params['cssLibs']);
            $this->setCssHeaderLink($params['cssFiles']);
            // JavaScript
            $this->setJsHeaderLink($params['jsLibs']);
            $this->setJsHeaderLink($params['jsFiles']);
            $this->setJsHeaderLink($params['jsFooterFiles']);
            $this->setJsHeaderLink($params['jsFooterLibs']);

            // delete last ','
            $index = count( $this->preloadLinks ) - 1;
            $value = $this->preloadLinks[$index];
            $this->preloadLinks[$index] = substr_replace($this->preloadLinks[$index], "", -1);

            header('Link: '.$this->checkHeaderLengthAndReturnImplodedArray($this->preloadLinks));
        } else {
            // echo ("cookie ist bereits gesetzt, keine Link Ausgabe");
        }
    }

    /**
     * setCssHeaderLink
     *
     * @param string $mergedOrigCssFiles
     * @return void
     */
    public function setCssHeaderLink($mergedOrigCssFiles) {
        if($this->cssSettings && !empty($mergedOrigCssFiles)) {
            // Get original files in the document
            // Css Files
            $origCssFiles = [];
            $dCss = new DOMDocument();
            $dCss->loadHTML($mergedOrigCssFiles); // the variable $ads contains the HTML code above
            $xpath = new DOMXPath($dCss);
            $ls_ads = $xpath->query('//link');

            foreach ($ls_ads as $key => $value) {
                array_push($origCssFiles, $value->getAttribute('href'));
            }

            foreach ($this->cssSettings as $key => $value) {
                // file10 { ajax=1 excludeFromConcatenation = 1 ... }
                if(substr($key, -1) === '.') {
                    if(is_array($value)) {
                        if($value['preloadPush'] === '1') {
                            $filePath = $this->cssSettings[substr_replace($key, "", -1)];
                            $relFilePath = $this->templateService->getFileName($filePath);
                            $linkVal = '';

                            if($this->versionNumberInFilename === 'querystring') {
                                foreach ($origCssFiles as $origKey => $origValue) {
                                    $valueWithoutQueryString = explode('?', $origValue);

                                    if($valueWithoutQueryString[0] === $relFilePath) {
                                        $linkVal = $origValue;
                                    } else if ($this->extensionConfiguration['concatenateFiles'] == true && strpos($origValue, 'compressed/merged-') !== false) {
                                        $linkVal = $origValue;
                                    } else if ($this->extensionConfiguration['compressFiles'] == true && strpos($origValue, 'compressed/') !== false && strpos($origValue, 'min.') !== false) {
                                        $linkVal = $origValue;
                                    }
                                }
                            }

                            if(!empty($linkVal)) {
                                $this->preloadLinks[] = '</'.$linkVal.'>; rel=preload; as=style,';
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * setJsHeaderLink
     *
     * @param string $mergedOrigJsFiles
     * @return void
     */
    public function setJsHeaderLink($mergedOrigJsFiles) {
        if($this->jsSettings && !empty($mergedOrigJsFiles)) {
            // Get original files in the document
            // JavaScript Files
            $origJsFiles = [];
            $dJs = new DOMDocument();
            $dJs->loadHTML($mergedOrigJsFiles); // the variable $ads contains the HTML code above
            $xpath = new DOMXPath($dJs);
            $ls_ads = $xpath->query('//script');

            foreach ($ls_ads as $key => $value) {
                array_push($origJsFiles, $value->getAttribute('src'));
            }

            foreach ($this->jsSettings as $key => $value) {
                // file10 { ajax=1 excludeFromConcatenation = 1 ... }
                if(substr($key, -1) === '.') {
                    if(is_array($value)) {
                        if($value['preloadPush'] === '1') {
                            $filePath = $this->jsSettings[substr_replace($key, "", -1)];
                            $relFilePath = $this->templateService->getFileName($filePath);
                            $linkVal = '';

                            if($this->versionNumberInFilename === 'querystring') {
                                foreach ($origCssFiles as $origKey => $origValue) {
                                    $valueWithoutQueryString = explode('?', $origValue);
                                    if($valueWithoutQueryString[0] === $relFilePath) {
                                        $linkVal = $origValue;
                                    } else if ($this->extensionConfiguration['concatenateFiles'] == true && strpos($origValue, 'compressed/merged-') !== false) {
                                        $linkVal = $origValue;
                                    } else if ($this->extensionConfiguration['compressFiles'] == true && strpos($origValue, 'compressed/') !== false && strpos($origValue, 'min.') !== false) {
                                        $linkVal = $origValue;
                                    }
                                }
                            }

                            if(!empty($linkVal)) {
                                $this->preloadLinks[] = '</'.$linkVal.'>; rel=preload; as=script,';
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * checkHeaderLengthAndReturnImplodedArray
     *
     * @param array $array
     * @return array
     */
    public function checkHeaderLengthAndReturnImplodedArray($array) {
        $limit = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['http2_push'])['webserverHeaderLengthLimit'];

        if(empty($limit)) {
            $limit = 8190;
        }

        $full = implode(', ', $array);
        if(strlen($full) < $limit) {
            return $full;
        } else {
            $short = substr($full, 0, $limit);

            return substr($short, 0, strrpos($short, ','));
        }
    }
}

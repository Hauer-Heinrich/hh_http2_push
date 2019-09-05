<?php
declare(strict_types=1);

namespace HauerHeinrich\HhHttp2Push\Middleware;

/**
 * This file is part of the "hh_http2_push" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use DOMDocument;
use DOMXPath;
use \TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use \TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Core\TypoScript\TemplateService;

class Http2PushMiddleware implements MiddlewareInterface {
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

    /**
     * pluginSettings
     *
     * @var array
     */
    protected $pluginSettings = [];

    /**
     * compressCssFiles
     *
     * @var boolean
     */
    protected $compressCssFiles = false;

    /**
     * compressJsFiles
     *
     * @var boolean
     */
    protected $compressJsFiles = false;

    /**
     * concatenateCssFiles
     *
     * @var boolean
     */
    protected $concatenateCssFiles = false;

    /**
     * concatenateJsFiles
     *
     * @var boolean
     */
    protected $concatenateJsFiles = false;

    public function initialize() {
        $this->extensionKey = 'hh_http2_push';

        $version9 = \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_branch) >= \TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger('9.3');
        if($version9) {
            // TYPO3 9:
            $this->extensionConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS'][$this->extensionKey];
        } else {
            // TYPO3 =< 8
            // Typo3 extension manager gearwheel icon (ext_conf_template.txt)
            $this->extensionConfiguration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
        }

        $objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
        $configurationManager = $objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\ConfigurationManager');
        $extbaseFrameworkConfiguration = $configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);

        $this->templateService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\TemplateService');
        $this->pluginSettings = $extbaseFrameworkConfiguration['plugin.']['tx_hhhttp2push.']['settings.'];

        $this->cssSettings = $extbaseFrameworkConfiguration['page.']['includeCSS.']; // includeJSLibs
        $this->jsLibsSettings = $extbaseFrameworkConfiguration['page.']['includeJSLibs.'];
        $this->jsSettings = $extbaseFrameworkConfiguration['page.']['includeJS.'];
        $this->jsFooterLibsSettings = $extbaseFrameworkConfiguration['page.']['includeJSFooterlibs.'];

        $this->compressCssFiles = $this->extensionConfiguration['compressCssFiles'] || $this->pluginSettings['compressCssFiles'] ? true : false;
        $this->compressJsFiles = $this->extensionConfiguration['compressJsFiles'] || $this->pluginSettings['compressJsFiles'] ? true : false;
        $this->concatenateCssFiles = $this->extensionConfiguration['concatenateCssFiles'] || $this->pluginSettings['concatenateCssFiles'] ? true : false;
        $this->concatenateJsFiles = $this->extensionConfiguration['concatenateJsFiles'] || $this->pluginSettings['concatenateJsFiles'] ? true : false;

        $this->cssFiles = $this->extensionConfiguration['cssFiles'] || $this->pluginSettings['cssFiles'] ? true : false;
        $this->cssJs = $this->extensionConfiguration['cssJs'] || $this->pluginSettings['cssJs'] ? true : false;

        $this->versionNumberInFilename = $GLOBALS['TYPO3_CONF_VARS']['FE']['versionNumberInFilename'];
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $cookie_name = "preload_push";
        $cookie_value = "true";

        $response = $handler->handle($request);
        $contents = $response->getBody()->getContents();
        $mainController = GeneralUtility::makeInstance(\TYPO3\CMS\Adminpanel\Controller\MainController::class);

        DebuggerUtility::var_dump($request->getParsedBody());
        DebuggerUtility::var_dump($response);
        // DebuggerUtility::var_dump($GLOBALS['TSFE']);
        DebuggerUtility::var_dump($contents);

        // DebuggerUtility::var_dump($response);

        // if(!isset($_COOKIE[$cookie_name])) {
        //     $this->initialize();
        //     $cookieLifeTime = $this->extensionConfiguration['cookieLifeTime'];
        //     $concatenateFiles = $this->extensionConfiguration['concatenateFiles'];
        //     $debug = $this->extensionConfiguration['debug'];
        // }

        // // Set cookie and add ResposeHeader Link
        // if(!isset($_COOKIE[$cookie_name]) && $GLOBALS['TYPO3_CONF_VARS']['FE']['debug'] != true || $debug == true) {
        //     setcookie($cookie_name, $cookie_value, time() + $cookieLifeTime, "/"); // 86400 = 1 day

        //     // Multi method call cause of file keys for example: cssFiles.file10 and cssLibs.file10 / duplicate key
        //     // CSS
        //     $this->setCssHeaderLink($params['cssLibs']);
        //     $this->setCssHeaderLink($params['cssFiles']);
        //     // JavaScript
        //     $this->setJsHeaderLink($params['jsLibs']);
        //     $this->setJsHeaderLink($params['jsFiles']);
        //     $this->setJsHeaderLink($params['jsFooterFiles']);
        //     $this->setJsHeaderLink($params['jsFooterLibs']);

        //     // delete last ','
        //     $index = count( $this->preloadLinks ) - 1;
        //     $value = $this->preloadLinks[$index];
        //     $this->preloadLinks[$index] = substr_replace($this->preloadLinks[$index], "", -1);

        //     header('Link: '.$this->checkHeaderLengthAndReturnImplodedArray($this->preloadLinks));
        // } else {
        //     // echo ("cookie ist bereits gesetzt, keine Link Ausgabe");
        // }

        return $response;
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

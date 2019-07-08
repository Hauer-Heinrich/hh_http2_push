<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE === 'FE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][] =
        'HauerHeinrich\\HhHttp2Push\\Hooks\\ContentPostProcessor->addPushHeader';
}

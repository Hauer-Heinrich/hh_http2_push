<?php
if (!defined('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE === 'FE') {
    // $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'][] =
    //     'HauerHeinrich\\HhHttp2Push\\Hooks\\ContentPostProcessor->addPushHeader';

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] =
        HauerHeinrich\\HhHttp2Push\\Hooks\\ContentPostProcessor::class . '->afterCached';
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-all'][] =
        HauerHeinrich\\HhHttp2Push\\Hooks\\ContentPostProcessor::class . '->beforeCached';

    // $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/index_ts.php']['preprocessRequest'][] =
        //     HauerHeinrich\HhSeo\Hooks\TestHook::class . '->test';
}

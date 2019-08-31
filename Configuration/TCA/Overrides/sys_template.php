<?php
defined('TYPO3_MODE') || die();

call_user_func(function() {

    $extensionKey = "hh_http2_push";

    // \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    //     $extensionKey,
    //     'Configuration/TypoScript',
    //     'Hauer-Heinrich http2 push'
    // );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScriptSetup('<INCLUDE_TYPOSCRIPT: source="FILE:EXT:hh_http2_push/Configuration/TypoScript/setup.typoscript">');
});

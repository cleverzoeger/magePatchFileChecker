<?php
/*            _                                _    _
 *        ___| | _____   __ __ _ __   _    ___(_)__(_)__ _  ___ _ __
 *       / __| |/ _ \ \ / / _ \ '_/ _| |_ |_  // _  \/ _` |/ _ \ '_/
 *      | (__| |  __/\ V /  __/ |  |_   _| / /( (_) | (_| |  __/ |
 *      \____|_|\___| \_/ \___|_|    |_|  /___\\___/ \__, |\___|_|
 *                               IT aus Leidenschaft |___/ (c)2017
 * __________________________________________________________________________
 *  
 *   This website is concepted + developed + supported by clever+zöger gmbh
 *     www.clever-zoeger.de - Moltkestr. 25, 42799 Leichlingen, Germany
 * __________________________________________________________________________
 *
 * LICENSE
 * This source file is subject of clever+zöger gmbh. You may be not allowed 
 * to change the sources without authorization of clever+zöger GmbH.
 *
 * @copyright  Copyright (c) 2010-2017 clever+zöger gmbh
 * @author:    Thomas Zöger <tzoeger@clever-zoeger.de>
 * @date:      04.06.2015
 * @category:  Cz
 * @package:   Cz_MagePatchFileChecker
 */

$toolVersion = "1.1.0";
$toolCopyright = date("Y") . " clever+zöger gmbh";

if (!isset($argv[1])) {
    echo "clever+zöger " . basename(__FILE__) . ', Version ' . $toolVersion . ", (c) $toolCopyright \n";
    echo "Usage: " . basename(__FILE__) . " [PATCH_FILE] [MAGENTO_ROOT_DIR]\n\n";
    echo "       Magento Security Patch Checker. Checks all included Patchfiles of [PATCH_FILE]\n";
    echo "       and checks if this exists inside community or local directories.\n\n";
    exit;
}

/**
 * Class magePatchFileChecker
 */
class magePatchFileChecker
{

    private $toMagentoRoot = '../';
    private $startMarker = '__PATCHFILE_FOLLOWS__';
    private $filesToCheck = array();

    private $defaultFolders = array(
        'app/code/community',
        'app/code/core',
        'app/code/local',
        'app/design/frontend/base/default',
        'app/design/frontend/default',
        'app/design/adminhtml/default/default',
        'errors',
        'lib',
    );

    private $ignoreDesignFolder = array(
        'app/design/frontend/default/iphone',
        'app/design/frontend/default/modern',
    );

    private $searchIn = array(
        'app/code/local',
        'app/code/community',
    );

    private $searchInDesign = array(
        'app/design/frontend',
        'app/design/adminhtml'
    );

    /**
     * magePatchFileChecker constructor.
     */
    function __construct()
    {
        global $argv;

        $patchFile = (isSet($argv[1])) ? $argv[1] : null;
        $mageRootPath = (isSet($argv[2])) ? $argv[2] : '.';

        if ($this->validateMageRootPath(rtrim($mageRootPath, '/') . '/'))
            $this->runChecker($patchFile);
    }

    /**
     * check path to magento root
     *
     * @param $mageRootPath
     * @return bool
     */
    protected function validateMageRootPath($mageRootPath)
    {
        if (!file_exists($mageRootPath . 'mage')) {
            echo "\nPath to magento root directory is not valid.\n";
            return false;
        } else {
            $this->toMagentoRoot = $mageRootPath;
            if (!defined('DS')) {
                define('DS', '/');
            }
            return true;
        }
    }

    /**
     * Grep patched files from patchfile and check for rewrites
     *
     * @param $patchFile
     */
    public function runChecker($patchFile)
    {
        echo "\nStart checking...\n";
        $found = false;
        $fp = fopen($patchFile, 'r');

        // Fetch all patched files inside magento patchfile
        while ($line = fgets($fp)) {
            $line = str_replace(array("\n", "\r"), '', $line);
            if ($line == $this->startMarker) {
                $found = true;
            }

            if ($found == true && (strpos($line, '---') === 0 || strpos($line, '+++') === 0)) {
                /* parse files */
                $line = str_replace(array('--- ', '+++ '), '', $line);
                $this->filesToCheck[$line] = $line;
            }
        }
        fclose($fp);

        // Check now our fetched list
        foreach ($this->filesToCheck AS $checkFile) {
            if ($checkFile == 'app/Mage.php') continue;
            $this->checkThisFile($checkFile);
        }
        echo "End patching.\n";
    }

    /**
     * Check if this file has been rewritten somewhere
     *
     * @param $patchedFile
     * @return bool
     */
    private function checkThisFile($patchedFile)
    {
        $defaultFolder = $this->getDefaultPart($patchedFile);
        if (!$defaultFolder) {
            echo '- Part not found for: ' . $patchedFile . "\n";
            return false;
        }
        $defaultFile = trim(str_replace($defaultFolder, '', $patchedFile), DS);
        if (strpos($defaultFolder, 'app/design/frontend') === 0) {
            $this->searchInDesign = array(
                'app/design/frontend',
            );
            echo "$defaultFile, $defaultFolder\n\n";
            $return = $this->checkFileInOtherPlaceDesign($defaultFile, $defaultFolder);
        } elseif (strpos($defaultFolder, 'app/design/adminhtml') === 0) {
            $this->searchInDesign = array(
                'app/design/adminhtml',
            );
            $return = $this->checkFileInOtherPlaceDesign($defaultFile, $defaultFolder);
        } else {
            $return = $this->checkFileInOtherPlace($defaultFile, $defaultFolder);
        }

        if (is_array($return) && count($return) > 0) {
            /* found in other place */
            foreach ($return AS $foundIn) {
                echo '- Patched file found in: ' . $foundIn . ' File: ' . $foundIn . DS . $defaultFile . "\n";
            }
        }
        return true;
    }

    /**
     * Check design rewrites (frontend and admin section)
     *
     * @param $defaultFile
     * @param $defaultFolder
     * @return array
     */
    private function checkFileInOtherPlaceDesign($defaultFile, $defaultFolder)
    {
        $foundPath = array();
        foreach ($this->searchInDesign AS $designFolder) {
            foreach (scandir($this->toMagentoRoot . $designFolder) AS $key => $folder) {
                if (in_array($folder, array(".", ".."))) {
                    continue;
                }

                if (is_dir($this->toMagentoRoot . $designFolder . DS . $folder)) {

                    foreach (scandir($this->toMagentoRoot . $designFolder . DS . $folder) AS $key2 => $folder2) {
                        if (in_array($folder2, array(".", ".."))) {
                            continue;
                        }

                        if (is_dir($this->toMagentoRoot . $designFolder . DS . $folder . DS . $folder2)) {
                            $folderToCheck = $designFolder . DS . $folder . DS . $folder2;

                            if (in_array($folderToCheck, $this->ignoreDesignFolder)) {
                                continue;
                            }

                            if ($folderToCheck != $defaultFolder && file_exists($this->toMagentoRoot . $folderToCheck . DS . $defaultFile)) {
                                $foundPath[] = $folderToCheck;
                            }

                        }
                    }
                }
            }
        }
        return $foundPath;
    }

    /**
     * Check other rewrites (frontend and admin section)
     * 
     * @param $defaultFile
     * @param $defaultFolder
     * @return array
     */
    private function checkFileInOtherPlace($defaultFile, $defaultFolder)
    {
        $foundPath = array();
        foreach ($this->searchIn AS $searchPath) {
            if ($searchPath != $defaultFolder && file_exists($this->toMagentoRoot . $searchPath . DS . $defaultFile)) {
                $foundPath[] = $searchPath;
            }
        }
        return $foundPath;
    }

    /**
     * Return Defaultfolder from patched file
     *
     * @param $patchedFile
     * @return bool|mixed
     */
    private function getDefaultPart($patchedFile)
    {
        foreach ($this->defaultFolders AS $folder) {
            if (strpos($patchedFile, $folder) === 0) {
                return $folder;
            }
        }
        return false;
    }
}

// Let's start
$magePatchFileChecker = new magePatchFileChecker();
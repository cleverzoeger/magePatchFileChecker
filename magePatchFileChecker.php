<?php
/**
 * magePatchFileChecker
 * Version 1.0.0
 *
 * Copyright 2015 clever+zöger gmbh
 */

$toolVersion = "1.0.0";
$toolCopyright = "2015 clever+zöger gmbh";

if (!isset($argv[1])) {
    echo "clever+zöger ".basename(__FILE__).', Version '.$toolVersion.", (c) $toolCopyright \n";
	echo 'Usage: '.basename(__FILE__).' [FILE]'."\n\n";
	echo "Checks all inludes Patchfiles of FILE and checks if this exists inside community or local directories.\n";
	exit;
}

class securityCheck {
	private $toMagentoRoot = '../';

	private $startMarker = '__PATCHFILE_FOLLOWS__';
	
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
	
	private $filesToCheck = array();
	
	public function run($patchFile) {
		/* check path to magento root */
		if (!file_exists($this->toMagentoRoot.'index.php')) {
			echo "\nPath To Magento Root not correct\n";
			return false;
		}
		
		if (!defined('DS')) {
			define('DS','/');
		}
		
		echo "\n--------- START ---------\n";
		$fp = fopen($patchFile,'r');

		$found = false;
		while($line = fgets($fp)) {
			$line = str_replace(array("\n","\r"),'',$line);
			if ($line == $this->startMarker) {
				$found = true;
			}

			if ($found == true && (strpos($line,'---') === 0 || strpos($line,'+++') === 0)) {
				/* parse files */
				$line = str_replace(array('--- ','+++ '),'',$line);
				$this->filesToCheck[$line] = $line;
			}
		}

		fclose($fp);

		foreach ($this->filesToCheck AS $checkFile) {
			if ($checkFile == 'app/Mage.php') continue;

			$this->checkThisFile($checkFile);
		}
		echo "--------- END ---------\n";
	}
	private function checkThisFile($file) {
		$defaultPart = $this->getDefaultPart($file);
		if (!$defaultPart) {
			echo 'Part not found for: '.$file."\n";
			return false;
		}
		$filePart = trim(str_replace($defaultPart,'',$file),DS);
		if (strpos($defaultPart,'app/design/frontend') === 0) {
			$this->searchInDesign = array(
				'app/design/frontend',
			);
			$return = $this->checkFileInOtherPlaceDesign( $file, $filePart, $defaultPart );
		} elseif (strpos($defaultPart,'app/design/adminhtml') === 0) {
			$this->searchInDesign = array(
				'app/design/adminhtml',
			);
			$return = $this->checkFileInOtherPlaceDesign( $file, $filePart, $defaultPart );
		} else {
			$return = $this->checkFileInOtherPlace( $file, $filePart, $defaultPart );
		}
		
		if (is_array($return) && count($return) > 0) {
			/* found in other place */
			foreach ($return AS $foundIn) {
				echo 'Patched file found in: '.$foundIn.' File: '.$foundIn.DS.$filePart."\n";
			}
		}
	}

	private function checkFileInOtherPlaceDesign($file,$filePart,$defaultPart) {
		$foundPath = array();
		foreach ($this->searchInDesign AS $designFolder) {
			foreach (scandir($this->toMagentoRoot.$designFolder) AS $key => $folder) {
				if (in_array($folder,array(".","..")))
                    continue;

				if (is_dir($this->toMagentoRoot.$designFolder.DS.$folder)) {

					foreach ( scandir( $this->toMagentoRoot . $designFolder . DS . $folder ) AS $key2 => $folder2 ) {
						if ( in_array( $folder2, array( ".", ".." ) ) )
							continue;

						if ( is_dir( $this->toMagentoRoot . $designFolder . DS . $folder . DS . $folder2 ) ) {
							$folderToCheck = $designFolder . DS . $folder . DS . $folder2;

							if (in_array($folderToCheck,$this->ignoreDesignFolder))
                                continue;

							if ( $folderToCheck != $defaultPart && file_exists($this->toMagentoRoot.$folderToCheck.DS.$filePart)) {
								$foundPath[] = $folderToCheck;
							}
							
						}
					}
				}
			}
		}
		return $foundPath;
	}
	
	private function checkFileInOtherPlace($file,$filePart,$defaultPart) {
		$foundPath = array();
		foreach ($this->searchIn AS $searchPath) {
			if ($searchPath != $defaultPart && file_exists($this->toMagentoRoot.$searchPath.DS.$filePart)) {
				$foundPath[] = $searchPath;
			}
		}
		return $foundPath;
	}
	
	private function getDefaultPart($file) {
		foreach ($this->defaultFolders AS $folder) {
			if (strpos($file,$folder) === 0) {
				return $folder;
			}
		}
		return false;
	}
}

$patchFile = $argv[1];
$securityCheck = new securityCheck();
$securityCheck->run($patchFile);


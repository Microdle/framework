<?php 
namespace com\microdle\library\core;

/**
 * Microdle Framework - https://microdle.com/
 * Files manager.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.library.core
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class FileManager {
	/**
	 * File type.
	 * @var integer
	 */
	const FILE_TYPE = 1;
	
	/**
	 * Folder type.
	 * @var integer
	 */
	const FOLDER_TYPE = 2;
	
	/**
	 * File and folder type.
	 * @var integer
	 */
	const ALL_TYPE = 3;
	
	/**
	 * Copy a file or folder.
	 * @param string $source Source file or folder.
	 * @param string $target Destination file or folder.
	 * @return void
	 * @throws \com\microdle\exception\FolderCreationException
	 * @throws \com\microdle\exception\FileCopyException
	 */
	static public function copy(string $source, string $target): void {
		//Copy a file
		if(is_file($source)) {
			copy($source, $target);
		}
		
		//Copy a folder
		else {
			//Create the target folder if does not exist yet
			if(!is_dir($target) && !mkdir($target)) {
				throw new \com\microdle\exception\FolderCreationException('Create folder impossible: ' . $target);
			}
			
			//Copy folder content
			$d = dir($source);
			while(($entry = $d->read()) !== false) {
				if(!($entry == '.' || $entry == '..')) {
					if(is_file($src = $source . '/' . $entry) && !copy($src, $target . '/' . $entry)) {
						throw new \com\microdle\exception\FileCopyException('Copy file impossible from ' . $src . ' to ' . $target . '/' . $entry);
					}
					else {
						self::copy($src, $target . '/' . $entry);
					}
				}
			}
			$d->close();
		}
	}
	
	/**
	 * Delete files or folders and subolders.
	 * @param string $pattern File or folder pattern.
	 * @return void
	 * @throws \com\microdle\exception\FileRemovalException
	 * @throws \com\microdle\exception\FolderRemovalException
	 */
	static public function delete(string $pattern): void {
		$files = glob($pattern);
		if($files) {
			foreach($files as &$file) {
				if(is_file($file)) {
					if(!unlink($file)) {
						throw new \com\microdle\exception\FileRemovalException($file);
					}
				}
				else {
					self::delete($file . '/*');
					if(!rmdir($file)) {
						throw new \com\microdle\exception\FolderRemovalException($file);
					}
				}
			}
		}
	}
	
	/**
	 * Return all files of a folder. Ignore all files/folders beginning by ".".
	 * @param string $pattern Pattern.
	 * @param integer $type (optional) File type : self::FILE_TYPE = files (= 1), self::FOLDER_TYPE = folders (= 2), self::ALL_TYPE = files and folders (= 3).
	 * @param boolean $pathIncluded (optional) If true, return files/folders with complete path, otherwise file/folder names are returned.
	 * @return array Files or/and folders list.
	 */
	static public function getFiles(string $pattern, int $type = 1, bool $pathIncluded = false): array {
		$results = glob($pattern);
		if(!$results) {
			return $type === self::FILE_TYPE || $type === self::FOLDER_TYPE ? [] : ['files' => [], 'folders' => []];
		}
		
		$files = [];
		$folders = [];
		foreach($results as &$file) {
			if(is_file($file)) {
				$files[] = $file;
			}
			else {
				$folders[] = $file;
			}
		}
		
		if(!$pathIncluded) {
			if($type === self::FILE_TYPE || $type === self::ALL_TYPE) {
				preg_match_all('/[^\/]+(?=\*|$)/', implode('*', $files), $files);
				$files = $files[0];
			}
			if($type === self::FOLDER_TYPE || $type === self::ALL_TYPE) {
				preg_match_all('/[^\/]+(?=\*|$)/', implode('*', $folders), $folders);
				$folders = $folders[0];
			}
		}
		
		if($type === self::FILE_TYPE) {
			return $files;
		}
		if($type === self::FOLDER_TYPE) {
			return $folders;
		}
		return ['files' => $files, 'folders' => $folders];
	}
	
	/**
	 * Convert array variable to string.
	 * @param string $name Variable name.
	 * @param array $value Variable value.
	 * @return string Conversion array variable to string.
	 */
	static public function toPHPVariable(string $name, array $value): string {
		return '$' . $name . '=' . preg_replace(
			['/\s+=>\s+/', '/array\x20+\(/', '/\,[\n\r\t]\x20*\)/', '/[\n\r\t]\x20+/', '/\d+=>(\d+)/']
			,['=>', '[', ']', '', '$1']
			,var_export($value, true)).';';
	}
	
	/**
	 * Create (PHP) file contaning PHP variable.
	 * @param string $fileName Target file name.
	 * @param string $name Variable name.
	 * @param array $value Variable value.
	 * @return void
	 * @throws \com\microdle\exception\FileUnlockException
	 */
	static public function setPHPVariable(string $fileName, string $name, array $value): void {
		self::setContent($fileName, '<?php ' . self::toPHPVariable($name, $value) . '?>');
	}
	
	/**
	 * Pause process.
	 * @return boolean true if maximum loop is not reached, otherwise false.
	 */
	static public function pause(int &$loop, int $loopMax = 20, int $multiplierMin = 0, int $multiplierMax = 10, int $interval = 1000): bool {
		//Avoid n infinite loop
		if(++$loop > $loopMax) {
			return false;
		}
		
		//Pause duration of the process in micro second
		usleep(round(mt_rand($multiplierMin, $multiplierMax) * $interval));
		return true;
	}
	
	/**
	 * Return a file content.
	 * @param string $fileName File name with complte path.
	 * @param integer $loopMax Number of iteration of the pause interval to avoid infinite loop.
	 * @param integer $multiplierMin Multiplier minimum of the pause interval.
	 * @param integer $multiplierMax Multiplier maximum of the pause interval.
	 * @param integer $interval Pause interval in micro second.
	 * @return string File content if found, otherwise null.
	 * @throws \com\microdle\exception\FileUnlockException
	 */
	static public function getContent(string $fileName, int $loopMax = 20, int $multiplierMin = 0, int $multiplierMax = 10, int $interval = 1000): ?string {
		//Check file
		if(!is_file($fileName)) {
			return null;
		}
		
		//Get content file
		$loop = 0;
		while(false === ($content = file_get_contents($fileName))) {
			if(!self::pause($loop, $loopMax, $multiplierMin, $multiplierMax, $interval)) {
				throw new \com\microdle\exception\FileUnlockException($fileName);
			}
		}
		return $content;
	}
	
	/**
	 * Set a file content.
	 * @param string $fileName File name with complte path.
	 * @param integer $loopMax Number of iteration of the pause interval to avoid infinite loop.
	 * @param integer $multiplierMin Multiplier minimum of the pause interval.
	 * @param integer $multiplierMax Multiplier maximum of the pause interval.
	 * @param integer $interval Pause interval in micro second.
	 * @return integer Return the number of bytes written.
	 * @throws \com\microdle\exception\FileUnlockException
	 */
	static public function setContent(string $fileName, string $content = '', int $loopMax = 20, int $multiplierMin = 0, int $multiplierMax = 10, int $interval = 1000): int {
		$loop = 0;
		while(false === ($n = file_put_contents($fileName, $content))) {
			if(!self::pause($loop, $loopMax, $multiplierMin, $multiplierMax, $interval)) {
				throw new \com\microdle\exception\FileUnlockException($fileName);
			}
		}
		return $n;
	}
	
	/**
	 * Append a file conten.
	 * @param string $fileName File name with complte path.
	 * @param integer $loopMax Number of iteration of the pause interval to avoid infinite loop.
	 * @param integer $multiplierMin Multiplier minimum of the pause interval.
	 * @param integer $multiplierMax Multiplier maximum of the pause interval.
	 * @param integer $interval Pause interval in micro second.
	 * @return integer Return the number of bytes appended.
	 * @throws \com\microdle\exception\FileUnlockException
	 */
	static public function appendContent(string $fileName, string $content = '', int $loopMax = 20, int $multiplierMin = 0, int $multiplierMax = 10, int $interval = 1000): int {
		$loop = 0;
		while(false === ($n = file_put_contents($fileName, $content, FILE_APPEND))) {
			if(!self::pause($loop, $loopMax, $multiplierMin, $multiplierMax, $interval)) {
				throw new \com\microdle\exception\FileUnlockException($fileName);
			}
		}
		return $n;
	}
	
	/**
	 * Create folder.
	 * @param string $path Path.
	 * @return void
	 * @throws \com\microdle\exception\FolderCreationException
	 */
	static public function createFolder(string $path): void {
		//Case folder does not exists
		$path = rtrim($path, '/');
		if(!empty($path) && !is_dir($path)) {
			//Decompose path
			$elements = explode('/', $path);
			
			//Build root
			if($elements[0] === '') {
				$root = '/';
			}
			else {
				$root = $elements[0];
			}
			if(!is_dir($root)) {
				if(!mkdir($root)) {
					throw new \com\microdle\exception\FolderCreationException('Create folder impossible: ' . $root);
				}
			}
			
			//Loop on path elements
			array_shift($elements);
			foreach($elements as &$folderName) {
				if(!is_dir($root .= '/' . $folderName)) {
					if(!mkdir($root)) {
						throw new \com\microdle\exception\FolderCreationException('Create folder impossible: ' . $root);
					}
				}
			}
		}
	}	
}
?>
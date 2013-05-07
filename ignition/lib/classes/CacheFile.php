<?php
class CacheFile {
	public static function isCached($cacheName) {
		$cacheFile = sprintf('%s%s.cache', $GLOBALS['DIR_WS_CACHE'], $cacheName);
		
		return file_exists($cacheFile);
	}
	
	public static function load($cacheName) {
		if(self::isCached($cacheName)) {
			$cacheData = array();
			$cacheFile = sprintf('%s%s.cache', $GLOBALS['DIR_WS_CACHE'], $cacheName);
			
			$time = microtime(true);
			
			if($fh = fopen($cacheFile, 'r')) {
				while(!feof($fh)) {
					$cache = trim(fgets($fh));
					
					if(!empty($cache)) {
						$cacheData[] = $cache;
					}
				}

				fclose($fh);
			}
			
			Application::addTiming('Cache', $time, sprintf('Loading \'%s\' cache.', $cacheName));
			
			return $cacheData;
		}

		return false;
	}

	public static function save($cacheName, $cacheContent = '') {
		$cacheFile = sprintf('%s%s.cache', $GLOBALS['DIR_WS_CACHE'], $cacheName);
		
		$time = microtime(true);
		
		$fh = fopen($cacheFile, 'w');
		
		fwrite($fh, $cacheContent);
		fclose($fh);
		
		Application::addTiming('Cache', $time, sprintf('Saving \'%s\' cache.', $cacheName));
	}
	
	public static function modified($cacheName) {
		if(self::isCached($cacheName)) {
			$cacheFile = sprintf('%s%s.cache', $GLOBALS['DIR_WS_CACHE'], $cacheName);
			
			return filemtime($cacheFile);
		}
		
		return false;
	}
	
	public static function delete($cacheName) {
		$cacheFile = sprintf('%s%s.cache', $GLOBALS['DIR_WS_CACHE'], $cacheName);
		
		if(self::isCached($cacheName)) {
			$time = microtime(true);
			
			unlink($cacheFile);	
			
			Application::addTiming('Cache', $time, sprintf('Deleting \'%s\' cache.', $cacheName));
		}
	}

	public static function findFileTypes() {
		$types = array();

		foreach (glob($GLOBALS['DIR_WS_CACHE'] . "*.cache") as $file) {
			$filename = basename($file);
			$last = strrpos($filename, "_");
			$types[substr($filename, 0, $last)] = true;
		}

		return array_keys($types);
	}

	public static function expire($type, $days) {
		foreach (glob($GLOBALS['DIR_WS_CACHE'] . "*.cache") as $file) {
			$filename = basename($file, ".cache");
			$last = strrpos($filename, "_");
			$filetype = substr($filename, 0, $last);

			if ($filetype != $type) {
				continue;
			}

			if ((CacheFile::modified($filename) + (86400 * $days)) < time()) {
				CacheFile::delete($filename);
			}
		}
	}
}

<?php
class cacheSys {

	public static $debug = false;

	/**
     * Retrieves cached data based on the cache name and key requested. By default cache does not expire.
     *
     * Example Usage:
     * <code>
	 *
     * $htmlToDisplay = cacheSys::get('outputHTMLCategory', md5('filenameOutputKey'));
	 *
	 * if($htmlToDisplay===false) { //perform resource intensive logic here, eventually saving the output or data to the cache }
	 *
	 * echo $htmlToDisplay;
     * </code>
     *
     * @param string $cacheName any alpha-numeric string. used to categorize cache types.
     * @param string $key any alpha-numeric string unique within the cache category.
	 * @param int $maxAge how old a cache file is allowed to be (in seconds)
     *
     * @return string the contents of the cache category/key combo. FALSE if the cache is not found.
    */
	public static function get( $cacheName, $key, $maxAge=false ) {

		if(self::$debug) {
			error_log('Get output from cacheSys: '.$key);
		}

		$content = false;

		$fileName = AS_ROOT_PATH.'/cache/'.$cacheName.'.'.$key.'.cache';

		if($maxAge===false) {
			if(file_exists($fileName)) {
				$content = file_get_contents($fileName);
			}
		}
		else {
			//if the cached file exists and it was last updated within the last X number of seconds
			if(file_exists($fileName) && time() < filemtime($fileName)+$maxAge) {
				$content = file_get_contents($fileName);
			}
		}

		return $content;

	}



	/**
     * Stores content in the cache. uses the category and key variables to enable retreival
     *
     * Example Usage:
     * <code>
	 *
     * $htmlToDisplay = cacheSys::put('outputHTMLCategory', md5('filenameOutputKey'), 'content to store');
	 *
	 * if($htmlToDisplay===false) { //perform resource intensive logic here, eventually saving the output or data to the cache }
	 *
	 * echo $htmlToDisplay;
     * </code>
     *
     * @param string $cacheName any alpha-numeric string. used to categorize cache types.
     * @param string $key any alpha-numeric string unique within the cache category.
     * @param string $content any string to store in the cache
     *
     * @return boolean returns true if successful
    */
	public static function put( $cacheName, $key, $content ) {

		if(self::$debug) {
			error_log('Put output in cacheSys: '.$key);
		}

		$fileName = AS_ROOT_PATH.'/cache/'.$cacheName.'.'.$key.'.cache';

		$cached = fopen($fileName, 'w');

		if($cached!==false) {
			fwrite($cached, $content);
			fclose($cached);
		}

		return true;
	}


	/**
     * Deletes all cached copies of items within a specified category.
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteCachedCategory('outputHTMLCategory');
	 *
     * </code>
     *
     * @param string $category any alpha-numeric string used to categorize cache types.
     *
     * @return boolean returns true if cache stores were deleted, false if no cache stores exist
    */
	public static function deleteCachedCategory( $category ) {

		if(self::$debug) {
			error_log('Delete cached category in cacheSys: '.$category);
		}

		$return = false;

		$path = AS_ROOT_PATH."/cache/";

		if ($handle = opendir($path)) {

			while (false !== ($file = readdir($handle)) && strpos($category)!==false) {
				if ($file != "." && $file != "..") {
					unlink($path.$file);
					$return = true;
				}
			}

			closedir($handle);
		}

		return $return;

	}


	/**
     * Delete a specified cached copy within a specified category.
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteCachedItem('outputHTMLCategory', md5('filenameOutputKey'));
	 *
     * </code>
     *
     * @param string $category any alpha-numeric string used to categorize cache types.
     * @param string $key any alpha-numeric string used to uniquely identify the item to delete.
     *
     * @return boolean returns true if a cached copy was deleted, false if no cache existed
    */
	public static function deleteCachedItem( $category, $key ) {

		if(self::$debug) {
			error_log('Delete cached item in cacheSys: '.$category.'.'.$key);
		}

		$fileName = AS_ROOT_PATH.'/cache/'.$category.'.'.$key.'.cache';

		if(file_exists($fileName)) {
			unlink($fileName);
			return true;
		}
		else {
			return false;
		}

	}


	/**
     * Delete a cached files with a specfied string in the file name
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteCachedItems('outputHTMLCategory', 'stringInFilename');
	 *
     * </code>
     *
     * @param string $category any alpha-numeric string used to categorize cache types.
     * @param string $key any alpha-numeric string used to uniquely identify the item to delete.
     *
     * @return boolean returns true if a cached copy was deleted, false if no cache existed
    */
	public static function deleteCachedItems( $category, $key=null ) {

		if(self::$debug) {
			error_log('Delete cached item in cacheSys: '.$category.'.'.$key);
		}

		$cacheFiles = ls(AS_ROOT_PATH.'/cache/', true);

		$search = $key;
		if($key===null) {
			$search = $category;
		}

		foreach($cacheFiles as $file) {
			$strpos = strpos($file, $search);
			if($strpos!==false && $strpos>=0) {
				unlink(AS_ROOT_PATH.'/cache/'.$file);
			}
		}

		return true;

	}


	/**
     * Delete all cache copies stored by cacheSys
     *
     * Example Usage:
     * <code>
	 *
     * $cacheDeleted = cacheSys::deleteAllCache();
	 *
     * </code>
     *
     * @return boolean returns true if cached copies were deleted, false if no cache existed
    */
	public static function deleteAllCache( ) {

		if(self::$debug) {
			error_log('Delete all cache in cacheSys.');
		}

		$return = false;

		$path = AS_ROOT_PATH.'/cache/';

		if ($handle = opendir($path)) {

			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					unlink($path.$file);
					$return = true;
				}
			}

			closedir($handle);
		}

		return $return;

	}


	/**
     * Sanitize the cache file key
     *
     * Example Usage:
     * <code>
	 *
     * $cacheKey = cacheSys::sanitizeKey( $dirtyString );
	 * $body	  = cacheSys::get( 'output', $cacheKey );
	 *
     * </code>
     *
     * @return string returns sanitized string
    */
	public static function sanitizeKey( $dirtyString ) {

		$a = str_replace('/', '~', trim($dirtyString,'/'));

		$b = str_replace('?mobileok=true', '', $a);

		$c = str_replace('?', '@', $b);

		return $c;

	}


}
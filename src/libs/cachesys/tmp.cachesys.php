<?php
class cacheSysTmp {

	private $tmpCache;

	/**
     * Retrieves temp cached data based on the cache name and key requested
     *
     * Example Usage:
     * <code>
	 *
	 * $cacheSysTmp = new cacheSysTmp();
     * $employee = $cacheSysTmp->get('getEmployee', '712');
	 *
	 * if($employee===false) { //perform resource intensive logic here, eventually saving the output or data to the cache }
	 *
     * </code>
     *
     * @param string $cacheName any alpha-numeric string. used to categorize cache types.
     * @param string $key alpha-numeric string unique within the cache category.
     *
     * @return the contents of the cache category/key combo. FALSE if the cache is not found.
    */
	public function get( $cacheName, $key ) {

		$content = false;

		if(isset($this->tmpCache[ $cacheName ]) && isset($this->tmpCache[ $cacheName ][ $key ])) {
			$content = $this->tmpCache[ $cacheName ][ $key ];
		}

		return $content;

	}



	/**
     * Stores content in the temp cache. uses the category and key variables to enable retreival
     *
     * Example Usage:
     * <code>
	 *
	 * $cacheSysTmp = new cacheSysTmp();
     * $employee = $cacheSysTmp->put('getEmployee', '712', 'content to store');
	 *
	 * if($employee===false) { //perform resource intensive logic here, eventually saving the output or data to the cache }
	 *
     * </code>
     *
     * @param string $cacheName any alpha-numeric string. used to categorize cache types.
     * @param string $key any alpha-numeric string unique within the cache category.
     * @param variable $content any string to store in the cache
     *
     * @return boolean returns true if successful
    */
	public function put( $cacheName, $key, $content ) {

		if(!isset($this->tmpCache[ $cacheName ])) {
			$this->tmpCache[ $cacheName ] = [];
		}

		$this->tmpCache[ $cacheName ][ $key ] = $content;

		return true;
	}



}
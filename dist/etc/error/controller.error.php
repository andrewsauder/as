<?php
class errorController {

	private $model;
	private $params;

	function __construct( $params=array('error'=>'') ) {
		$this->params = $params;
	}

	public function view() {
		if(!isset($this->params['error'])) {
			$this->params['error'] = '';
		}
		if($this->params['error']=='403 Forbidden') {
			$file = 'view.403.php';
		}
		elseif($this->params['error']=='404 Not Found') {
			$file = 'view.404.php';
		}
		else {
			$file = 'view.generic.php';
		}

		return array('view'=>'views/'.$file);
	}

	public function getPageTitle() {
		return 'Error';
	}

	public function getPageKeywords() {
		return '';
	}

	public function includeHeader() {
		if(!isset($_SERVER['HTTP_AJAX'])) {
			return true;
		}
		else {
			return false;
		}

	}

	public function includeFooter() {
		if(!isset($_SERVER['HTTP_AJAX'])) {
			return true;
		}
		else {
			return false;
		}
	}

}
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
			$file = 'view.403';
		}
		elseif($this->params['error']=='404 Not Found') {
			$file = 'view.404';
		}
		else {
			$file = 'view.generic';
		}

		ob_start();

			include(view(AS__PATH.'/etc/error/views/'.$file.'.php'));
			$body = ob_get_contents();

		ob_end_clean();

		return $body;
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
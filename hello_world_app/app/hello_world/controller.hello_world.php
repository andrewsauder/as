<?php
class hello_worldController {

	private $params, $URLParams, $FormParams, $m, $model;

	function __construct( $params, $URLParams=array(), $FormParams=array(), $m=false ) {
		$this->params 		= $params;
		$this->URLParams 	= $URLParams;
		$this->FormParams	= $FormParams;
		$this->m = $m;
		$this->model = new hello_worldModel();
	}

	public function view() {

		$viewVars = array();

		if($this->URLParams['R2']=='special') {

			$viewVars['specialText'] = 'Special view inside same controller :)';
			$file = 'views/view.special.php';

		}
		else {

			$textArr = $this->model->getHelloText();
			$viewVars['text'] = $textArr['text'];
			$file = 'views/view.basic.php';

		}

		return array('view'=>$file, 'vars'=>$viewVars);

	}

	public function getPageScripts() {
		$scripts = array();
		return $scripts;
	}

	public function getPageCSS() {
		$files = array();
		return $files;
	}

	public function getPageTitle() {
		return 'Hello';
	}

	public function getPageKeywords() {
		return '';
	}

	public function getPageDescription() {
		return '';
	}

	public function includeHeader() {
		return true;
	}

	public function includeFooter() {
		return true;
	}

	public function allowCaching() {
		return false;
	}
}
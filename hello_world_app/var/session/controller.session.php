<?php
include_once('model.session.php');
class sessionController {

	private static $model;
	private static $params;

	function __construct( $params = array() ) {

		self::$model = new sessionModel();

	}

	public static function startApp() {

		if(!isset(self::$model)) {
			self::$model = new sessionModel();
		}

		self::setTheme();

	}

	public static function pageLoad() {

		if(!isset(self::$model)) {
			self::$model = new sessionModel();
		}

	}

	private static function setTheme() {
		$_SESSION[AS_APP]['theme'] = self::getTheme();
	}

	public static function getTheme() {

		$dirName = 'hello';
		$viewsUnformatted = ls(AS_WWW_PATH.'/theme/'.$dirName.'/templates/');
		$views = array();

		foreach($viewsUnformatted as $view) {
			if($view['type']=='dir' && is_array($view['files']) && count($view['files'])>0) {
				$views[ $view['name'] ] = array();
				foreach($view['files'] as $f) {
					$views[ $view['name'] ][] = $f['name'];
				}
			}
		}

		return array(
			'dirname'=>$dirName,
			'views'=>$views
		);
	}

}

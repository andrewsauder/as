<?php
class {module}Controller {

	private $model;

	function __construct() {
		$this->model = new {module}Model();
	}

	public function view() {
		
		//associative array keys of $vars will be mapped as local variables in the view
		//ex $vars['employeeID'] can be accessed as $employeeID in the view
		$vars = [];
		
		//do work
		
		//to return a view to include in the template uncomment the next line
		//return ['view'=>'views/view.{module}.php', 'vars'=>$vars];
		
		//to return json to the browser uncomment the next line
		//return ['data'=>$vars];

	}

	
	//html page title
	public function getPageTitle() {
		return '{module}';
	}

	
	//whether to include the html header or not
	public function includeHeader() {
		if(isset($_SERVER['HTTP_AJAX'])) {
			return false;
		}
		return true;
	}

	
	//whether or not to include the html footer or not
	public function includeFooter() {
		if(isset($_SERVER['HTTP_AJAX'])) {
			return false;
		}
		return true;
	}

	
	//whether or not to allow the output of view() to be cached
	public function allowCaching() {
		return false;
	}

	/*
	 * Available top level functions you can include in the controller
	 *
	 *	getPageCSS()
	 *		return a list of style sheets to include on the page
	 *
	 * getPageScripts()
	 *		return a list of javascript files to include on the page
	  *
	 * getRequireJSModules()
	 *		return a list of require js modules to load on the page (requires your template to include require.js)
	*/
	
}
<?php


namespace framework\helpers\models;


class routeHandler {


	public string $class          = '';

	public string $method         = '';

	public bool   $authentication = false;


	public function __construct( string $class, string $method, bool $authentication = false ) {

		$this->class          = $class;
		$this->method         = $method;
		$this->authentication = $authentication;

	}

}
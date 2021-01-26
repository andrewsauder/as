<?php


namespace framework\models;


use JetBrains\PhpStorm\Pure;


class route {

	/** @var string|string[] HTTP Method for route */
	public string|array $httpMethod = '';

	/**
	 * @var string Example: "/organization"
	 * @see https://github.com/nikic/FastRoute
	 */
	public string $route  = '';

	/** @var string Class to instantiate */
	public string $class  = '';

	/** @var string Method inside $class to call */
	public string $method = '';

	/** @var bool Authentication is required */
	public bool $authentication = false;


	#[Pure]
	public function __construct( string|array $httpMethod = '', string $route = '', string $class = '', string $method = '', bool $authentication = false ) {

		$this->httpMethod     = $httpMethod;
		$this->route          = $route;
		$this->class          = $class;
		$this->method         = $method;
		$this->authentication = $authentication;

	}

}
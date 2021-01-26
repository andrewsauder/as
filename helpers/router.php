<?php


namespace framework\helpers;


abstract class router {

	public function __construct() {

	}


	/**
	 * @return string
	 * @throws \framework\exceptions\routeException
	 */
	protected function executeRouteAndRender() : string {

		$routes = $this->getRoutes();

		$dispatcher = \FastRoute\simpleDispatcher( function( \FastRoute\RouteCollector $r ) use ( $routes ) {

			foreach( $routes as $route ) {
				$r->addRoute( $route->httpMethod, $route->route, new \framework\models\routeHandler( $route->class, $route->method, $route->authentication ) );
			}
		} );


		// Fetch method and URI from somewhere
		$httpMethod = $_SERVER[ 'REQUEST_METHOD' ];
		$uri        = $_SERVER[ 'REQUEST_URI' ];

		// Strip query string (?foo=bar) and decode URI
		if( false !== $pos = strpos( $uri, '?' ) ) {
			$uri = substr( $uri, 0, $pos );
		}
		$uri = rawurldecode( $uri );

		$routeInfo = $dispatcher->dispatch( $httpMethod, $uri );
		switch( $routeInfo[ 0 ] ) {
			case \FastRoute\Dispatcher::NOT_FOUND:
				// ... 404 Not Found
				http_response_code(404);
				throw new \framework\exceptions\routeException ( 'Not Found', 404);
				break;
			case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$allowedMethods = $routeInfo[ 1 ];
				// ... 405 Method Not Allowed
				http_response_code(405);
				throw new \framework\exceptions\routeException ( 'Method Not Allowed', 405);
				break;
			case \FastRoute\Dispatcher::FOUND:

				$handler = $routeInfo[ 1 ];
				$vars    = $routeInfo[ 2 ];

				try {
					//authenticate
					//TODO: expand authentication to handle roles?
					if($handler->authentication && !$this->authentication()) {
						http_response_code(401);
						throw new \framework\exceptions\routeException ( 'Authentication failed', 401);
					}

					//return rendered
					return $this->render( $handler, $vars );
				}
				catch( \framework\exceptions\controllerException | \framework\exceptions\routeException $e ) {
					if($e->getCode()>200 && $e->getCode()<600) {
						http_response_code($e->getCode());
					}
					else {
						http_response_code(500);
					}
					throw new \framework\exceptions\routeException ( $e->getMessage(), $e->getCode(), $e);
				}
				catch( \Exception|\Error|\ErrorException $e ) {
					\error_log($e);
					http_response_code(500);
					throw new \framework\exceptions\routeException('Unhandled major system error. Contact support.', 500, $e);
				}
				break;
		}

		http_response_code(500);
		throw new \framework\exceptions\routeException( 'Routing failed', 500);

	}


	/**
	 * @param  \framework\models\routeHandler  $handler
	 * @param  array                           $vars
	 *
	 * @return string
	 */
	private function render( \framework\models\routeHandler $handler, array $vars ) : string {

		//exceptions raised here are all logic exceptions: a route is defined that points to a class or method that does not exist
		try {

			$class = new \ReflectionClass( $handler->class );

			$method = $class->getMethod( $handler->method );

			if($method->isStatic()) {
				$instance = $class->newInstanceWithoutConstructor();
			}
			else {
				$instance = $class->newInstance();
			}

			$result = $method->invokeArgs( $instance, $vars );

		}
		catch( \ReflectionException $e ) {
			error_log($e);
			throw new \Error($e->getMessage(), 0, $e);
		}

		if( isset( $result[ 'data' ] ) ) {
			header( 'Content-Type:application/json' );
			return json_encode( $result[ 'data' ] );
		}

		//LOGIC ERROR: view and vars for templated views does not exist
		throw new \Error('View and vars not implemented');

	}


	/**
	 * Defines URL routes and returns them as an array
	 *
	 * @return \framework\models\route[]
	 */
	abstract function getRoutes() : array;


	/**
	 * Performs actual route by calling executeRouteAndRender() and handles exceptions for displaying errors
	 *
	 * Example of what should be inside route method:
	 * <code>
	 * try {
	 *     return $this->executeRouteAndRender();
	 * }
	 * catch( \framework\exceptions\routeException  $e ) {
	 *     \app\services\api::sendError( $e->getCode(), $e->getMessage() );
	 * }
	 * </code>
	 *
	 * @return string
	 */
	abstract function route() : string;


	/**
	 * Checks if user is authenticated and returns true if yes, or false if not
	 *
	 * @return bool
	 * @throws \framework\exceptions\routeException
	 */
	abstract function authentication() : bool;
}
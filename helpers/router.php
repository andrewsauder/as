<?php


namespace framework\helpers;


class router {

	public function __construct() {

	}


	/**
	 * @return string
	 * @throws \Exception
	 */
	public function route() : string {

		$appRouter = new \app\router\router();

		$dispatcher = \FastRoute\simpleDispatcher( function( \FastRoute\RouteCollector $r ) use ( $appRouter ) {

			foreach( $appRouter->getRoutes() as $route ) {
				$r->addRoute( $route->httpMethod, $route->route, new \framework\helpers\models\routeHandler( $route->class, $route->method, $route->authentication ) );
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
				//TODO: add error routing
				break;
			case \FastRoute\Dispatcher::METHOD_NOT_ALLOWED:
				$allowedMethods = $routeInfo[ 1 ];
				// ... 405 Method Not Allowed
				//TODO: add error routing
				break;
			case \FastRoute\Dispatcher::FOUND:

				$handler = $routeInfo[ 1 ];
				$vars    = $routeInfo[ 2 ];

				try {
					//authenticate
					if($handler->authentication) {
						//TODO: expand to handle roles?
						if(!$appRouter->authentication()) {
							throw new \Exception('Authentication failed', 401);
						}
					}

					//return rendered
					return $this->render( $handler, $vars );
				}
				catch( \framework\helpers\models\routeException $e ) {
					//TODO: render error
					elog($e->getMessage());
					http_response_code( $e->getCode() );
					return $e->getMessage();
				}
				catch( \Exception $e ) {
					//TODO: render error
					elog($e->getMessage());
					return $e->getMessage();
				}

				break;
		}

		return '';

	}


	/**
	 * @param  \framework\helpers\models\routeHandler  $handler
	 * @param  array                                   $vars
	 *
	 * @return string
	 * @throws \Exception
	 * @throws \ReflectionException
	 */
	private function render( \framework\helpers\models\routeHandler $handler, array $vars ) : string {

		//get class via reflection
		try {
			$class = new \ReflectionClass( $handler->class );
		}
		catch( \ReflectionException $e ) {
			throw new \Exception('Class '.$handler->class.' does not exist: '.$e->getMessage(), 404, $e );
		}

		//get method via reflection
		try {
			$method = $class->getMethod( $handler->method );
		}
		catch( \ReflectionException $e ) {
			throw new \Exception('Class '.$handler->class.' does not contain method '.$handler->method.': '.$e->getMessage(), 404, $e );
		}

		//instantiate class
		if($method->isStatic()) {
			$instance = $class->newInstanceWithoutConstructor();
		}
		else {
			$instance = $class->newInstance();
		}

		try {
			$result = $method->invokeArgs( $instance, $vars );
		}
		catch( \ReflectionException $e ) {
				throw new \Exception('Invalid parameters provided for '.$handler->class.'->'.$handler->method.': '.$e->getMessage(), 400, $e );
			}

		if( isset( $result[ 'data' ] ) ) {
			header( 'Content-Type:application/json' );

			return json_encode( $result[ 'data' ] );
		}

		throw new \Exception('View and vars not implemented yet', 500);

	}

}
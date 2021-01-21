<?php


namespace framework\helpers\models;


class routeException extends \Exception {

	// Redefine the exception so message isn't optional
	public function __construct( $message, $code = 0, \Exception $previous = null ) {

		// make sure everything is assigned properly
		parent::__construct( $message, $code, $previous );
	}


	// custom string representation of object
	public function __toString() : string {

		return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
	}


}
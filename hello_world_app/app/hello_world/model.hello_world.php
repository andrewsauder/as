<?php
class hello_worldModel {

	private $db;

	function __construct() {
		$this->db = new db();
	}

	public function getHelloText( $params=array() ) {
		//TODO: add $this->db->read($query) or $this->db->readOneRow($query) database call here and return data
		return array('text'=>'Hello world');
	}


}
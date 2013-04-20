<?php
class sessionModel {

	private $db;

	function __construct() {
		$this->db = new db();
	}

}
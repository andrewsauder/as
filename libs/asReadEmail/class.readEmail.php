<?php

class asReadEmail {

	// imap server connection
	public $conn;

	// inbox storage and inbox message count
	private $inbox;
	private $msg_cnt;

	// email login credentials
	private $server = '';
	private $user   = '';
	private $pass   = '';
	private $port   = 143; // adjust according to server settings

	// connect to the server and get the inbox emails
	function __construct( $params ) {

		$this->server	= $params['server'];
		$this->user		= $params['user'];
		$this->pass		= $params['pass'];
		$this->port		= isset($params['port']) ? $params['port'] : 143;

		$this->connect();
		//$this->inbox();
	}

	// close the server connection
	function close() {
		$this->inbox = array();
		$this->msg_cnt = 0;

		imap_close($this->conn);
	}

	// open the server connection
	// the imap_open function parameters will need to be changed for the particular server
	// these are laid out to connect to a Dreamhost IMAP server
	function connect() {
		$this->conn = imap_open('{'.$this->server.'}', $this->user, $this->pass);
	}

	// move the message to a new folder
	function move($msg_index, $folder='INBOX.Processed') {
		// move on server
		imap_mail_move($this->conn, $msg_index, $folder);
		imap_expunge($this->conn);

		// re-read the inbox
		$this->inbox();
	}

	function delete( $msgId ) {

		imap_delete( $this->conn, $msgId );

	}

	// get a specific message (1 = first email, 2 = second email, etc.)
	function get($msg_index=NULL) {

		if($this->inbox==null) {
			$this->inbox();
		}

		if (count($this->inbox) <= 0) {
			return array();
		}
		elseif ( ! is_null($msg_index) && isset($this->inbox[$msg_index])) {
			return $this->inbox[$msg_index];
		}

		return $this->inbox;
	}

	function getUnread() {
		$messageIds = imap_search($this->conn, 'UNSEEN');

		$messages = [];

		foreach($messageIds as $messageId) {

			$rawBody = $this->getBody($this->conn, $messageId);
			$bodyParts = $this->getBodyParts($rawBody);

			$messages[] = array(
				'index'     => $messageId,
				'header'    => imap_headerinfo($this->conn, $messageId),
				'rawbody'   => $rawBody,
				'body'		=> $bodyParts,
				'structure' => imap_fetchstructure($this->conn, $messageId)
			);
		}

		return $messages;

	}

	// read the inbox
	function inbox() {
		$this->msg_cnt = imap_num_msg($this->conn);

		$in = array();
		for($i = $this->msg_cnt; $i>0; --$i) {

			$rawBody = $this->getBody($this->conn, $i);
			$bodyParts = $this->getBodyParts($rawBody);

			$in[] = array(
				'index'     => $i,
				'header'    => imap_headerinfo($this->conn, $i),
				'rawbody'   => $rawBody,
				'body'		=> $bodyParts,
				'structure' => imap_fetchstructure($this->conn, $i)
			);

		}

		$this->inbox = $in;
	}


	function getBodyParts( $htmlStr ) {

		set_error_handler("asReadEmailErrorHandler", E_WARNING|E_NOTICE);

		$html = DOMDocument::loadHTML($htmlStr);

		$body = $html->getElementsByTagName("body");

		restore_error_handler();

		$rsp = '';

		foreach($body as $b) {
			$rsp .= $this->DOMinnerHTML($b);
		}

		$rsp = trim($rsp);

		return $rsp;

	}

	function getBody($imap, $uid)
	{
		$body = $this->get_part($imap, $uid, "TEXT/HTML");
		// if HTML body is empty, try getting text body
		if ($body == "") {
			$body = $this->get_part($imap, $uid, "TEXT/PLAIN");
		}

		return $body;
	}

	function DOMinnerHTML($element)
	{
		$innerHTML = "";
		$children = $element->childNodes;
		foreach ($children as $child)
		{
			$tmp_dom = new DOMDocument();
			$tmp_dom->appendChild($tmp_dom->importNode($child, true));
			$innerHTML.=trim($tmp_dom->saveHTML());
		}
		return $innerHTML;
	}

	function get_part($imap, $uid, $mimetype, $structure = false, $partNumber = false)
	{
		if (!$structure) {
			$structure = imap_fetchstructure($imap, $uid);
		}
		if ($structure) {
			if ($mimetype == $this->get_mime_type($structure)) {
				if (!$partNumber) {
					$partNumber = 1;
				}
				$text = imap_fetchbody($imap, $uid, $partNumber);
				switch ($structure->encoding) {
					case 3:
						return imap_base64($text);
					case 4:
						return imap_qprint($text);
					default:
						return $text;
				}
			}

			// multipart
			if ($structure->type == 1) {
				foreach ($structure->parts as $index => $subStruct) {
					$prefix = "";
					if ($partNumber) {
						$prefix = $partNumber . ".";
					}
					$data = $this->get_part($imap, $uid, $mimetype, $subStruct, $prefix . ($index + 1));
					if ($data) {
						return $data;
					}
				}
			}
		}
		return false;
	}


	function get_mime_type($structure)
	{
		$primaryMimetype = ["TEXT", "MULTIPART", "MESSAGE", "APPLICATION", "AUDIO", "IMAGE", "VIDEO", "OTHER"];

		if ($structure->subtype) {
			return $primaryMimetype[(int)$structure->type] . "/" . $structure->subtype;
		}
		return "TEXT/PLAIN";
	}

}

function asReadEmailErrorHandler() {

}
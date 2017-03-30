<?php
class ldap {

	private $config = [
		'domain'	=>	[
			'name'	=>	'',
			'pieces'=>	[],
			'piecesStr'=>''
		],
		'server'	=>	'',
		'user'		=>	'',
		'password'	=>	'',
		'port'		=>	3268
	];
	private $connection = null;

	function __construct( $params ) {

		//config
			//domain
				$this->config['domain']['name']		= $params['config']['domain'];
				$this->config['domain']['pieces']	= explode('.', $params['config']['domain']);
				$ps = [];
				foreach($this->config['domain']['pieces'] as $p) {
					$ps[] = 'DC='.$p;
				}
				$this->config['domain']['piecesStr'] = implode(',', $ps);

			//server and user/pass
				$this->config['server']				= $params['config']['server'];
				$this->config['user']				= $params['config']['user'];
				$this->config['password']			= $params['config']['password'];

			//port if not default
				if(isset($params['config']['port'])) {
					$this->config['port']	= $params['config']['port'];
				}

	}

	private function connect() {

		if($this->connection!==null) {
			return $this->connection;
		}

		$this->connection = ldap_connect($this->config['server']);

		if($this->connection===false) {
			error_log('LDAP: failed to connect using connection string: '.$this->config['server']);
			return false;
		}

		ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, 3);
		ldap_set_option($this->connection, LDAP_OPT_REFERRALS, 0);

		$successful = ldap_bind($this->connection, $this->config['user'], $this->config['password']);

		if(!$successful) {
			$errorNo = ldap_errno($this->connection);
			error_log('LDAP: failed to bind. Error '.$errorNo.'. Using username: '.$this->config['user'].' and password: '.$this->config['password'].' on connection string: '.$connectionString);
			return false;
		}

		return true;

	}

	public function search( $q='', $f=[], $preBN='' ) {

		$this->connect();

		if(!is_array($f)) {
			$f = json_decode( $f, true );
		}

		$result = ldap_search($this->connection, $preBN.$this->config['domain']['piecesStr'], $q, $f);

		if($result===false) {
			error_log("Error in search query: ".ldap_error($this->connection));
		}
		//$result = ldap_search($this->connection, $this->config['domain']['piecesStr'], "ou=*", json_decode('["ou"]',1)) or error_log("Error in search query: ".ldap_error($this->connection));

		$data = ldap_get_entries($this->connection, $result);

		$fin = [];

		if($data['count']>0) {
			foreach($data as $index=>$item) {

				if(!is_numeric($index)) {
					continue;
				}

				$finIndex = count($fin);
				$fin[$finIndex] = [];

				foreach($item as $key=>$value) {
					if(is_numeric($key) || $key=='count') {
						continue;
					}

					if(!isset($value['count'])) {
						$fin[ $finIndex ][ $key ] = $value;
					}
					elseif($value['count']==0) {
						$fin[ $finIndex ][ $key ] = null;
					}
					elseif($value['count']==1) {
						$fin[ $finIndex ][ $key ] = $value[0];
					}
					else {
						$fin[ $finIndex ][ $key ] = [];
						foreach($value as $vi=>$vv) {
							if(!is_numeric($vi)) {
								continue;
							}
							$fin[ $finIndex ][ $key ][] = $vv;
						}
					}

				}

			}
		}

		return $fin;

	}

	public function singleSearch( $q='', $f='', $preBN='') {

		$this->connect();

		$sr = ldap_list($this->connection, $preBN.$this->config['domain']['piecesStr'], $q, [$f]) or error_log($preBN.$this->config['domain']['piecesStr']);

		if($sr===false) {
			return false;
		}

		$info = ldap_get_entries($this->connection, $sr);

		$fin = [];

		for ($i=0; $i < $info["count"]; $i++) {
			$fin[] = $info[$i][$f][0];
		}

		return $fin;
	}

	public function flatSearch( $q='', $f=[], $preBN='') {

		$this->connect();

		$sr = ldap_list($this->connection, $preBN.$this->config['domain']['piecesStr'], $q, $f) or error_log($preBN.$this->config['domain']['piecesStr']);

		if($sr===false) {
			return false;
		}

		$info = ldap_get_entries($this->connection, $sr);

		$fin = [];

		for ($i=0; $i < $info["count"]; $i++) {
			$ffin = [];
			foreach($f as $k) {
				if(!isset($info[$i][$k]['count'])) {
					$ffin[ $k ] = $info[$i][$k];
				}
				elseif($info[$i][$k]['count']==0) {
					$ffin[ $k ] = null;
				}
				elseif($info[$i][$k]['count']==1) {
					$ffin[ $k ] = $info[$i][$k][0];
				}
				else {
					$ffin[ $k ] = [];
					foreach($info[$i][$k] as $vi=>$vv) {
						if(!is_numeric($vi)) {
							continue;
						}
						$ffin[ $k ][] = $vv;
					}
				}
				$fin[] = $ffin;
			}
		}

		return $fin;
	}

	public function modify( $dn, $new ) {

		$this->connect();

		$modified = ldap_modify($this->connection, $dn, $new);

		return $modified;

	}

	public function delete( $dn, $new ) {

		$this->connect();

		$modified = ldap_mod_del($this->connection, $dn, $new);

		return $modified;

	}

	public function add( $dn, $record ) {

		$add = ldap_add($this->connection, $dn.',OU='.$_SESSION['AS']['config']['settings']['ldap']['top_level_user_ou'].','.$this->config['domain']['piecesStr'], $record);

		return $add;

	}

}
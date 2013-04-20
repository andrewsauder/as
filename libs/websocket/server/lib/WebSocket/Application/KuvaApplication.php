<?php

namespace WebSocket\Application;


/**
 * Websocket-Server KUVA interactive application.
 *
 * @author Andrew Sauder
 */
class KuvaApplication extends Application {

	private $_clients = array();
	private $registeredClients = array();


	public function onConnect($client) {
		$id = $client->getClientId();
		$this->_clients[$id] = $client;

		$this->kuvaData($client, ['msg'=>'Successfull connection.'], 'connected');
	}


	public function onDisconnect($client) {
		$id = $client->getClientId();
		unset($this->_clients[$id]);
		unset($this->registeredClients[$id]);
	}


	public function onData($encodedData, $client) {
		$decodedData = $this->_decodeData($encodedData);
		$d = $decodedData;
		if($decodedData === false || $d['action']=='') {
			$this->kuvaData($client, ['msg'=>'Invalid reception of empty payload.'], 'error');
		}

		$clientID = $client->getClientId();

		if($d['action'] == 'register') {
			$this->registeredClients[$clientID] = [
				'type'=>$d['data']['type'],
				'kuvaID'=>$d['data']['kuvaID'],
				'wsID'=>$clientID,
				'meta'=>[]
			];
			$this->kuvaData($client, $this->registeredClients[$clientID], 'registered');
			return true;
		}

		if(!isset($this->registeredClients[$clientID])) {
			error_log('KUVA - not registered');
			$this->kuvaData($client, ['msg'=>'You must register before sending other requests.'], 'error');
			return false;
		}

		if($d['action'] == 'pushNewOrder') {
			$order = $d['data']['order'];

			//TODO: determine which fulfillment station to send it to.
			foreach($this->_clients as $connectedClientID=>$connectedClient) {
				if($this->registeredClients[$connectedClientID]['type']=='fulfillment') {
					$clientForOrder = $connectedClient;
				}
			}

			//if no client is available
			if(isset($clientForOrder)) {
				$this->kuvaData($clientForOrder, $order, 'pushNewOrder');
			}
			else {
				//if no fulfillment stations are available, order cannot be placed.
				$order['msg'] = 'No fulfillment stations are available.';
				$this->kuvaData($client, $order, 'rejectOrder');
			}

			return true;
		}

	}


	private function kuvaData($client, $data = [], $action = 'data') {
		$fin = array(
			'kuva'=>array(
				'action'=>$action,
				'data'=>(array) $data,
				'client_id'=>$client->getClientId(),
				'ts'=>date('Y-m-d H:i:s')
			)
		);

		$payload = json_encode($fin);

		$client->send($payload);
	}

}
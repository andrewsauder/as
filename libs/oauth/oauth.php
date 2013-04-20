<?php

class oauth {

	private $params;

	function __construct( $params = array() ) {
		$this->params = $params;
	}

	public function login() {

		if( $this->params[1]=='google' ) {
			$user = $this->google();
		}
		elseif( $this->params[1]=='facebook' ) {
			$user = $this->facebook();
		}

		unset($_SESSION['oauth']['login']);

		return $user;

	}

	private function google() {

		$GoogleApiConfig = array(
			// The application_name is included in the User-Agent HTTP header.
			'application_name' => 'Insight',

			// OAuth2 Settings, you can get these keys at https://code.google.com/apis/console
			'oauth2_client_id' => '982458295252.apps.googleusercontent.com',
			'oauth2_client_secret' => 'T5Gp4sZlQ_9CjkWAch-ovAHv',
			'oauth2_redirect_uri' => 'http://localhost:8082/'.$_SESSION['org']['sid'].'/authentication/oauth/google/callback',

		);

		include_once 'google/apiClient.php';
		include_once 'google/contrib/apiOauth2Service.php';

		$user = array();

		$client = new apiClient();
		$client->setApplicationName("Google UserInfo PHP Starter Application");
		$oauth2 = new apiOauth2Service($client);

		if (isset($_GET['code'])) {
			$client->authenticate();
			$_SESSION['token'] = $client->getAccessToken();
			$user = $oauth2->userinfo->get();
			return $user;
		}

		if (isset($_SESSION['token'])) {
			$client->setAccessToken($_SESSION['token']);
		}

		if (isset($_REQUEST['logout'])) {
			unset($_SESSION['token']);
			$client->revokeToken();
		}

		if ($client->getAccessToken()) {
			$user = $oauth2->userinfo->get();
			//$_SESSION["userprofile"] = $user;

			// The access token may have been updated lazily.
			$_SESSION['token'] = $client->getAccessToken();

			// return after login
			return $user;

		} else {
		  $authUrl = $client->createAuthUrl();
		}

		if(isset($authUrl)) {
			header("Location: $authUrl");
			exit;
		}

	}

	private function facebook() {

		include_once('facebook/facebook.php');

		$facebook = new Facebook(array(
			'appId'  => '512569998760634',
			'secret' => '268d13bb346c6c76ddc59871e8813cf5',
		));

		// Get User ID
		$user = $facebook->getUser();

		if($_SESSION['cc']>3) { pie($_SESSION['cc']); }
		if ($user) {
			try {
				// Proceed knowing you have a logged in user who's authenticated.
				$user_profile = $facebook->api('/me');
			} catch (FacebookApiException $e) {
				error_log($e);
				$user = NULL;
			}
		}

		// Login or logout url will be needed depending on current user state.
		if ($user) {
			$user_profile = $user_profile;
			return $user_profile;
		}
		elseif(!$user) {
			$_SESSION['cc'] = $_SESSION['cc']+1;
			$loginUrl = $facebook->getLoginUrl(array('scope'=>'email'));
			pie($loginUrl);
			header("Location: $loginUrl");
			exit;
		}

	}



}
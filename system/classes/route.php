<?php

class Route {

	public $route;

	public $request;

	function __construct( $sekretaer ) {

		$request_string = $_SERVER['REQUEST_URI'];
		$request_string = preg_replace( '/^'.preg_quote($sekretaer->basefolder, '/').'/', '', $request_string );

		$query_string = false;

		$request = explode( '?', $request_string );
		if( count($request) > 1 ) $query_string = $request[1];
		$request = $request[0];

		$request = explode( '/', $request );

		$this->request = $request;



		// check if we want to auto-login
		if( ! $sekretaer->authorized() && ! empty($_COOKIE['sekretaer-session']) ) {

			$cookie_session_id = $_COOKIE['sekretaer-session'];

			$cache = new Cache( 'session', $cookie_session_id, true );
			$session_data = trim($cache->get_data());

			if( $session_data ) {
				$session_data = json_decode($session_data, true);

				// restore session data:
				$_SESSION = $session_data;

				// refresh cookie lifetime:
				$cookie_lifetime = $sekretaer->config->get('cookie_lifetime');
				setcookie( 'sekretaer-session', $cookie_session_id, array(
					'expires' => time()+$cookie_lifetime,
					'path' => '/'
				));

				// refresh cache lifetime:
				$cache->touch();

			} else {

				// session expired, delete cookie
				setcookie( 'sekretaer-session', false, array(
					'expires' => -1,
					'path' => '/'
				));

			}

			$this->redirect( $request_string );

		}


		if( ! empty($request[0]) && $request[0] == 'action' ) {

			if( ! empty($request[1]) ) {
				$action = $request[1];

				$redirect_path = '';

				if( $action == 'logout' ) {

					$sekretaer->logout();

				} elseif( $action == 'login' ) {

					if( ! empty($_POST['path']) ) {
						$_SESSION['login_redirect_path'] = trailing_slash_it($_POST['path']);
					}

					$sekretaer->authorize( $_POST );
					exit;

				} elseif( $action == 'redirect' ) {

					$redirect_path = 'dashboard/';
					if( isset($_SESSION['login_redirect_path']) ) {
						$redirect_path = $_SESSION['login_redirect_path'];
					}

					$sekretaer->login();

				}

				if( isset($_SESSION['login_redirect_path']) ) {
					unset($_SESSION['login_redirect_path']);
				}
				
				$this->redirect( $redirect_path );

			}

			$this->route = array(
				'template' => '404',
			);

			return $this; // always end here if an action is set
		}


		if( $sekretaer->authorized() ) {

			if( empty($request[0]) ) {
				
				$this->redirect('dashboard');

			} else {

				$template = $request[0];

				if( ! file_exists($sekretaer->abspath.'system/site/'.$template.'.php') ) {
					$template = '404';
				}

				$channel = false;
				if( ! empty($request[1]) ) {
					$channel = $request[1];
				}

				$action = false;
				if( ! empty($request[2]) ) {
					$action = $request[2];
				}

				$this->route = array(
					'template' => $template,
					'channel' => $channel,
					'action' => $action
				);

			}

			
		} else {

			$this->route = array(
				'template' => 'login'
			);
	
		}
		
		return $this;
	}

	function get( $name = false ) {

		if( $name ) {

			if( ! is_array($this->route) ) return false;

			if( ! array_key_exists($name, $this->route) ) return false;

			return $this->route[$name];
		}

		return $this->route;
	}

	function redirect( $path ) {
		php_redirect( $path );
	}
	
}

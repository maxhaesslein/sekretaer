<?php

class Route {

	public $route;

	public $request;

	function __construct( $sekretaer ) {

		$request = $_SERVER['REQUEST_URI'];
		$request = preg_replace( '/^'.preg_quote($sekretaer->basefolder, '/').'/', '', $request );

		$query_string = false;

		$request = explode( '?', $request );
		if( count($request) > 1 ) $query_string = $request[1];
		$request = $request[0];

		$request = explode( '/', $request );

		$this->request = $request;


		if( ! empty($request[0]) && $request[0] == 'action' ) {

			if( ! empty($request[1]) ) {
				$action = $request[1];

				$redirect_path = '';

				if( $action == 'logout' ) {
					$sekretaer->logout();
				} elseif( $action == 'login' ) {
					$sekretaer->login( $_POST );

					$redirect_path = 'dashboard';
					if( ! empty($_POST['path']) ) {
						$redirect_path = $_POST['path'];
					}

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

				$test_template = $request[0];

				$template = '404';

				if( file_exists($sekretaer->abspath.'system/site/'.$test_template.'.php') ) {
					$template = $test_template;
				}

				$this->route = array(
					'template' => $template
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

		global $sekretaer;

		$new_location = trailing_slash_it($sekretaer->baseurl.$path);

		header( 'location:'.$new_location );
		exit;
	}
	
}

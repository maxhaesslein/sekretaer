<?php

class Microsub {

	private $me;
	private $name;

	private $api_url;
	private $access_token;
	private $scope;
	private $authorization;

	private $channels = [];
	private $feeds = [];

	function __construct(){

		if( empty($_SESSION['me']) || empty($_SESSION['name']) ) return; // this should not happen, but just in case ..

		$this->me = $_SESSION['me'];
		$this->name = $_SESSION['name'];

		if( ! isset($_SESSION['microsub_endpoint']) ) {
			// TODO: option to refresh the endpoint
			$this->show_error( 'no microsub endpoint found for '.$this->me );
		}
		$this->api_url = $_SESSION['microsub_endpoint'];

		if( ! isset($_SESSION['access_token']) ) {
			$this->show_error( 'no access token found for '.$this->me );
		}
		$this->access_token = $_SESSION['access_token'];

		if( ! isset($_SESSION['scope']) ) {
			$this->show_error( 'no scope found for '.$this->me );
		}
		$this->scope = explode( ' ', $_SESSION['scope'] );


		if( ! in_array( 'read', $this->scope ) || ! in_array( 'follow', $this->scope ) ) {
			$this->show_error( 'scope is missing <em>read</em> or <em>follow</em> (scope is <strong>'.implode( ' ', $_SESSION['scope']).'</strong>) for '.$this->me );
		}

		$this->authorization = 'Authorization: Bearer '.$this->access_token;

	}

	function get_channels() {

		if( ! count($this->channels) ) { // according to spec, we should have at least 2 channels - https://indieweb.org/Microsub-spec#Channels
			$api_result = $this->api_get( 'channels' );

			$channels = [];
			if( ! empty($api_result->channels) ) {
				foreach( $api_result->channels as $channel ) {
					$channels[$channel->uid] = $channel;
				}
			}

			$this->channels = $channels;
		}

		return $this->channels;
	}


	function get_feeds( $channel ) {

		if( ! array_key_exists($channel, $this->feeds) ) {
			$feeds = $this->api_get( 'follow', array( 'channel' => $channel ) );
			$this->feeds[$channel] = $feeds;
		}

		return $this->feeds[$channel];
	}


	function api_get( $action, $args = array() ) {

		$url = $this->api_url.'?action='.$action;

		if( ! isset($args['me']) ) {
			$args['me'] = $this->me;
		}

		if( count($args) ) {
			foreach( $args as $key => $value ) {
				$url .= '&'.$key.'='.$value; // TODO: sanitize
			}
		}

		$cache = new Cache( 'microsub', $url, false, 60*3 ); // cache for 3 minutes
		$data = $cache->get_data();
		if( $data ) return json_decode($data);

		$request = new Request( $url );
		$request->set_headers( array('Content-Type: application/json', $this->authorization) );
		$request->curl_request();

		$body = $request->get_body();

		if( $body ) $body = json_decode($body);

		$cache->add_data( json_encode($body) );

		return $body;
	}

	function api_post( $action, $args = array() ) {

		$url = $this->api_url.'?action='.$action;
		
		if( ! isset($args['me']) ) {
			$args['me'] = $this->me;
		}

		$request = new Request( $url );
		$request->set_headers( array($this->authorization) );
		$request->set_post_data( $args );
		$request->curl_request();

		$status_code = $request->get_status_code();
		$headers = $request->get_headers();
		$body = $request->get_body();

		return [
			'status_code' => $status_code,
			'headers' => $headers,
			'body' => $body
		];
	}

	function find_feeds( $url ) {

		$search_result = $this->api_post( 'search', [ 'query' => $url ] );

		if( $search_result['status_code'] != 200 ) {
			global $sekretaer;
			$sekretaer->debug( 'something went wrong while searching for feeds, the site return an unexpected status code', $search_result['status_code'], $url );
			return [];
		}

		$json = json_decode($search_result['body']);

		$results = [];
		if( ! empty($json->results) ) {
			$results = $json->results;
		}

		if( count($results) < 1 ) {
			return [];
		}

		return $results;
	}

	function subscribe_feed( $url, $active_channel ) {

		// follow feed - https://indieweb.org/Microsub-spec#Following
		$response = $this->api_post( 'follow', [
			'channel' => $active_channel,
			'url' => $url
		] );


		if( $response['status_code'] == 200 ) {
			return 'success';
		}

		return $response['body'];
	}


	function show_error( $error_message ) {

		echo '<p><strong>Error!</strong></p>';
		echo '<p>'.$error_message.'</p>';

		snippet( 'footer' );
		exit;
	}


}

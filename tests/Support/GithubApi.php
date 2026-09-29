<?php

namespace LibreSign\WordPressTheme\Tests\Support;

final class GithubApi {

	private $responses = array();

	private $requests = array();

	public function __construct() {
		add_filter( 'pre_http_request', array( $this, 'answer' ), 10, 3 );
	}

	public function answer_with( $path, $body, $status = 200 ) {
		$this->responses[ $path ] = array( $status, $body );
	}

	public function answer( $preempt, $args, $url ) {
		if ( 'api.github.com' !== wp_parse_url( $url, PHP_URL_HOST ) ) {
			return $preempt;
		}

		$path             = (string) wp_parse_url( $url, PHP_URL_PATH );
		$this->requests[] = array(
			'method'  => $args['method'],
			'path'    => $path,
			'headers' => $args['headers'],
			'body'    => $args['body'] ?? null,
		);

		list( $status, $body ) = $this->responses[ $path ] ?? array( 404, '{"message":"Not Found"}' );

		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $status,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}

	public function requests() {
		return $this->requests;
	}

	public function paths() {
		return array_column( $this->requests, 'path' );
	}
}

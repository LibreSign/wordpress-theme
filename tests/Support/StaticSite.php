<?php

namespace LibreSign\WordPressTheme\Tests\Support;

use donatj\MockWebServer\MockWebServer;
use donatj\MockWebServer\RequestInfo;
use donatj\MockWebServer\Response;
use donatj\MockWebServer\Responses\NotFoundResponse;

final class StaticSite {

	private static $server;

	private static $sites = 0;

	private $prefix;

	private $offset;

	public function __construct() {
		++self::$sites;
		$this->prefix = '/site-' . self::$sites;
		$this->offset = count( $this->received() );
	}

	public function origin() {
		return self::server()->getServerRoot() . $this->prefix;
	}

	public function serve( $path, $body, $status = 200 ) {
		self::server()->setResponseOfPath( $this->prefix . $path, new Response( $body, array(), $status ) );
	}

	public function paths() {
		return array_map(
			fn ( RequestInfo $request ) => substr( $request->getRequestUri(), strlen( $this->prefix ) ),
			array_slice( $this->received(), $this->offset )
		);
	}

	private function received() {
		$requests = array();
		$request  = self::server()->getRequestByOffset( 0 );

		while ( null !== $request ) {
			$requests[] = $request;
			$request    = self::server()->getRequestByOffset( count( $requests ) );
		}

		return $requests;
	}

	private static function server() {
		if ( null === self::$server ) {
			self::$server = new MockWebServer();
			self::$server->start();
			self::$server->setDefaultResponse( new NotFoundResponse() );
		}

		return self::$server;
	}
}

<?php
/**
	Uses Guzzle to make and receive HTTP Requests

	@see Use the Google to search about Guzzle Loging and MessageFormatter
	@see https://github.com/guzzle/guzzle/blob/master/src/Middleware.php
	@see https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php

*/

use Edoceo\Radix\DB\SQL;

class CRE_HTTP extends \GuzzleHttp\Client
{
	/**
		Simulate the old _curl_* functions from old CRE code
	*/
	static function _curl_init()
	{


	}

	/**
		Simulate the old _curl_* functions from old CRE code
	*/
	static function _curl_exec()
	{

	}

	function __construct($opt = null)
	{
		$chs = $this->_makeClientHandlerStack();

		$cfg = array(
			'handler' => $chs,
			'headers' => array(
				'user-agent' => 'OpenTHC/420.20.102 (pipe-stem)',
			),
			'http_errors' => false
		);

		if (is_array($opt)) {
			$cfg = array_merge($cfg, $opt);
		}

		parent::__construct($cfg);

	}

	function postForm($url, $arg=null)
	{
		return $this->_c->post($url, array('form_params' => $arg));
	}

	function postJSON($url, $arg=null)
	{
		return $this->_c->post($url, array('json' => $arg));
	}

	/**
		Make a Handler Stack and attach a Logger
		@return HandlerStack
	*/
	private function _makeClientHandlerStack()
	{
		$chs = GuzzleHttp\HandlerStack::create();
		$chs->push(function (callable $handler) {

			return function ($req, $opt=null) use ($handler) {

				$fmt = new GuzzleHttp\MessageFormatter(GuzzleHttp\MessageFormatter::DEBUG);

				// Success Handler
				$success = function($res) use ($req, $fmt) {

					SQL::insert('log_audit', array(
						'code' => $res->getStatusCode(),
						'path' => $req->getRequestTarget(),
						'req' => GuzzleHttp\Psr7\str($req),
						'res' => ($res ? GuzzleHttp\Psr7\str($res) : null),
					));

					return $res;

				};

				// Failure Handler
				$failure = function($err) use ($req, $fmt) {

					$res = $err instanceof GuzzleHttp\Exception\RequestException ? $err->getResponse() : null;

					SQL::insert('log_audit', array(
						'code' => ($res ? $res->getStatusCode() : '0'),
						'path' => $req->getRequestTarget(),
						'req' => GuzzleHttp\Psr7\str($req),
						'res' => ($res ? GuzzleHttp\Psr7\str($res) : null),
						'err' => $err->getMessage(),
					));

					return \GuzzleHttp\Promise\rejection_for($err);

				};

				// Use a Promise to view the details at the END of the request/response cycle
				$res = $handler($req, $opt)->then($success, $failure);

				return $res;

			};
		});

		return $chs;
	}

}

<?php
/**
 * Class Ajax
 * @package Adknown\ProxyScalyr
 * @author ahockley <alex@adknown.com>
 * @since 2018-02-22
 *
 * v1.00.00 - 2018-02-22 - Alex Hockley
 *          - First.
 *
 *        This class is intended to simplify ajax handlers across our platforms, should ensure some semblance of consistency.
 *        It is up to the implementing class to handle the data format and the data within the response object
 *
 *
 */

namespace Adknown\ProxyScalyr\Controllers;

use Exception;

abstract class Ajax
{
	const STATUS_OK = 'ok';
	const STATUS_WARNING = 'warning';
	const STATUS_ERROR = 'error';

	protected $response;

	protected $timeStart;

	protected abstract function Get();
	protected abstract function Put();
	protected abstract function Post();
	protected abstract function Delete();

	/**
	 * Handles the ajax request depending on the request method
	 * @throws Exception
	 */
	public function HandleRequest()
	{
		$this->timeStart = time();
		try
		{
			switch($_SERVER{'REQUEST_METHOD'})
			{
				case 'GET':
					$this->Get();
					break;
				case 'PUT':
					$this->Put();
					break;
				case 'POST':
					$this->Post();
					break;
				case 'DELETE':
					$this->Delete();
					break;
				default:
					throw new Exception("Unsupported HTTP Request Method: {$_SERVER['REQUEST_METHOD']}");
			}
		}
		catch(Exception $ex)
		{
			$this->RespondError($ex->getMessage());
		}

		$this->RespondWarning('HandleRequest method reached the end without sending a proper Response', null);
	}

	/**
	 * Send an error response to the client
	 *
	 * @param string $message The error message
	 *
	 * @param int    $statusCode
	 *
	 * @throws Exception
	 */
	protected function RespondError($message, int $statusCode = 400)
	{
		$this->response = new Response();
		$this->response->status = self::STATUS_ERROR;
		$this->response->message = $message;
		$this->response->data = null;
		$this->response->runtime = time() - $this->timeStart;
		$this->Respond($statusCode);
	}

	/**
	 * Send a warning response to the client
	 *
	 * @param string $message
	 * @param mixed  $data
	 *
	 * @param int    $statusCode
	 *
	 * @throws Exception
	 */
	protected function RespondWarning($message, $data, int $statusCode = 400)
	{
		$this->response = new Response();
		$this->response->status = self::STATUS_WARNING;
		$this->response->message = $message;
		$this->response->data = $data;
		$this->response->runtime = time() - $this->timeStart;
		$this->Respond($statusCode);
	}

	/**
	 * Send an OK response to the client
	 *
	 * @param mixed $data The data to send back to the client in the response body
	 *
	 * @param int   $statusCode
	 *
	 * @throws Exception
	 */
	protected function RespondOK($data, int $statusCode = 200)
	{
		$this->response = new Response();
		$this->response->status = self::STATUS_OK;
		$this->response->message = '';
		$this->response->data = $data;
		$this->response->runtime = time() - $this->timeStart;
		$this->Respond($statusCode);
	}

	/**
	 * Send an error response to the client stating their http method is unsupported
	 *
	 * @throws Exception
	 */
	protected function RespondUnsupportedMethod()
	{
		$this->RespondError("Unsupported Method");
	}

	/**
	 * Send response to the client
	 *
	 * @param int $statusCode
	 *
	 * @throws Exception
	 */
	protected function Respond(int $statusCode)
	{
		$json = json_encode($this->response);
		if($json === false)
		{
			throw new Exception('Unable to encode json object');
		}
		header('Content-type: application/json;charset=utf-8');
		http_response_code($statusCode);

		if(getenv("GSPS_PPROF") !== false)
		{
			if (function_exists('memprof_enable')) {
				$fp = fopen(sys_get_temp_dir() . DIRECTORY_SEPARATOR . time() . "_profile.heap", "w");
				memprof_dump_pprof($fp);
				fclose($fp);
			}
		}
		die($json);
	}
}


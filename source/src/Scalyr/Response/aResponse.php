<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-17
 * Time: 11:54
 */

namespace Adknown\ProxyScalyr\Scalyr\Response;


abstract class aResponse
{
	/** @var string */
	public $status;

	/** @var int */
	public $executionTime;

	public function __construct(string $response)
	{
		$this->AssignValues(\GuzzleHttp\json_decode($response, true));
	}

	public abstract function AssignValues(array $obj);

	/**
	 * @param array $fields
	 * @param       $obj
	 *
	 * @throws \Exception
	 */
	protected function AllFieldsPresent(array $fields, $obj)
	{
		foreach($fields as $field)
		{
			if(!isset($obj[$field]))
			{
				throw new \Exception("Missing $field. Requires: " . implode(",", $fields) . ", found: " . array_keys($obj));
			}
		}
	}
}
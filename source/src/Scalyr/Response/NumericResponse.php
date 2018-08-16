<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-17
 * Time: 11:56
 */

namespace Adknown\ProxyScalyr\Scalyr\ScalyrResponse;


class NumericResponse extends aResponse
{
	/** @var int[] */
	public $values;

	public function AssignValues(array $obj)
	{
		$this->AllFieldsPresent(['status', 'values', 'executionTime'], $obj);

		$this->status = $obj['status'];
		$this->values = $obj['values'];
		$this->executionTime = $obj['executionTime'];
	}
}
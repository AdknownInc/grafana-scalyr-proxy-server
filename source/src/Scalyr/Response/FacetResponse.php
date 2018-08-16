<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-17
 * Time: 13:24
 */

namespace Adknown\ProxyScalyr\Scalyr\ScalyrResponse;


class FacetResponse extends aResponse
{
	/** @var int */
	public $matchCount;

	/** @var array[] Each element is an assoc array of structure ['value' => string, 'count' => int] */
	public $values;

	public function AssignValues(array $obj)
	{
		$this->AllFieldsPresent(['status', 'values', 'matchCount', 'executionTime'], $obj);
		$this->status = $obj['status'];
		$this->values = $obj['values'];
		$this->matchCount = $obj['matchCount'];
		$this->executionTime = $obj['executionTime'];
	}
}
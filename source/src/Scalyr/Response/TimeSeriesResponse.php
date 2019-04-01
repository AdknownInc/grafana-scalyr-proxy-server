<?php


namespace Adknown\ProxyScalyr\Scalyr\Response;


class TimeSeriesResponse extends aResponse
{
	/** @var int[] */
	public $values;

	public function AssignValues(array $obj)
	{
		$this->AllFieldsPresent(['status', 'results', 'executionTime'], $obj);
		$this->status = $obj['status'];
		$this->values = $obj['results'][0]['values'];
		$this->executionTime = $obj['executionTime'];
	}

	/**
	 * Gets a NumericResponse object out of this instance of a timeseries object
	 *
	 * @return NumericResponse
	 */
	public function GetAsNumeric()
	{
		$response = new NumericResponse("");
		$response->status = $this->status;
		$response->values = $this->values;
		$response->executionTime = $this->executionTime;
		return $response;
	}
}
<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 05/07/18
 * Time: 11:57 AM
 */

namespace Adknown\ProxyScalyr\Grafana\Response\Query;


class TimeSeries implements \JsonSerializable
{
	/**
	 * @var TimeSeriesTarget[]
	 */
	private $targets = [];

	public function AddTarget(TimeSeriesTarget $target)
	{
		$this->targets[] = $target;
	}
	/**
	 * Specify data which should be serialized to JSON
	 * @link  http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		$return = [];
		foreach ($this->targets as $target)
		{
			$return[] = [
				"target" => $target->target,
				"datapoints" => $target->datapoints,
				"queries" => $target->individualQueries,
				"refId" => $target->refId
			];
		}

		return $return;
	}

	/**
	 * @return TimeSeriesTarget[]
	 */
	public function GetTargets()
	{
		return $this->targets;
	}
}
<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 08/07/18
 * Time: 9:31 PM
 */

namespace Adknown\ProxyScalyr\Grafana\Response\Query;


class Datapoint implements \JsonSerializable
{
	public $value;
	public $timestamp;

	public function __construct($value, $timestamp)
	{
		$this->value = $value;
		$this->timestamp = $timestamp;
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
		return [$this->value, $this->timestamp];
	}
}
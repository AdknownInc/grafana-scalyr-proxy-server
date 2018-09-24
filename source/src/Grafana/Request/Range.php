<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 05/07/18
 * Time: 11:48 AM
 */

namespace Adknown\ProxyScalyr\Grafana\Request;


class Range
{
	public $from;
	public $to;

	/**
	 * Returns the unix timestamp equivalent of the set $from string
	 *
	 * @return false|int
	 */
	public function GetFromAsTimestamp()
	{
		return strtotime($this->from);
	}

	/**
	 * Returns the unix timestamp equivalent of the set $to string
	 *
	 * @return false|int
	 */
	public function GetToAsTimestamp()
	{
		return strtotime($this->to);
	}


}
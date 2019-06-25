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
		//Alerts don't pass time as a string, they pass as unix ms
		if(!strtotime($this->from))
		{
			return $this->from;
		}
		return strtotime($this->from);
	}

	/**
	 * Returns the unix timestamp equivalent of the set $to string
	 *
	 * @return false|int
	 */
	public function GetToAsTimestamp()
	{
		//Alerts don't pass time as a string, they pass as unix ms
		if(!strtotime($this->to))
		{
			return $this->to;
		}
		return strtotime($this->to);
	}


}
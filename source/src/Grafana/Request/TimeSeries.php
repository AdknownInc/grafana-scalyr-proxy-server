<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 05/07/18
 * Time: 11:46 AM
 */

namespace Adknown\ProxyScalyr\Grafana\Request;


class TimeSeries
{
	/**
	 * @var int
	 */
	public $panelId;

	/**
	 * @var string
	 */
	public $panelName;

	/**
	 * @var string
	 */
	public $user;

	/**
	 * @var int
	 */
	public $userId;

	/**
	 * @var string
	 */
	public $org;

	/**
	 * @var int
	 */
	public $orgId;

	/**
	 * @var Range
	 */
	public $range;

	/**
	 * @var string
	 */
	public $interval;

	/**
	 * @var Target[]
	 */
	public $targets;

	/**
	 * @var int
	 */
	public $maxDataPoints;

	/**
	 * @var bool
	 */
	public $parseComplex;

	/**
	 * Returns information about the request for logging purposes
	 * @return array
	 */
	public function GetLoggingInfo()
	{
		return [
			"Username" => $this->user,
			"User Id" => $this->userId,
			"Org name" => $this->org,
			"Org Id" => $this->orgId,
			"Panel Name" => $this->panelName,
			"Panel Id" => $this->panelId
		];
	}

}
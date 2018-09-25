<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 06/07/18
 * Time: 5:44 PM
 */

namespace Adknown\ProxyScalyr\Grafana\Request;


class Target
{
	const INTERVAL_TYPE_FIXED = 'fixed';
	const INTERVAL_TYPE_WINDOW = 'window';

	const FIXED_INTERVAL_MINUTE = 'minute';
	const FIXED_INTERVAL_HOUR = 'hour';
	const FIXED_INTERVAL_DAY = 'day';
	const FIXED_INTERVAL_WEEK = 'week';
	const FIXED_INTERVAL_MONTH = 'month';

	//all fields are assumed to be strings unless otherwise annotated
	public $filter;
	public $graphFunction;
	public $metricName;
	public $namespace;
	/**
	 * @var int
	 */
	public $percentage;
	public $periodl;
	public $placeholder;
	public $refId;
	public $region;
	/**
	 * @var int
	 */
	public $secondsInterval;
	/**
	 * @var string The type of interval used in this time series.
	 * Possible Values: 'fixed', 'window'
	 */
	public $intervalType;

	/**
	 * @var string The chosen interval type, only relevant when the user has selected 'fixed' for interval type
	 */
	public $chosenType;
	public $target;
	public $type;
	public $expression;
}
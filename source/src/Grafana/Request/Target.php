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
	public $target;
	public $type;
	public $expression;

}
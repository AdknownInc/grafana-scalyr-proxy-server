<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-10-31
 */

namespace Adknown\ProxyScalyr\Grafana\Response\ElasticSearchFaker;


class Hits
{
	/** @var int */
	public $total;

	/** @var double */
	public $max_score;

	public $hits = [];
}
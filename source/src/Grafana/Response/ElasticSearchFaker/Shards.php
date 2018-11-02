<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-10-31
 */

namespace Adknown\ProxyScalyr\Grafana\Response\ElasticSearchFaker;


class Shards
{

	/** @var int */
	public $total;

	/** @var int */
	public $successful;

	/** @var int */
	public $skipped;

	/** @var int */
	public $failed;

}
<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-10-31
 */

namespace Adknown\ProxyScalyr\Grafana\Response\ElasticSearch;


class ElasticSearchResponse
{
	/** @var int */
	public $took;

	/** @var bool */
	public $timed_out;

	/** @var Shards */
	public $_shards;

	/** @var Hits */
	public $hits;

	public $aggregations;

	/** @var int */
	public $status;

	/**
	 * Populates the object with some meaningless default values
	 */
	public static function DefaultResponse()
	{
		$esr = new ElasticSearchResponse();

		$esr->took = 10;
		$esr->timed_out = false;
		$esr->_shards = new Shards();
		$esr->_shards->total = 5;
		$esr->_shards->successful = 5;
		$esr->_shards->skipped = 0;
		$esr->_shards->failed = 0;

		$esr->hits = new Hits();
		$esr->hits->total = 1;
		$esr->hits->max_score = 0;

		$esr->aggregations = [
			"2" => [
				"buckets" => [

				]
			]
		];

		$esr->status = 200;

		return $esr;
	}

	public function AddToResult($value, $count)
	{
		$this->aggregations["2"]["buckets"][] = [
			"key_as_string" => (string)$value,
			"key" => $value,
			"doc_count" => $count
		];
	}
}
<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-10-31
 */

namespace Adknown\ProxyScalyr\Grafana\Response\ElasticSearch;


class ElasticSearchMultiSearchResponse
{
	/** @var ElasticSearchResponse[] */
	public $responses = [];

	/**
	 * @param ElasticSearchResponse $response
	 */
	public function AddResponse($response)
	{
		$this->responses[] = $response;
	}
}
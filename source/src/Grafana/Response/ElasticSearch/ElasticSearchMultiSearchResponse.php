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

	public $README = "Hello! If you're reading this, you're probably trying to set alerts on scalyr data
	through the Grafana Elasticsearch data source. To accomplish this, set the query block below's fields to the following values: 
	Metric->Count, Group By->Date, Histogram->@timestamp->Interval: auto. The Query field is where you're going to put your information.
	Enter your query in the following json format: {\"target\": \"your target value\", \"filter\":\"your filter value\"}.
	Those are the only two properties you need.
	You can also specify any amount of fields in the query field with this object (defaults are provided as described):
	{\"target\": null, \"filter\": null, \"secondsInterval\": 60, \"graphFunction\": \"mean\", \"intervalType\": \"window\", \"chosenType\": \"minute\",
	\"type\": \"complex numeric query\", \"percentage\": 25}. intervalType should have one of the following values:
	[\"window\", \"fixed\"]. If window is set, secondsInterval should be set to the desired number of seconds. 
	If fixed is set, chosenType should have one of the following values:
	[\"minute\", \"hour\", \"day\", \"week\", \"month\"]";

	public $message = "ok";

	/**
	 * @param ElasticSearchResponse $response
	 */
	public function AddResponse($response)
	{
		$this->responses[] = $response;
	}
}
<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-11-01
 */

namespace Adknown\ProxyScalyr\Tests;

use Adknown\ProxyScalyr\Controllers\ElasticScalyrConverter;
use Adknown\ProxyScalyr\Grafana\Request\Range;
use Adknown\ProxyScalyr\Grafana\Request\Target;
use Adknown\ProxyScalyr\Grafana\Request\TimeSeries;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearch\ElasticSearchMultiSearchResponse;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearch\ElasticSearchResponse;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearch\Hits;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearch\Shards;
use Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeriesTarget;
use Karriere\JsonDecoder\JsonDecoder;
use PHPUnit\Util\Json;

class ElasticScalyrConverterTest extends \PHPUnit\Framework\TestCase
{
	const ELASTIC_TWO_QUERIES_REQUEST_FILE = __DIR__ . "/ElasticRequests/TwoQueries.txt";
	const SCALYR_TWO_QUERIE_RESPONSE_FILE = __DIR__ . "/ScalyrResponses/TwoResponses.json";

	public function testGetTargetArrayFromQueries()
	{
		$converter = new ElasticScalyrConverter();

		$queries = file_get_contents(self::ELASTIC_TWO_QUERIES_REQUEST_FILE);

		$queries = $converter->GetDecodedQueries($queries, true);

		$expectedTargetArray = [];

		for($i = 0; $i < 2; $i++)
		{
			$expectedTarget = new Target();

			$expectedTarget->graphFunction = "mean";
			$expectedTarget->percentage = 25;
			$expectedTarget->secondsInterval = 60;
			$expectedTarget->chosenType = "minute";
			$expectedTarget->target = "t";
			$expectedTarget->type = "complex numeric query";
			$expectedTargetArray[] = $expectedTarget;
		}

		$expectedTargetArray[0]->filter = 'count(message contains "error") / count(message contains "success")';
		$expectedTargetArray[1]->filter = 'count(message contains "success")';

		$targetArrayResult = $converter->GetTargetArrayFromQueries($queries);

		$this->assertEquals($expectedTargetArray, $targetArrayResult);
	}

	public function testConvertElasticSearchRequestToScalyr()
	{
		$converter = new ElasticScalyrConverter();

		$queries = file_get_contents(self::ELASTIC_TWO_QUERIES_REQUEST_FILE);

		$queries = $converter->GetDecodedQueries($queries, true);

		$result = $converter->ConvertElasticSearchRequestToScalyr($queries);

		$expectedTargetArray = [];

		for($i = 0; $i < 2; $i++)
		{
			$expectedTarget = new Target();

			$expectedTarget->graphFunction = "mean";
			$expectedTarget->percentage = 25;
			$expectedTarget->secondsInterval = 60;
			$expectedTarget->chosenType = "minute";
			$expectedTarget->target = "t";
			$expectedTarget->type = "complex numeric query";
			$expectedTargetArray[] = $expectedTarget;
		}

		$expectedTargetArray[0]->filter = 'count(message contains "error") / count(message contains "success")';
		$expectedTargetArray[1]->filter = 'count(message contains "success")';

		$expectedScalyrRequest = new TimeSeries();

		$expectedScalyrRequest->targets = $expectedTargetArray;

		$expectedRange = new Range();
		$expectedRange->from = "2018-11-01T06:59:36.000Z";
		$expectedRange->to = "2018-11-01T12:59:36.000Z";

		$expectedScalyrRequest->range = $expectedRange;
		$expectedScalyrRequest->interval = "60s";

		$expectedScalyrRequest->maxDataPoints = 479;

		$this->assertEquals($expectedScalyrRequest, $result);
	}

	public function testConvertScalyrResponseToElasticSearch()
	{
		$converter = new ElasticScalyrConverter();

		$scalyrTargets = file_get_contents(self::SCALYR_TWO_QUERIE_RESPONSE_FILE);
		$scalyrTargets = json_decode($scalyrTargets, true);

		$scalyrResponse = new \Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeries();


		foreach($scalyrTargets as $scalyrTarget)
		{
			$target = new TimeSeriesTarget($scalyrTarget["target"], $scalyrTarget["datapoints"]);
			$scalyrResponse->AddTarget($target);
		}

		$result = $converter->ConvertScalyrResponseToElasticSearch($scalyrResponse);

		$expected = new ElasticSearchMultiSearchResponse();

		for($i = 0; $i < 2; $i++)
		{
			$response = new ElasticSearchResponse();
			$response->took = 10;
			$response->timed_out = false;
			$response->_shards = new Shards();
			$response->_shards->total = 5;
			$response->_shards->successful = 5;
			$response->_shards->skipped = 0;
			$response->_shards->failed = 0;
			$response->hits = new Hits();
			$response->hits->total = 1;
			$response->hits->max_score = 0;
			$response->hits->hits = [];
			$response->status = 200;
			$expected->AddResponse($response);
		}
		$expected->responses[0]->aggregations = [
			"2" => [
				"buckets" => [
					[
						"key_as_string" => "1",
						"key"           => 1,
						"doc_count"     => 1
					],
					[
						"key_as_string" => "56",
						"key"           => 56,
						"doc_count"     => 2
					],
					[
						"key_as_string" => "96",
						"key"           => 96,
						"doc_count"     => 69
					],
				]
			]
		];
		$expected->responses[1]->aggregations = [
			"2" => [
				"buckets" => [
					[
						"key_as_string" => "345",
						"key"           => 345,
						"doc_count"     => 10000
					],
					[
						"key_as_string" => "1234",
						"key"           => 1234,
						"doc_count"     => 1234
					],
					[
						"key_as_string" => "876",
						"key"           => 876,
						"doc_count"     => 678
					],
					[
						"key_as_string" => "34",
						"key"           => 34,
						"doc_count"     => 12
					],
				]
			]
		];

		$this->assertEquals($expected, $result);
	}

	public function testGetDecodedQueries()
	{
		$converter = new ElasticScalyrConverter();

		$queries = file_get_contents(self::ELASTIC_TWO_QUERIES_REQUEST_FILE);

		$result = $converter->GetDecodedQueries($queries, true);

		$expected =
			[
				0 =>
					[
						'size'  => 0,
						'query' =>
							[
								'bool' =>
									[
										'filter' =>
											[
												0 =>
													[
														'range' =>
															[
																'@timestamp' =>
																	[
																		'gte'    => '1541055576466',
																		'lte'    => '1541077176466',
																		'format' => 'epoch_millis',
																	],
															],
													],
												1 =>
													[
														'query_string' =>
															[
																'analyze_wildcard' => true,
																'query'            => '{"percentage": 25,"graphFunction": "mean","secondsInterval": 60,"target": "t","type": "complex numeric query", "chosenType": "minute", "filter": "count(message contains \"error\") / count(message contains \"success\")"}',
															],
													],
											],
									],
							],
						'aggs'  =>
							[
								2 =>
									[
										'date_histogram' =>
											[
												'interval'        => '1d',
												'field'           => '@timestamp',
												'min_doc_count'   => 0,
												'extended_bounds' =>
													[
														'min' => '1541055576466',
														'max' => '1541077176466',
													],
												'format'          => 'epoch_millis',
											],
										'aggs'           =>
											[
											],
									],
							],
					],
				1 =>
					[
						'size'  => 0,
						'query' =>
							[
								'bool' =>
									[
										'filter' =>
											[
												0 =>
													[
														'range' =>
															[
																'@timestamp' =>
																	[
																		'gte'    => '1541055576466',
																		'lte'    => '1541077176466',
																		'format' => 'epoch_millis',
																	],
															],
													],
												1 =>
													[
														'query_string' =>
															[
																'analyze_wildcard' => true,
																'query'            => '{"percentage": 25,"graphFunction": "mean","secondsInterval": 60,"target": "t","type": "complex numeric query", "chosenType": "minute", "filter": "count(message contains \"success\")"}',
															],
													],
											],
									],
							],
						'aggs'  =>
							[
								2 =>
									[
										'date_histogram' =>
											[
												'interval'        => '1d',
												'field'           => '@timestamp',
												'min_doc_count'   => 0,
												'extended_bounds' =>
													[
														'min' => '1541055576466',
														'max' => '1541077176466',
													],
												'format'          => 'epoch_millis',
											],
										'aggs'           =>
											[
											],
									],
							],
					],
			];

		$this->assertEquals($result, $result);
	}
}

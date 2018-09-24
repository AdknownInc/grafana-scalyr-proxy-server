<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-12
 * Time: 15:04
 */

namespace Adknown\ProxyScalyr\Controllers;

use Adknown\ProxyScalyr\Grafana\Request\Target;
use Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeriesTarget;
use Adknown\ProxyScalyr\Logging\LoggerImpl;
use Adknown\ProxyScalyr\Scalyr\ComplexExpressions\Parser;
use Adknown\ProxyScalyr\Scalyr\Request\Numeric;
use Adknown\ProxyScalyr\Scalyr\Response\FacetResponse;
use Adknown\ProxyScalyr\Scalyr\Response\NumericResponse;
use Adknown\ProxyScalyr\Scalyr\SDK;
use Exception;

class Middleware
{
	/**
	 * @var SDK
	 */
	private $api;

	const QUERY_TYPES = [
		'numeric query'         => 0,
		'facet query'           => 0,
		'complex numeric query' => 0
	];

	public function __construct()
	{
		$this->api = new SDK(getenv('SCALYR_READ_KEY'));
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Request\TimeSeries $request
	 *
	 * @return \Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeries
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function GrafanaToScalyrQuery(\Adknown\ProxyScalyr\Grafana\Request\TimeSeries $request)
	{
		$grafResponse = new \Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeries();

		foreach($request->targets as $targetIndex => $queryData)
		{
			if(!isset(self::QUERY_TYPES[$queryData->type]))
			{
				throw new Exception("'type' must defined as one of: " . implode("','", self::QUERY_TYPES));
			}

			if(empty($queryData->target))
			{
				throw new Exception(sprintf("Empty target found for query #%d. All queries must have a target.", $targetIndex + 1));
			}
			$start = $request->range->GetFromAsTimestamp();
			$end = $request->range->GetToAsTimestamp();
			$buckets = $this->CalculateBuckets($start, $end, $queryData->secondsInterval);

			switch($queryData->type)
			{
				case 'numeric query':
					//split the query into complete intervals
					if($queryData->intervalType === Target::INTERVAL_TYPE_FIXED)
					{
						$startDT = new \DateTime($request->range->from, new \DateTimeZone('utc'));
						$endDT = new \DateTime($request->range->to, new \DateTimeZone('utc'));
						switch($queryData->chosenType)
						{
							case Target::FIXED_INTERVAL_MINUTE:
								$startDT->setTime(
									(int)$startDT->format('H'),
									(int)$startDT->format('i'),
									0
								);
								$endDT->setTime(
									(int)$endDT->format('H'),
									(int)$endDT->format('i'),
									0
								);
								$queryData->secondsInterval = 60;
								break;
							case Target::FIXED_INTERVAL_HOUR:
								$startDT->setTime(
									(int)$startDT->format('H'),
									0,
									0
								);
								$endDT->setTime(
									(int)$endDT->format('H'),
									0,
									0
								);
								$queryData->secondsInterval = 3600;
								break;
							case Target::FIXED_INTERVAL_DAY:
								$startDT->setTime(
									0,
									0,
									0
								);
								$endDT->setTime(
									0,
									0,
									0
								);
								$queryData->secondsInterval = 86400;
								break;
							case Target::FIXED_INTERVAL_WEEK:
							case Target::FIXED_INTERVAL_MONTH:
							default:
								throw new Exception("Selection '{$queryData->chosenType}' Not yet implemented");
						}

						$startRemainder = $endDT->getTimestamp();
						$endRemainder = $end;
						$bucketsRemainder = 1;
						$remainderResponse = $this->api->NumericQuery(
							new Numeric(
								$queryData->filter,
								$queryData->graphFunction,
								!empty($queryData->expression) ? $queryData->expression : '',
								$startRemainder,
								$endRemainder,
								$bucketsRemainder
							)
						);

						$start = $startDT->getTimestamp();
						$end = $endDT->getTimestamp();
						$buckets = $this->CalculateBuckets($start, $end, $queryData->secondsInterval);
					}


					$response = $this->api->NumericQuery(
						new Numeric(
							$queryData->filter,
							$queryData->graphFunction,
							!empty($queryData->expression) ? $queryData->expression : '',
							$start,
							$end,
							$buckets
						)
					);

					if(isset($remainderResponse))
					{
						$response->values = array_merge($response->values, $remainderResponse->values);
						unset($remainderResponse);
					}

					$grafResponse->AddTarget($this->ConvertScalyrNumericToGrafana($response, $queryData->target, $start, $queryData->secondsInterval));
					break;

				case 'complex numeric query':

					$simpleExpressions = Parser::ParseComplexExpression($queryData->filter, $start, $end, $buckets, $fullVariableExpression);
					foreach($simpleExpressions as $key => $scalyrParams)
					{
						if($scalyrParams instanceof Numeric)
						{
							$response = $this->api->NumericQuery($scalyrParams);
							$simpleExpressions[$key] = $response;
						}
					}
					$fullResponse = Parser::NewEvaluateExpression($fullVariableExpression, $simpleExpressions);
					$grafResponse->AddTarget($this->ConvertScalyrNumericToGrafana($fullResponse, $queryData->target, $start, $queryData->secondsInterval));
					break;
				case 'facet query':
					throw new Exception("facet queries not yet implemented");
				default:
					throw new Exception("Unsupported query type: " . $queryData->type);
			}
		}

		return $grafResponse;
	}

	/**
	 * Calculates the number of buckets to send to Scalyr's api based on the time frame passed in
	 *
	 * @param int $start Starting timestamp in milliseconds
	 * @param int $end   Ending timestamp in milliseconds
	 * @param int $intervalSeconds
	 *
	 * @return int
	 */
	public static function CalculateBuckets(int $start, int $end, int $intervalSeconds)
	{
		$timeframe = $end - $start;

		return (int)($timeframe / $intervalSeconds);
	}

	/**
	 * Converts the values returned by a Scalyr numeric query into a series of datapoints that can be consumed by grafana.
	 *
	 * Grafana requires their timestamps to be in milliseconds while Scalyr returns their times in seconds, hence this neccesity.
	 *
	 * @param NumericResponse $response
	 * @param string          $target
	 * @param int             $startTime      timestamp
	 * @param int             $incrementValue amount of time to increase each timestmap by in seconds
	 *
	 * @return TimeSeriesTarget
	 */
	public static function ConvertScalyrNumericToGrafana(NumericResponse $response, ?string $target, int $startTime, int $incrementValue)
	{
		$datapoints = [];
		$startTime *= 1000;
		$incrementValue *= 1000;

		foreach($response->values as $value)
		{
			$datapoints[] = [(double)$value, $startTime];
			$startTime += $incrementValue;
		}

		return new TimeSeriesTarget($target, $datapoints);
	}

	/**
	 * Converts the values returned by a Scalyr numeric query into a series of datapoints that can be consumed by grafana.
	 *
	 * Grafana requires their timestamps to be in milliseconds while Scalyr returns their times in seconds, hence this necessity.
	 *
	 * @param FacetResponse $response
	 * @param null|string   $target
	 * @param int           $startTime
	 * @param int           $incrementValue
	 *
	 * @return TimeSeriesTarget
	 */
	public static function ConvertScalyrFacetToGrafana(FacetResponse $response, ?string $target, int $startTime, int $incrementValue)
	{
		$datapoints = [];

		$startTime *= 1000;
		$incrementValue *= 1000;

		foreach($response->values as $valuePair)
		{
			$datapoints[] = [(double)$valuePair['count'], $startTime];
			$startTime += $incrementValue;
		}

		return new TimeSeriesTarget($target, $datapoints);
	}
}

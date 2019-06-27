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
use Adknown\ProxyScalyr\Scalyr\ComplexExpressions\Parser;
use Adknown\ProxyScalyr\Scalyr\Request\Numeric;
use Adknown\ProxyScalyr\Scalyr\Request\TimeSeriesQuery;
use Adknown\ProxyScalyr\Scalyr\Response\FacetResponse;
use Adknown\ProxyScalyr\Scalyr\Response\NumericResponse;
use Adknown\ProxyScalyr\Scalyr\SDK;

class Middleware
{
	/**
	 * @var SDK
	 */
	private $api;

	/**
	 * @var bool Whether or not to send numeric queries or to send them as TimeSeries queries
	 */
	private $useNumeric;

	const QUERY_TYPES = [
		'numeric query'         => 0,
		'facet query'           => 0,
		'complex numeric query' => 0
	];

	public function __construct(bool $useNumeric)
	{
		//"<no value>" is what grafana populates when no read key is given
		if(empty($_SERVER['HTTP_X_SCALYR_READ_KEY']) || $_SERVER['HTTP_X_SCALYR_READ_KEY'] === "<no value>")
		{
			$readLogsKey = getenv('SCALYR_READ_KEY');
		}
		else
		{
			$readLogsKey = $_SERVER['HTTP_X_SCALYR_READ_KEY'];
		}

		if(empty($_SERVER['HTTP_X_SCALYR_READ_CONFIG_KEY']) || $_SERVER['HTTP_X_SCALYR_READ_CONFIG_KEY'] === "<no value>")
		{
			$readConfigKey = getenv('SCALYR_READ_CONFIG_KEY');
		}
		else
		{
			$readConfigKey = $_SERVER['HTTP_X_SCALYR_READ_CONFIG_KEY'];
		}

		$this->api = new SDK($readLogsKey, $readConfigKey);
		$this->useNumeric = $useNumeric;
	}

	/**
	 * @param \DateTime $dt Datetime to floor to nearest minute
	 */
	private function DtFloorToMinute(\DateTime $dt)
	{
		$dt->setTime(
			(int)$dt->format('H'),
			(int)$dt->format('i'),
			0
		);
	}

	/**
	 * @param \DateTime $dt Datetime to floor to nearest hour
	 */
	private function DtFloorToHour(\DateTime $dt)
	{
		$dt->setTime((int)$dt->format('H'), 0, 0);
	}

	/**
	 * @param \DateTime $dt Datetime to floor to nearest day
	 */
	private function DtFloorToDay(\DateTime $dt)
	{
		$dt->setTime(0, 0, 0);
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Request\Target
	 * @param int $roundedEnd - The "end" timestamp, rounded
	 * @param int $end        - The unrounded "end" timestamp
	 *
	 * @return float - The Scalyr datapoint value representing the interval [roundendEnd, end]
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException
	 */
	private function GetScalyrNumericRemainder($queryData, $roundedEnd, $end)
	{
		return $this->GetScalyrNumericResponse($queryData, $roundedEnd, $end, 1)->values[0];
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Request\Target
	 * @param int $start          - Start time timestamp
	 * @param int $end            - End time timestamp
	 * @param int $buckets        - The number of buckets (datapoints) Scalyr should return, distributed evenly
	 *                            between $start and $end
	 *
	 * @return NumericResponse - A Scalyr numeric response contain X datapoints, where X == $buckets
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 * @throws \Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException
	 */
	private function GetScalyrNumericResponse($queryData, $start, $end, $buckets)
	{
		if($this->useNumeric)
		{
			return $this->api->NumericQuery(
				new Numeric(
					$queryData->filter,
					$queryData->graphFunction,
					!empty($queryData->expression) ? $queryData->expression : '',
					$start,
					$end,
					$buckets
				)
			);
		}
		else
		{
			return $this->api->TimeSeriesQuery([
				new TimeSeriesQuery(
					$queryData->filter,
					$queryData->graphFunction,
					!empty($queryData->expression) ? $queryData->expression : '',
					$start,
					$end,
					$buckets
				)
			]);
		}
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Request\TimeSeries $request
	 * @param \Adknown\ProxyScalyr\Grafana\Request\Target     $queryData
	 *
	 * @return TimeSeriesTarget - The target to send in the Grafana response
	 * @throws \Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function GetNumericQueryTarget($request, $queryData)
	{
		$start = $request->range->GetFromAsTimestamp();
		$end = $request->range->GetToAsTimestamp();

		if($queryData->intervalType === Target::INTERVAL_TYPE_FIXED)
		{
			$startDT = new \DateTime($request->range->from, new \DateTimeZone('utc'));
			$endDT = new \DateTime($request->range->to, new \DateTimeZone('utc'));
			switch($queryData->chosenType)
			{
				case Target::FIXED_INTERVAL_MINUTE:
					$this->DtFloorToMinute($startDT);
					$this->DtFloorToMinute($endDT);
					$queryData->secondsInterval = 60;
					break;
				case Target::FIXED_INTERVAL_HOUR:
					$this->DtFloorToHour($startDT);
					$this->DtFloorToHour($endDT);
					$queryData->secondsInterval = 3600;
					break;
				case Target::FIXED_INTERVAL_DAY:
					$this->DtFloorToDay($startDT);
					$this->DtFloorToDay($endDT);
					$queryData->secondsInterval = 86400;
					break;
				case Target::FIXED_INTERVAL_WEEK:
				case Target::FIXED_INTERVAL_MONTH:
				default:
					throw new \Exception("Selection '{$queryData->chosenType}' Not yet implemented");
			}

			$remainderEnd = $end;
			$remainderValue = $this->GetScalyrNumericRemainder(
				$queryData,
				$endDT->getTimestamp(),
				$remainderEnd
			);


			$start = $startDT->getTimestamp();
			$end = $endDT->getTimestamp();
		}

		$start -= $queryData->secondsInterval;
		$buckets = self::CalculateBuckets($start, $end, $queryData->secondsInterval);
		$response = $this->GetScalyrNumericResponse($queryData, $start, $end, $buckets);

		$grafanaTarget = self::ConvertScalyrNumericToGrafana($response, $queryData->target, $start, $queryData->secondsInterval);

		if(isset($remainderValue) && isset($remainderEnd))
		{
			$grafanaTarget->Append($remainderValue, $remainderEnd);
		}

		return $grafanaTarget;
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Request\TimeSeries $request
	 * @param \Adknown\ProxyScalyr\Grafana\Request\Target     $queryData
	 *
	 * @return TimeSeriesTarget - The target to send in the Grafana response
	 * @throws \Exception - Numeric bucket limit reached
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	private function GetComplexQueryTarget($request, $queryData)
	{
		$start = $request->range->GetFromAsTimestamp();
		$end = $request->range->GetToAsTimestamp();
		$buckets = self::CalculateBuckets($start, $end, $queryData->secondsInterval);

		$simpleExpressions = Parser::ParseComplexExpression($queryData->filter, $start, $end, $buckets, $fullVariableExpression, $this->useNumeric);
		$individualExpressions = $simpleExpressions;
		foreach($simpleExpressions as $key => $scalyrParams)
		{
			if($scalyrParams instanceof Numeric)
			{
				$response = $this->api->NumericQuery($scalyrParams);
				$simpleExpressions[$key] = $response;
			}
			else if ($scalyrParams instanceof TimeSeriesQuery)
			{
				$response = $this->api->TimeSeriesQuery([$scalyrParams]);
				$simpleExpressions[$key] = $response;
			}
		}
		$fullResponse = Parser::NewEvaluateExpression($fullVariableExpression, $simpleExpressions);

		$target = self::ConvertScalyrNumericToGrafana($fullResponse, $queryData->target, $start, $queryData->secondsInterval);
		$target->individualQueries = $individualExpressions;

		return $target;
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Request\TimeSeries $request
	 *
	 * @return \Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeries
	 * @throws \Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function GrafanaToScalyrQuery(\Adknown\ProxyScalyr\Grafana\Request\TimeSeries $request)
	{
		$grafResponse = new \Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeries();

		foreach($request->targets as $targetIndex => $queryData)
		{
			if(!isset(self::QUERY_TYPES[$queryData->type]))
			{
				throw new \Exception("'type' must defined as one of: " . implode("','", array_keys(self::QUERY_TYPES)));
			}

			if(empty($queryData->target))
			{
				throw new \Exception(sprintf("Empty target found for query #%d. All queries must have a target.", $targetIndex + 1));
			}

			switch($queryData->type)
			{
				case 'numeric query':
					$target = $this->GetNumericQueryTarget($request, $queryData);
					break;
				case 'complex numeric query':
					$target = $this->GetComplexQueryTarget($request, $queryData);
					break;
				case 'facet query':
					throw new \Exception("facet queries not yet implemented");
				default:
					throw new \Exception("Unsupported query type: " . $queryData->type);
			}

			$target->refId = $queryData->refId;
			$grafResponse->AddTarget($target);
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
		$endTime = $startTime + $incrementValue;

		foreach($response->values as $value)
		{
			$datapoints[] = [(double)$value, $endTime];
			$endTime += $incrementValue;
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

<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-10-31
 */

namespace Adknown\ProxyScalyr\Controllers;


use Adknown\ProxyScalyr\Grafana\Request\Range;
use Adknown\ProxyScalyr\Grafana\Request\Target;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearchFaker\ElasticSearchMultiSearchResponse;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearchFaker\ElasticSearchResponse;
use Adknown\ProxyScalyr\Logging\LoggerImpl;
use Adknown\ProxyScalyr\StatusCodes;
use Karriere\JsonDecoder\JsonDecoder;

class ElasticScalyrConverter extends Ajax
{
	//Divide milliseconds by these to get desired values
	const MS_TO_HOURS = 3600000;
	const MS_TO_MINUTES = 60000;
	const MS_TO_SECONDS = 1000;

	const REDUCE_INTERVAL_MS = 360;

	const DEFAULT_TIMESERIES_MAXDATAPOINTS = 479;

	/**
	 * @throws \Exception
	 */
	protected function Get()
	{
		$res = [
			"scalyr" => [
				"mappings" => [
					"_doc" => [
						"properties" => [
							"@timestamp" => [
								"type" => "date",
								"format" => "epoch_millis"
							]
						]
					]
				]
			]
		];
		//For some reason, grafana only likes the response if we do it this way >:|
		die(json_encode($res));
	}

	/**
	 * @throws \Exception
	 */
	protected function Put()
	{
		$this->RespondUnsupportedMethod();
	}

	/**
	 * @throws \Exception
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	protected function Post()
	{
		try
		{
			$input = file_get_contents('php://input');
			$decodedRequests = $this->GetDecodedQueries($input, true);

			if($decodedRequests === null)
			{
				throw new \Exception("Unable to decode grafana elasticsearch request");
			}
			$timeSeriesRequest = $this->ConvertElasticSearchRequestToScalyr($decodedRequests);
		}
		catch(\Exception $e)
		{
			$this->response = new ElasticSearchMultiSearchResponse();
			$this->response->message = $e->getMessage();
			$this->Respond(StatusCodes::HTTP_BAD_REQUEST);
		}

		$mid = new Middleware();
		try
		{
			$stuff = $mid->GrafanaToScalyrQuery($timeSeriesRequest);
			$this->response = $this->ConvertScalyrResponseToElasticSearch($stuff);
			$this->Respond(StatusCodes::HTTP_OK);
		}
		catch (\GuzzleHttp\Exception\ClientException $ex)
		{
			LoggerImpl::Exception($ex);
			//get the message out the exception
			list($metaResponse, $response) = explode("response:", $ex->getMessage(), 2);
			$resparray = json_decode($response, true);
			$message = !empty($resparray['message']) ? $resparray['message'] : "Unable to get the error message from scalyr";
			$this->RespondError($message);
		}
		catch (\Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException $ex)
		{
			LoggerImpl::Exception($ex);
			$message = "Selected time interval too small for selected time range. Logic to make multiple Scalyr requests to get the required data points not yet implementeed. " . $ex->getMessage();
			$this->RespondError($message);
		}
	}

	/**
	 * @param $elasticSearchRequests
	 * @return \Adknown\ProxyScalyr\Grafana\Request\TimeSeries
	 * @throws \Exception
	 */
	public function ConvertElasticSearchRequestToScalyr($elasticSearchRequests)
	{
		//Only need the first querie for range info
		$decodedRequest = $elasticSearchRequests[0];

		$timeSeriesRequest = new \Adknown\ProxyScalyr\Grafana\Request\TimeSeries();

		$range = new Range();

		$from = $decodedRequest["aggs"]["2"]["date_histogram"]["extended_bounds"]["min"];
		$to = $decodedRequest["aggs"]["2"]["date_histogram"]["extended_bounds"]["max"];

		$range->from = $this->formatDateFromMilliseconds($from);
		$range->to = $this->formatDateFromMilliseconds($to);

		$timeSeriesRequest->range = $range;

		$intervalMs = (($to - $from) / self::REDUCE_INTERVAL_MS);

		$timeSeriesRequest->interval = ($intervalMs / self::MS_TO_SECONDS) . "s";
		$timeSeriesRequest->maxDataPoints = self::DEFAULT_TIMESERIES_MAXDATAPOINTS;

		//Get targets from all queries
		$targetArray = $this->GetTargetArrayFromQueries($elasticSearchRequests);

		$timeSeriesRequest->targets = $targetArray;


		return $timeSeriesRequest;
	}

	/**
	 * @param \Adknown\ProxyScalyr\Grafana\Response\Query\TimeSeries $scalyrRes
	 * @return ElasticSearchMultiSearchResponse
	 */
	public function ConvertScalyrResponseToElasticSearch($scalyrRes)
	{
		$finalResponse = new ElasticSearchMultiSearchResponse();

		foreach($scalyrRes->GetTargets() as $actualRes)
		{
			$result = ElasticSearchResponse::DefaultResponse();

			$dataPoints = $actualRes->datapoints;

			foreach($dataPoints as $dataPoint)
			{
				$result->AddToResult($dataPoint[1], $dataPoint[0]);
			}

			$finalResponse->AddResponse($result);
		}

		return $finalResponse;
	}

	/**
	 * @param $requests
	 * @return Target[]
	 * @throws \Exception
	 */
	public function GetTargetArrayFromQueries($requests)
	{
		$jsonDecoder = new JsonDecoder();
		$targetArray = [];
		foreach($requests as $request)
		{
			$targetArrayJson = $request["query"]["bool"]["filter"][1]["query_string"]["query"];

			if($targetArrayJson === "*")
			{
				throw new \Exception("Empty query provided");
			}

			/** @var Target $target */
			$target = $jsonDecoder->decode($targetArrayJson, Target::class);
			if(!$target)
			{
				throw new \Exception("Error decoding query field, make sure appropriate characters are escaped");
			}
			$target->percentage = is_null($target->percentage) ? 25 : $target->percentage;
			$target->graphFunction = is_null($target->graphFunction) ? "mean" : $target->graphFunction;
			$target->type = is_null($target->type) ? "complex numeric query" : $target->type;
			$target->secondsInterval = is_null($target->secondsInterval) ? 60 : $target->secondsInterval;
			$target->chosenType = is_null($target->chosenType) ? "minute" : $target->chosenType;
			$target->intervalType = is_null($target->intervalType) ? "window" : $target->intervalType;

			$targetArray[] = $target;
		}

		return $targetArray;
	}

	private function formatDateFromMilliseconds($ms)
	{
		$complete = '';
		$calendar = date("Y-m-d", $ms / 1000);

		$complete .= $calendar . "T";

		$dateTime = new \DateTime();

		$dateTime->setTime(($ms / self::MS_TO_HOURS) % 24, ($ms / self::MS_TO_MINUTES) % 60, ($ms / self::MS_TO_SECONDS) % 60);

		$complete .= $dateTime->format('H:i:s.000') . "Z";

		return $complete;
	}

	public function GetDecodedQueries($json, $assoc = false)
	{
		$objects = [];

		$jsonObjs = explode("\n", $json);

		for($i = 1; $i < count($jsonObjs); $i += 2)
		{
			$objects[] = json_decode($jsonObjs[$i], $assoc);
		}

		return $objects;
	}

	/**
	 * @throws \Exception
	 */
	protected function Delete()
	{
		$this->RespondUnsupportedMethod();
	}
}
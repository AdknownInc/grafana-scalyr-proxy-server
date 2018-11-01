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
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearch\ElasticSearchMultiSearchResponse;
use Adknown\ProxyScalyr\Grafana\Response\ElasticSearch\ElasticSearchResponse;
use Adknown\ProxyScalyr\Logging\LoggerImpl;
use Karriere\JsonDecoder\JsonDecoder;

class ElasticScalyrConverter extends Ajax
{
	protected function Get()
	{
		$this->RespondUnsupportedMethod();
	}

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
		$input = file_get_contents('php://input');
		$decodedRequests = $this->GetDecodedQueries($input, true);

		if($decodedRequests === null)
		{
			throw new \Exception("Unable to decode grafana elasticsearch request");
		}

		$timeSeriesRequest = $this->ConvertElasticSearchRequestToScalyr($decodedRequests);

		$mid = new Middleware();
		try
		{
			$stuff = $mid->GrafanaToScalyrQuery($timeSeriesRequest);
			$this->response = $this->ConvertScalyrResponseToElasticSearch($stuff);
			$this->Respond(200);
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

		$intervalMs = (($to - $from) / 360);

		$timeSeriesRequest->interval = ($intervalMs / 1000) . "s";
		$timeSeriesRequest->maxDataPoints = 479;

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
	 */
	public function GetTargetArrayFromQueries($requests)
	{
		$jsonDecoder = new JsonDecoder();
		$targetArray = [];
		foreach($requests as $request)
		{
			$targetArrayJson = $request["query"]["bool"]["filter"][1]["query_string"]["query"];

			$targetArray[] = $jsonDecoder->decode($targetArrayJson, Target::class);
		}

		return $targetArray;
	}

	private function formatDateFromMilliseconds($ms)
	{
		$complete = '';
		$calendar = date("Y-m-d", $ms / 1000);

		$complete .= $calendar . "T";

		$dateTime = new \DateTime();

		$dateTime->setTime(($ms / (1000*60*60)) % 24, ($ms / (1000*60)) % 60, ($ms / 1000) % 60);

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

	protected function Delete()
	{
		$this->RespondUnsupportedMethod();
	}
}
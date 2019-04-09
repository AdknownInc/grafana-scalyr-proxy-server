<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 04/07/18
 * Time: 6:23 PM
 */

namespace Adknown\ProxyScalyr\Controllers;


use Karriere\JsonDecoder\JsonDecoder;
use Adknown\ProxyScalyr\Grafana\Request\TimeSeries;
use Adknown\ProxyScalyr\Grafana\Request\TimeSeriesTransformer;
use Adknown\ProxyScalyr\Logging\LoggerImpl;

class Query extends Ajax
{

	protected function Get()
	{
		$this->RespondUnsupportedMethod();
	}

	protected function Put()
	{
		$this->RespondUnsupportedMethod();
	}

	protected function Post()
	{
		$jsonDecoder = new JsonDecoder();
		$jsonDecoder->register(new TimeSeriesTransformer());


		$input = file_get_contents('php://input');
		/* @var TimeSeries $timeSeriesRequest*/
		$timeSeriesRequest = $jsonDecoder->decode($input, TimeSeries::class);
		$mid = new Middleware($timeSeriesRequest->useNumeric);
		try
		{
			$stuff = $mid->GrafanaToScalyrQuery($timeSeriesRequest);
			$this->response = $stuff;
			$this->Respond(200);
		}
		catch (\GuzzleHttp\Exception\ClientException $ex)
		{
			$response = $ex->getResponse()->getBody()->getContents();
			$resparray = json_decode($response, true);
			$message = !empty($resparray['message']) ? $resparray['message'] : "Unable to get the error message from scalyr";
			LoggerImpl::Exception($ex, $message, $timeSeriesRequest->GetLoggingInfo());
			$this->RespondError($message);
		}
		catch (\Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException $ex)
		{
			LoggerImpl::Exception($ex, 'Bad Buckets Exception', $timeSeriesRequest->GetLoggingInfo());
			$message = "Selected time interval too small for selected time range. Logic to make multiple Scalyr requests to get the required data points not yet implementeed. " . $ex->getMessage();
			$this->RespondError($message);
		}
	}

	protected function Delete()
	{
		$this->RespondUnsupportedMethod();
	}
}
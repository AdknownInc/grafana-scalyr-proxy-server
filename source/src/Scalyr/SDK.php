<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-09
 * Time: 17:14
 */

namespace Adknown\ProxyScalyr\Scalyr;


use Adknown\ProxyScalyr\Scalyr\Request\Numeric;
use Adknown\ProxyScalyr\Scalyr\Response\FacetResponse;
use Adknown\ProxyScalyr\Scalyr\Response\NumericResponse;
use \Exception;
use GuzzleHttp\Client;

class SDK
{
	const CONTENT_TYPE_JSON = "appliction/json";
	const CONTENT_TYPE_PLAIN = "text/plain";
	const BASE_API_URL = "https://www.scalyr.com/api";

	const QUERY_PAGE_MODE_HEAD = "head";
	const QUERY_PAGE_MODE_TAIL = "tail";


	/** @var Client */
	private $client;
	private $readLogsKey;

	/**
	 * SDK constructor.
	 *
	 * @param string $readLogsKey The scalyr key that has access to the scalyr logs
	 */
	public function __construct(string $readLogsKey)
	{
		$this->client = new Client();
		$this->readLogsKey = $readLogsKey;
	}

	/**
	 * type GET
	 *
	 * @param string $filter
	 * @param int    $maxCount
	 * @param string $pageMode
	 *
	 * @return bool|string
	 * @throws Exception
	 */
	public function Query($filter = "", $maxCount = 100, $pageMode = self::QUERY_PAGE_MODE_TAIL)
	{
		if($maxCount < 1 || $maxCount > 5000)
		{
			throw new Exception("Max count must be between 1 and 5000");
		}

		if($pageMode !== self::QUERY_PAGE_MODE_TAIL && $pageMode !== self::QUERY_PAGE_MODE_HEAD)
		{
			throw new Exception("Pagemode must be one of " . self::QUERY_PAGE_MODE_TAIL . " or " . self::QUERY_PAGE_MODE_HEAD);
		}

		$url = self::BASE_API_URL . "/query";
		$params = [
			"token"     => $this->readLogsKey,
			"queryType" => "log",
			"filter"    => urlencode($filter),
			"maxCount"  => $maxCount,
			"pageMode"  => $pageMode,
			"priority"  => "low"
		];


		$url .= "?";
		foreach($params as $key => $value)
		{
			$url .= "$key=$value&";
		}
		$url = substr($url, 0, strlen($url) - 1);
		$response = file_get_contents($url);

		return json_decode($response, true);
	}

	/**
	 * type GET
	 *
	 * @param Numeric $request
	 *
	 * @return NumericResponse
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function NumericQuery(Numeric $request)
	{
		$url = self::BASE_API_URL . "/numericQuery";
		$params = [
			"token"     => $this->readLogsKey,
			"queryType" => $request::QUERY_TYPE,
			"filter"    => $request->filter,
			"function"  => $request->function,
			"startTime" => $request->startTime,
			"endTime"   => $request->endTime,
			"buckets"   => $request->buckets,
			"priority"  => $request->priority
		];

		$response = $this->client->request('GET', $url, [
			'query' => $params
		]);

		return new NumericResponse($response->getBody()->getContents());
	}

	/**
	 * type GET
	 *
	 * @param $filter
	 * @param $field
	 * @param $maxCount
	 * @param $startTime
	 * @param $endTime
	 *
	 * @return FacetResponse
	 * @throws Exception
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	public function FacetQuery($filter, $field, $maxCount, $startTime, $endTime)
	{
		if($maxCount < 1 || $maxCount > 1000)
		{
			throw new Exception("maxCount must be between 1 and 1000");
		}

		$url = self::BASE_API_URL . "/facetQuery";
		$params = [
			"token"     => $this->readLogsKey,
			"queryType" => "facet",
			"filter"    => $filter,
			"field"     => $field,
			"maxCount"  => $maxCount,
			"startTime" => $startTime,
			"endTime"   => $endTime,
			"priority"  => "low"
		];

		$response = $this->client->request('GET', $url, [
			'query' => $params
		]);
		$body = $response->getBody()->getContents();

		return new FacetResponse($body);
	}
}
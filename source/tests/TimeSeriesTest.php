<?php
/**
 *
 *
 * @author Stewart Thomson.
 * @since  2018-11-12
 */

namespace Adknown\ProxyScalyr\Tests;

use Adknown\ProxyScalyr\Grafana\Request\TimeSeries;
use Adknown\ProxyScalyr\Grafana\Request\TimeSeriesTransformer;
use Karriere\JsonDecoder\JsonDecoder;
use PHPUnit\Framework\TestCase;

class TimeSeriesTest extends TestCase
{
	const STANDARD_REQUEST_FILE = __DIR__ . "/ScalyrRequests/StandardRequest.json";

	/* @var JsonDecoder */
	private $jsonDecoder;

	public function testRequestIdentifyingInfo()
	{
		$input = file_get_contents(self::STANDARD_REQUEST_FILE);
		/* @var TimeSeries $timeSeriesRequest*/
		$timeSeriesRequest = $this->jsonDecoder->decode($input, TimeSeries::class);
		$expected = [
			'Username' => 'admin',
			'User Id' => 1,
			'Org name' => 'Main Org.',
			'Org Id' => 1,
			'Panel Name' => 'Panel Title',
			'Panel Id' => 2,
		];
		$this->assertEquals($expected, $timeSeriesRequest->GetLoggingInfo());
	}

	public function setUp()
	{
		$this->jsonDecoder = new JsonDecoder();
		$this->jsonDecoder->register(new TimeSeriesTransformer());
	}
}

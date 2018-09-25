<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 25/09/18
 * Time: 11:22 AM
 */

namespace Adknown\ProxyScalyr\Scalyr\Request\Exception;


use Adknown\ProxyScalyr\Scalyr\Request\Numeric;
use Throwable;

class BadBucketsException extends \Exception
{
	const MESSAGE_FORMAT = "attempted to use %d buckets. Scalyr allows between %d and %d buckets in their api calls.";

	public function __construct(int $attemptedBuckets, string $appendedMessage = "", int $code = 0, Throwable $previous = null)
	{
		$message = sprintf(self::MESSAGE_FORMAT, $attemptedBuckets, Numeric::BUCKETS_MIN, Numeric::BUCKETS_MAX);
		parent::__construct($message . $appendedMessage, $code, $previous);
	}
}
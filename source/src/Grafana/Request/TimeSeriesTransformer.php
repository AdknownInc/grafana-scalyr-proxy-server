<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 06/07/18
 * Time: 5:49 PM
 */

namespace Adknown\ProxyScalyr\Grafana\Request;


use Karriere\JsonDecoder\Bindings\ArrayBinding;
use Karriere\JsonDecoder\Bindings\FieldBinding;
use Karriere\JsonDecoder\ClassBindings;
use Karriere\JsonDecoder\Transformer;

class TimeSeriesTransformer implements Transformer
{
	public function register(ClassBindings $classBindings)
	{
		$classBindings->register(new FieldBinding('range', 'range', Range::class));
		$classBindings->register(new ArrayBinding('targets', 'targets', Target::class));
	}

	public function transforms()
	{
		return TimeSeries::class;
	}
}
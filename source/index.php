<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-11
 * Time: 18:57
 */

require __DIR__ . '/vendor/autoload.php';

$parsed = parse_url($_SERVER['REQUEST_URI']);

header("Access-Control-Allow-Origin", "*");

try
{
	switch($parsed['path'])
	{
		//Just to satisfy the test when setting the datasource
		case '/scalyr/_mapping':
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
			die(json_encode($res));
		case '/_msearch':
			$controller = new \Adknown\ProxyScalyr\Controllers\ElasticScalyrConverter();
			$controller->HandleRequest();
			break;
		case '/':
			die(200);
		case '/query':
			$controller = new \Adknown\ProxyScalyr\Controllers\Query();
			$controller->HandleRequest();
			break;
		case '/search':
			$body = json_decode(file_get_contents('php://input'), true);
			\Adknown\ProxyScalyr\Logging\LoggerImpl::Log("search called", $body);
			die(json_encode([
				"not_yet", "implemented"
			]));
			break;
		case '/annotations':
			break;
		case '/tag-keys':
			break;
		case '/tag-values':
			break;
		default:
	}
}
catch(Exception $ex)
{
	\Adknown\ProxyScalyr\Logging\LoggerImpl::Exception($ex);
	header('Content-type: application/json;charset=utf-8');
	http_response_code(500);
	die(json_encode(['message' => 'Unexpected error occurred. Contact your grafana administrator.']));
}


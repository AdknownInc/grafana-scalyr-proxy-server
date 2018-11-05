<?php
/**
 * Created by PhpStorm.
 * User: Matt Jourard
 * Date: 2018-01-11
 * Time: 19:05
 */

namespace Adknown\ProxyScalyr\Utilities;


class Utilities
{
	const DUMP_DIRECTORY = __DIR__ . '/../dump';

	// Gets the user's user agent string if it exists as a server variable
	public static function GetUserAgent()
	{
		$ua = (isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : null;

		return $ua;
	}

	/**
	 * Gets the current user's IP address
	 *
	 * @param bool $reverseProxy If this code is behind a reverse proxy then the IP will be in HTTP_X_FORWARDED_FOR
	 * @param bool $cleanIp      If multiple IP's are comma separated, choose the most correct one
	 *
	 * @return mixed|null
	 */
	public static function GetUserIP($reverseProxy = true, $cleanIp = true)
	{
		$ip = null;

		if($reverseProxy)
		{
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']))
			{
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else
			{
				$ip = $_SERVER['REMOTE_ADDR'];
			}
		}
		else
		{
			$ip = $_SERVER['REMOTE_ADDR'];
		}

		if($cleanIp)
		{
			return self::CleanIpAddress($ip);
		}
		else
		{
			return $ip;
		}
	}

	/**
	 * If an IP address has multiple IP's comma separated, choose the most correct one
	 *
	 * @param string $ipAddress The reported IP address(s) for the user
	 *
	 * @return string The cleaned IP address
	 */
	public static function CleanIpAddress($ipAddress)
	{
		$arr = explode(',', str_replace(" ", "", $ipAddress));

		if(count($arr) == 1)
		{
			return $arr[0];
		}

		//Remove local IP and local subnet
		$final = array();

		//Filter out 127.*, 192.168.*, 10.*, 244.*, 0.*
		foreach($arr as $ip)
		{
			if(stripos($ip, '127.') === 0
				|| stripos($ip, '192.168.') === 0
				|| stripos($ip, '10.') === 0 || stripos($ip, '010.') === 0
				|| stripos($ip, '0.') === 0 || stripos($ip, '00.') === 0 || stripos($ip, '000.') === 0
				|| stripos($ip, '224.') === 0
			)
			{
				continue;
			}

			$final[] = $ip;
		}

		if(count($final) > 0)
		{
			$cleanIp = array_values($final)[0];
		}
		else
		{
			$cleanIp = array_pop($arr);
		}

		return $cleanIp;
	}

	/**
	 * Returns the current page url
	 *
	 * @return string The page URL
	 */
	public static function GetCurrentPageUrl()
	{
		$pageURL = 'http';
		if(self::IsSsl())
		{
			$pageURL .= "s";
		}

		$pageURL .= "://";

		if($_SERVER["SERVER_PORT"] != "80" && $_SERVER["SERVER_PORT"] != "443")
		{
			$pageURL .= $_SERVER['HTTP_HOST'] . ":" . $_SERVER["SERVER_PORT"];
		}
		else
		{
			$pageURL .= $_SERVER['HTTP_HOST'];
		}

		$pageURL .= $_SERVER["REQUEST_URI"];

		return $pageURL;
	}

	/**
	 * Checks if the current request came from SSL.  This handles CloudFront and ELB
	 *
	 * v1.0 - 2017-05-11 - Andrew Gorrie
	 *      - First
	 *
	 * @return bool True if SSL otherwise false
	 */
	public static function IsSsl()
	{
		//grab the https headers
		$https = 'off';
		$port = $_SERVER['SERVER_PORT'];

		if(isset($_SERVER['HTTPS']))
		{
			$https = $_SERVER['HTTPS'];
		}

		//Check for an ELB or other load balancer
		if(isset($_SERVER['HTTP_X_FORWARDED_PROTO']))
		{
			$https = $_SERVER['HTTP_X_FORWARDED_PROTO'];
		}

		if(isset($_SERVER['HTTP_X_FORWARDED_PORT']))
		{
			$port = $_SERVER['HTTP_X_FORWARDED_PORT'];
		}

		//Check if this is served by CloudFront
		if(isset($_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO']))
		{
			$https = $_SERVER['HTTP_CLOUDFRONT_FORWARDED_PROTO'];
			$port = '443';
		}

		return ($https === 'on' || $https == 'https') && $port === '443';
	}

}
<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 23/10/18
 * Time: 2:53 PM
 */


//must match this version
const PATTERNS = [
	'/^7.1/',
	'/^7.2/'
];
$matched = false;
foreach(PATTERNS as $pattern)
{
	preg_match($pattern, phpversion(), $matches);
	if (!empty($matches))
	{
		$matched = true;
	}
}
if (!$matched)
{
	die("\n****WRONG PHP VERSION DETECTED!*****\n");
}

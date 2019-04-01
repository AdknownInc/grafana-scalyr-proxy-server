<?php
/**
 * Copyright  2018 Adknown Inc. All rights reserved.
 * Created:   January 31st
 * Author:    Bradley Leonard (Co-op)
 * Project:   Proxy Scalyr API
 */

namespace Adknown\ProxyScalyr\Scalyr\ComplexExpressions;

use Adknown\ProxyScalyr\Logging\LoggerImpl;
use Adknown\ProxyScalyr\Scalyr\Request\Numeric;
use Adknown\ProxyScalyr\Scalyr\Request\TimeSeriesQuery;
use Adknown\ProxyScalyr\Scalyr\Response\NumericResponse;

class Parser
{

	const PRECEDENCE_LEVEL_1 = 1;
	const PRECEDENCE_LEVEL_2 = 2;

	const FLOAT_CUTOFF = 0.0001;
	const DIVIDE_BY_ZERO_RESULT = -1;

	const VALUE_INDEX = 0;
	const TIMESTAMP_INDEX = 1;

	const REPLACE_PREFIX = 'var';

	/**
	 * @param        $expression
	 * @param        $start
	 * @param        $end
	 * @param        $buckets
	 * @param string $fullVariableExpression
	 *
	 * @return array
	 * @throws \Adknown\ProxyScalyr\Scalyr\Request\Exception\BadBucketsException
	 */
	public static function ParseComplexExpression($expression, $start, $end, $buckets, &$fullVariableExpression, $useNumeric)
	{
		/*
		 * Match graph public static function calls
		 * Part 1
		 * (count|rate|mean|min|max|sumPerSecond|median|fraction|p|p\\[\\d+\\]|p\\d+) match any of the graph function
		 * keywords (One of count, rate, mean, min, max, sumPerSecond, median, fraction, p, p[##] or p##)
		 * p\\[\\d+\\] matches p the left [ and one or more digits the right ]
		 * p\\d+ matches p and one or more digits
		 * Part 2
		 * \\((.*?)\\) matches left ( and everything inside up until right ) in a non greedy way
		 */
		$graphFunctionRegex = "[(?<function>count|rate|mean|min|max|sumPerSecond|median|fraction|p|p\\[\\d+\\]|p\\d+)\\(((?<field>[a-zA-Z]+) where )?(?<filter>.*?)\\)]";
		/*
		 * Match any constant
		 * \\b  boundry on the left side
		 * \\d+ one or more digits
		 * \\b  boundry on the right side
		 */
		$constantNumberRegex = "[\\b\\d+\\b]";

		//Replace all API calls with a placeholder with var prefix
		$varCount = 0;
		$foundCount = 0;
		$varArray = [];
		//Match all scalyr parts
		do
		{
			$replaceString = self::REPLACE_PREFIX . $varCount;
			if(preg_match($graphFunctionRegex, $expression, $matches))
			{
				LoggerImpl::DebugLog('', $matches);
				$filter = $matches['filter'];
				$graphFunction = $matches['function'];
				$field = empty($matches['field']) ? '' : $matches['field'];
				//Get the type of query based of of keyword

				if ($useNumeric === true)
				{
					$varArray[$varCount] = new Numeric(
						$filter,
						$graphFunction,
						$field,
						$start,
						$end,
						$buckets
					);
				}
				else
				{
					$varArray[$varCount] = new TimeSeriesQuery(
						$filter,
						$graphFunction,
						$field,
						$start,
						$end,
						$buckets
					);
				}
				$varCount++;
			}
			$expression = preg_replace($graphFunctionRegex, $replaceString, $expression, 1, $foundCount);
		} while($foundCount !== 0);

		//Replace all constants with a placeholder with var prefix
		$foundCount = 0;
		//Match all constants
		do
		{
			$replaceString = self::REPLACE_PREFIX . $varCount;
			if(preg_match($constantNumberRegex, $expression, $matches))
			{
				$varArray[$varCount] = $matches[0];
				$varCount++;
			}
			$expression = preg_replace($constantNumberRegex, $replaceString, $expression, 1, $foundCount);
		} while($foundCount !== 0);

		$fullVariableExpression = $expression;

		return $varArray;
	}



	/**
	 * infixToRPN changes infix notation equation in to an array in reverse polish notation
	 * @see http://andreinc.net/2010/10/05/converting-infix-to-rpn-shunting-yard-algorithm/
	 * @see https://en.wikipedia.org/wiki/Reverse_Polish_notation
	 *
	 * @param array $inputExpressions
	 *
	 * @return \SplQueue the expression in RPN
	 */
	private static function ConvertInfixNotationToReversePolishNotation(array $inputExpressions)
	{
		$stack = new \SplStack();
		$output = new \SplQueue();

		//Change iterate though infix and change the order to RPN
		foreach($inputExpressions as $token)
		{
			if(self::isOperator($token))
			{
				while(!$stack->isEmpty() && self::isOperator($stack->top()))
				{
					if(self::precedenceCompare($token, $stack->top()) <= 0)
					{
						$output->push($stack->pop());
						continue;
					}
					break;
				}
				$stack->push($token);
			}
			else
			{
				if($token === "(")
				{
					$stack->push($token);
				}
				else
				{
					if($token === ")")
					{
						while(!$stack->isEmpty() && $stack->top() !== "(")
						{
							$output->push($stack->pop());
						}
						$stack->pop();
					}
					else
					{
						$output->push($token);
					}
				}
			}
		}
		//Empty stack
		while(!$stack->isEmpty())
		{
			$output->push($stack->pop());
		}

		return $output;
	}

	/**
	 * isOperator Figures out whether the input character is an operator
	 *
	 * @param $inputOperator
	 *
	 * @return bool whether the input is an operator
	 */
	private static function isOperator($inputOperator)
	{
		switch($inputOperator)
		{
			case '+':
			case '-':
			case '*':
			case '/':
				return true;
			default:
				return false;
		}
	}

	/**
	 * precedenceCompare Compares the precedence for two operators so they can follow BEDMAS
	 *
	 * @param $firstToken  string the first operator
	 * @param $secondToken string the second operator
	 *
	 * @return int a number representing the precedence level
	 */
	private static function precedenceCompare($firstToken, $secondToken)
	{
		//Higher precedence operators
		$higherPrecedence = [
			'*' => 0,
			'/' => 0
		];
		//Set the first precedence
		$firstPrecedence = isset($higherPrecedence[$firstToken]) ? self::PRECEDENCE_LEVEL_2 : self::PRECEDENCE_LEVEL_1;

		//Set the second precedence
		$secondPrecedence = isset($higherPrecedence[$secondToken]) ? self::PRECEDENCE_LEVEL_2 : self::PRECEDENCE_LEVEL_1;

		return $firstPrecedence - $secondPrecedence;
	}

	/**
	 * evaluateExpression runs through all the operands and operators and calls operations on them
	 *
	 * @param string $expression operators and operands in RPN order
	 * @param array  $varArray   values to be put back into the equation
	 *
	 * @return mixed a single object representing the result of the expression
	 */
	public static function NewEvaluateExpression($expression, $varArray)
	{
		$expression = str_replace([" ", "v", "a", "r"], "", $expression);

		$rpnExpression = self::ConvertInfixNotationToReversePolishNotation(str_split($expression));

		LoggerImpl::DebugLog('', $rpnExpression);

		$stack = new \SplStack();
		foreach($rpnExpression as $token)
		{
			if(self::isOperator($token))
			{
				//Operator
				$result = self::performOperation($token, $stack->pop(), $stack->pop());
				$stack->push($result);
			}
			else
			{
				//Operand
				$stack->push($varArray[(int)$token]);
				continue;
			}
		}

		return $stack->pop();
	}

	/**
	 * performOperation calls the appropriate equation logic given the operator
	 *
	 * @param string    $operator  operator  of the equation
	 * @param NumericResponse|int $firstVar  first operand of the equation
	 * @param NumericResponse|int $secondVar second operand of the equation
	 *
	 * @return array|float|int|string a single object representing the result of the expression
	 */
	private static function performOperation($operator, $firstVar, $secondVar)
	{
		switch($operator)
		{
			case '+':
				return self::logic($firstVar, $secondVar, self::class . '::add');
			case '-':
				return self::logic($firstVar, $secondVar, self::class . '::sub');

			case '/':
				return self::logic($firstVar, $secondVar, self::class . '::div');

			case '*':
				return self::logic($firstVar, $secondVar, self::class . '::mul');
			default:
				return -1;
		}
	}

	private static function add($a, $b)
	{
		return $a + $b;
	}

	private static function sub($a, $b)
	{
		return $a - $b;
	}

	private static function div($a, $b)
	{
		return ($b === 0 || $b < self::FLOAT_CUTOFF) ? self::DIVIDE_BY_ZERO_RESULT : $a / $b;
	}

	private static function mul($a, $b)
	{
		return $a * $b;
	}

	/**
	 * addLogic all logic for addition of the 4 possible query cases
	 *
	 * @param          $firstVar  NumericResponse|int first operand of the equation
	 * @param          $secondVar NumericResponse|int second operand of the equation
	 * @param callback $op        second operand of the equation
	 *
	 * @return NumericResponse|int a single object representing the result of the expression
	 */
	private static function logic($firstVar, $secondVar, Callable $op)
	{
		//Both are queries
		if($firstVar instanceof NumericResponse && $secondVar instanceof NumericResponse)
		{
			//Prepare response
			$response = clone $firstVar;
			foreach($firstVar->values as $index => $datapoint)
			{
				$response->values[$index] = $op($secondVar->values[$index], $datapoint);
			}

			return $response;
		}


		//Constant and query or constant and constant


		//First is constant
		if(is_numeric($firstVar) && $secondVar instanceof NumericResponse)
		{
			//Prepare response
			$response = clone $secondVar;

			foreach($secondVar->values as $index => $datapoint)
			{
				$response->values[$index] = $op($datapoint, $firstVar);
			}

			return $response;
		}

		//Second is constant
		if($firstVar instanceof NumericResponse && is_numeric($secondVar))
		{
			//Prepare response
			$response = clone $firstVar;

			foreach($response->values as $index => $datapoint)
			{
				$response->values[$index] = $op($secondVar, $datapoint);
			}

			return $response;
		}


		//Both are constant
		return $op($secondVar, $firstVar);
	}
}
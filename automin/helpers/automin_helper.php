<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * AutoMin helper functions
 *
 * @package     ExpressionEngine
 * @category    Module
 * @author      Matthew Kirkpatrick
 * @copyright   Copyright (c) 2020, Matthew Kirkpatrick
 * @link        https://github.com/javashakes
 */

/**
 * Debug
 *
 * @param       mixed
 * @param       bool
 * @return      void
 */
if ( ! function_exists('automin_dump'))
{
	function automin_dump($var, $exit = TRUE)
	{
		echo '<pre>'.print_r($var, TRUE).'</pre>';
		if ($exit) exit;
	}
}

// --------------------------------------------------------------

/* End of file automin_helper.php */

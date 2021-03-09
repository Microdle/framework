<?php  
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * SQL default exception. Occurs when an SQL error appears.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class SqlException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 500;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Internal Server Error';
}
?>
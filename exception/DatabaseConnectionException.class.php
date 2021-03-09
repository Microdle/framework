<?php 
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Database connection exception. Occurs when connection with database is impossible.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class DatabaseConnectionException extends \Exception {
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
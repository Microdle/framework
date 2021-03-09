<?php  
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Method not available exception. Occurs when a method must be implemented but which is not available.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class MethodNotAvailableException extends \Exception {
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
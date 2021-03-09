<?php  
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Authentication exception. Occurs when login or password is invalid.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class AuthenticationException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 401;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Unauthorized';
}
?>
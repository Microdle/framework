<?php  
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Service unavailable exception. Occurs when the server cannot handle the request (because it is overloaded or down for maintenance). Generally, this is a temporary state.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class ServiceUnavailableException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 503;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Service Unavailable';
}
?>
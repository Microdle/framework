<?php  
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Not implemented request method exception. Occurs when the server either does not recognize the request method, or it lacks the ability to fulfil the request.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class NotImplementedException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 501;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Not Implemented';
}
?>
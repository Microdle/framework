<?php 
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Form data exception. Occurs when a request data (from query or form data) is not expected.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class FormDataException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 412;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Precondition Failed';
}
?>
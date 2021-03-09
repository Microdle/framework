<?php 
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * File unfound exception. Occurs when a resource is not found.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class NotFoundException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 404;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Not Found';
}
?>
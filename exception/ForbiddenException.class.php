<?php  
namespace com\microdle\exception;

/**
 * Microdle Framework - https://microdle.com/
 * Forbidden exception. Occurs when a permission is denied on a resource.
 * @author Vincent SOYSOUVANH
 * @package com.microdle.exception
 * @license https://github.com/microdlephp/microdle/blob/master/LICENSE (MIT License)
 */
class ForbiddenException extends \Exception {
	/**
	 * The exception code.
	 * @var int
	 */
	protected $code = 403;

    /**
	 * The exception message.
	 * @var string
	 */
    protected $message = 'Forbidden';
}
?>
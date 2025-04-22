<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Exceptions;

/**
 * Class UserException.
 *
 * Base class for user-defined exceptions in the application. Ensures that user-defined error codes start from 5000.
 * Developers can use this class directly or extend it to create custom exceptions.
 */
class UserException extends ApplicationException
{
    /**
     * The base offset for user-defined error codes.
     */
    private const BASE_CODE_OFFSET = 5000;

    /**
     * Constructor for UserException.
     *
     * @param int             $code     the user-defined error code (will be offset by 5000)
     * @param string          $message  the error message
     * @param null|\Throwable $previous the previous exception, if any
     */
    public function __construct(int $code, string $message, ?\Throwable $previous = null)
    {
        $this->applicationCode = self::BASE_CODE_OFFSET + $code;
        parent::__construct($message, $previous);
    }
}

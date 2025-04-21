<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Core\HelpRow;
use Vasoft\VersionIncrement\Exceptions\ApplicationException;

/**
 * Interface ApplicationHandlerInterface.
 *
 * Defines the contract for application handlers. Handlers are responsible for processing command-line arguments,
 * executing specific logic, and providing help information for their functionality.
 */
interface ApplicationHandlerInterface
{
    /**
     * Handles the execution of the handler based on the provided command-line arguments.
     *
     * This method processes the input arguments and performs the necessary actions for the handler.
     * The behavior of this method depends on the type of handler:
     *
     * - If the handler is responsible for displaying help or other auxiliary information, it may return an exit code (e.g., `0`).
     * - If the handler does not handle the request (e.g., the arguments do not match its responsibility), it returns `null`.
     * - If the handler is responsible for the main functionality (e.g., running version updates), it typically returns `null`
     *   to indicate that the script should continue executing. In case of errors, an exception is thrown instead.
     *
     * @param array $argv the command-line arguments passed to the application
     *
     * @return null|int the exit code to terminate the application with, or `null` if:
     *                  - The handler does not handle the request, or
     *                  - The handler is part of the main functionality and does not require interrupting the script execution
     *
     * @throws ApplicationException
     */
    public function handle(array $argv): ?int;

    /**
     * Retrieves the help information for the handler.
     *
     * This method provides an array of `HelpRow` objects that describe the functionality and usage of the handler.
     * The help information is used to display usage instructions when the user requests help (e.g., via a `--help` flag).
     *
     * @return HelpRow[] An array of `HelpRow` objects representing the help information for the handler.
     *                   Each `HelpRow` contains details such as the section, key, and description.
     */
    public function getHelp(): array;
}

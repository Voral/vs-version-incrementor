<?php

declare(strict_types=1);

namespace Vasoft\VersionIncrement\Contract;

use Vasoft\VersionIncrement\Events\Event;
use Vasoft\VersionIncrement\Exceptions\ApplicationException;

/**
 * Interface EventListenerInterface.
 *
 * Defines the contract for event listeners in the application. Listeners are responsible for handling specific events
 * dispatched by the event bus. Each listener must implement the `handle` method to process events and perform the
 * necessary actions.
 *
 * The `handle` method may throw an `ApplicationException` if an error occurs during event processing. This allows the
 * application to handle exceptional cases gracefully and provide meaningful feedback to the user.
 */
interface EventListenerInterface
{
    /**
     * Handles an event dispatched by the event bus.
     *
     * This method is called when an event is dispatched, and it should contain the logic to process the event.
     * The implementation may include actions such as logging, modifying application state, or triggering additional
     * processes.
     *
     * @param Event $event the event object containing information about the event, including its type and data
     *
     * @throws ApplicationException If an error occurs during event processing. This exception can be used to signal
     *                              issues such as invalid event data, failed operations, or other unexpected conditions.
     */
    public function handle(Event $event): void;
}

<?php declare(strict_types=1);

namespace ErrorHandlerModule\Logger;

use Tracy\ILogger;

/**
 * Interface IFilterLogger
 */
interface IFilterLogger extends ILogger
{
    /**
     * Splňuje logovaná zpráva podmínku tohoto loggeru?
     */
    public function isMatch(mixed $message): bool;
}

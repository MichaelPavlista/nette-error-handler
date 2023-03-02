<?php declare(strict_types=1);

namespace ErrorHandlerModule\Logger;

use Nette\Database;

/**
 * Class DeadlockLogger
 * @package ErrorHandlerModule
 */
class DeadlockLogger extends FilterLogger
{
    /** @const string priorita chyby typu deadlock */
    public const DEADLOCK = 'deadlock';

    /** @var string|null přetížení priority logovaných zpráv přes tento logger */
    protected $overridePriority = self::DEADLOCK;


    /**
     * Jedná se o MySQL chybu deadlock?
     * @param mixed $message
     * @return bool
     */
    public function isMatch($message): bool
    {
        if($message instanceof Database\DriverException)
        {
            return $message->getDriverCode() === 1213;
        }

        return false;
    }
}

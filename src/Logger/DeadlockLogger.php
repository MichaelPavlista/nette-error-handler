<?php declare(strict_types=1);

namespace ErrorHandlerModule\Logger;

use Nette\Database;

/**
 * Class DeadlockLogger
 */
class DeadlockLogger extends FilterLogger
{
    /** @const string priorita chyby typu deadlock */
    public const DEADLOCK = 'deadlock';

    /** @const int MySQL error kód uváznutí transakcí */
    private const MYSQL_DEADLOCK_CODE = 1213;


    /** @var string|null přetížení priority logovaných zpráv přes tento logger */
    protected ?string $overridePriority = self::DEADLOCK;


    /**
     * Jedná se o MySQL chybu deadlock?
     */
    public function isMatch(mixed $message): bool
    {
        if($message instanceof Database\DriverException)
        {
            return (int) $message->getDriverCode() === self::MYSQL_DEADLOCK_CODE;
        }

        return false;
    }
}

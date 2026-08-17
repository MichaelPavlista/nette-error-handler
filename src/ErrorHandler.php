<?php declare(strict_types=1);

namespace ErrorHandlerModule;

use InvalidArgumentException;
use LogicException;
use Throwable;
use Tracy;

/**
 * Class ErrorHandler
 */
final class ErrorHandler
{
    /** @const string cesta k výchozí šabloně chyby */
    public const DEFAULT_ERROR_TEMPLATE = __DIR__ . '/error.500.phtml';


    /** @var bool byl error handler zaregistrován? */
    private static bool $registered = false;

    /** @var string cesta k šabloně chyby zaregistrované do Tracy */
    private static string $errorTemplate = self::DEFAULT_ERROR_TEMPLATE;

    private static ?LogDispatcher $logDispatcher = null;

    /** @var (callable(Tracy\ILogger): ?Tracy\Logger)|null funkce extrahující standardní Tracy logger z vlastního loggeru */
    private static $loggerResolver = null;


    /**
     * Zaregistruje error handler do Nette
     * @param string $errorTemplate cesta k výchozí šabloně chyby
     */
    public static function register(string $errorTemplate = self::DEFAULT_ERROR_TEMPLATE): void
    {
        // Error handler je možné zaregistrovat do Nette pouze jednou
        if(self::$registered)
        {
            throw new LogicException('ErrorHandler is already registered');
        }

        // Kontrola a zaregistrování šablony výchozí chyby do Tracy
        if(!file_exists($errorTemplate) || !is_readable($errorTemplate))
        {
            throw new InvalidArgumentException(sprintf('Error template %s is missing or not readable', $errorTemplate));
        }

        self::$errorTemplate = $errorTemplate;

        Tracy\Debugger::$errorTemplate = $errorTemplate;

        // Zaregistrování callbacku, který se zavolá po kritické chybě v aplikaci
        Tracy\Debugger::$onFatalError[] = self::onFatalError(...);

        // Error handler byl úspěšně inicializován
        self::$registered = true;
    }

    /**
     * Nastaví funkci, která se pokusí ze zaregistrovaného loggeru Tracy získat standardní Tracy\Logger.
     * Použije se pouze v případě, že logger nastavený v Tracy sám o sobě není Tracy\Logger
     * (typicky když aplikace standardní logger obaluje vlastní implementací).
     * Funkce vrací Tracy\Logger, nebo null pokud jej z předaného loggeru nelze získat.
     * @param (callable(Tracy\ILogger): ?Tracy\Logger)|null $loggerResolver
     */
    public static function setLoggerResolver(?callable $loggerResolver): void
    {
        self::$loggerResolver = $loggerResolver;
    }

    /**
     * Vrací standardní Tracy logger zaregistrovaný v Tracy, nebo null pokud jej nelze získat
     */
    public static function getTracyLogger(): ?Tracy\Logger
    {
        // Načteme Tracy loger a pokud je standardní, vrátíme ho.
        $logger = Tracy\Debugger::getLogger();

        if($logger instanceof Tracy\Logger)
        {
            return $logger;
        }

        // Pokud není definovaný logger resolver, vrátíme null.
        if(self::$loggerResolver === null)
        {
            return null;
        }

        // Pokud je definovaný logger resolver, zavoláme ho.
        // Metoda se volá i při vykreslování chybové stránky, selhání resolveru nesmí vykreslení shodit.
        try
        {
            $resolvedLogger = (self::$loggerResolver)($logger);

            return $resolvedLogger instanceof Tracy\Logger
                ? $resolvedLogger
                : null;
        }
        catch(Throwable)
        {
            return null;
        }
    }

    /**
     * Aktivuje a integruje vylepšený error logger do Tracy který umožňuje definovat pro jakou chybu se použije jaký ILogger
     */
    public static function activateLogDispatcher(): LogDispatcher
    {
        if(!self::$logDispatcher)
        {
            self::$logDispatcher = new LogDispatcher(
                Tracy\Debugger::$logDirectory,
                Tracy\Debugger::$email,
                Tracy\Debugger::getBlueScreen(),
            );
            // nette back compatibility
            self::$logDispatcher->directory = &Tracy\Debugger::$logDirectory;
            self::$logDispatcher->email = &Tracy\Debugger::$email;

            // Přeneseme nastavení ze standartního Tracy Loggeru
            $tracyLogger = self::getTracyLogger();

            if($tracyLogger !== null)
            {
                self::$logDispatcher->fromEmail = $tracyLogger->fromEmail;
                self::$logDispatcher->emailSnooze = $tracyLogger->emailSnooze;
                self::$logDispatcher->mailer = $tracyLogger->mailer;
            }
        }

        Tracy\Debugger::setLogger(self::$logDispatcher);

        return self::$logDispatcher;
    }

    /**
     * Vrací název souboru s uloženou chybou ve formátu html (přes Tracy)
     * Tato funkce soubor nevytváří!
     */
    public static function getErrorFile(Throwable $error): string
    {
        $tracyLogger = self::getTracyLogger();

        if($tracyLogger !== null && $tracyLogger->directory)
        {
            return basename($tracyLogger->getExceptionFile($error));
        }

        return '';
    }

    /**
     * Funkce která se zavolá po kritické chybě v aplikaci
     * @internal
     */
    public static function onFatalError(Throwable $error): void
    {
        if(Tracy\Debugger::$errorTemplate !== self::$errorTemplate)
        {
            throw new LogicException('Tracy error template changed, error handler is disabled');
        }
    }

    /**
     * Ruční vykreslení zadané kritické chyby
     */
    public static function renderError(Throwable $error, bool $logged): void
    {
        self::onFatalError($error);

        // Šablona se vykresluje v izolovaném scope, kde vidí právě jen proměnné $exception a $logged.
        $renderTemplate = static function (Throwable $exception, bool $logged): void {
            require self::$errorTemplate;
        };

        $renderTemplate($error, $logged);
    }
}

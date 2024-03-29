<?php declare(strict_types=1);

namespace ErrorHandlerModule;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;
use Throwable;

/**
 * Class ErrorPresenter
 * @package ErrorHandler
 */
class ErrorPresenter implements Nette\Application\IPresenter
{
    use Nette\SmartObject;

    /** @var ILogger */
    private $logger;


    /**
     * ErrorPresenter constructor.
     * @param ILogger $logger
     */
    public function __construct(ILogger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Funkce umožňující modifikovat application request aby směřoval na zadaný presenter a akci
     * @param Nette\Application\Request $request
     * @param string $presenterName
     * @param string $action
     * @return Nette\Application\Request
     */
    final public function modifyRequest(Nette\Application\Request $request, string $presenterName, string $action = ''): Nette\Application\Request
    {
        $request->setPresenterName($presenterName);

        if($action)
        {
            $requestParametrs = $request->getParameters();
            $requestParametrs[Nette\Application\UI\Presenter::ACTION_KEY] = $action;

            $request->setParameters($requestParametrs);
        }

        return $request;
    }


    /**
     * Funkce zajišťující vyhodnocení a přesměrování na presenter se zobrazením chyby 4xx
     * @param Nette\Application\BadRequestException $exception
     * @param Nette\Application\Request $request
     * @return Nette\Application\Response
     */
    public function handleBadRequestException(Nette\Application\BadRequestException $exception, Nette\Application\Request $request) : Nette\Application\Response
    {
        if(PHP_SAPI === 'cli')
        {
            return new Responses\TextResponse('404: ' . $exception->getMessage() . "\n");
        }

        // Předáme požadavek na výchozí error presenter Nette
        return new Responses\ForwardResponse($request->setPresenterName('Nette:Error'));
    }


    /**
     * Vyhodnocení příkazu na error presenter
     * @param Nette\Application\Request $request
     * @return Nette\Application\Response
     */
    final public function run(Nette\Application\Request $request): Nette\Application\Response
    {
        /** @var Throwable $e */
        $e = $request->getParameter('exception');

        // Pokud se jedná o očekávanou chybu 4xx
        if ($e instanceof Nette\Application\BadRequestException)
        {
            // Zalogujeme informaci o volání neexistující nebo nepovolené stránky
            $this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');

            // Předáme funkci, která ma nastarosti vrátit forward request na zobrazení detailu http chyby
            return $this->handleBadRequestException($e, $request);
        }

        // Pokud se jedná o kritické selhání aplikace (error 500) vykreslíme chybovou stránku včetně názvu souboru s chybou
        try
        {
            $this->logger->log($e, ILogger::EXCEPTION);

            $logged = TRUE;
        }
        catch (Throwable $logError)
        {
            $logged = FALSE;
        }

        return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse) use ($e, $logged): void
        {
            // Zobrazujeme HTML chybovou stránku
            if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type')))
            {
                ErrorHandler::renderError($e, $logged);
            }
            // Zobrazení kódu chyby v CLI
            elseif (PHP_SAPI === 'cli')
            {
                echo sprintf("error 500: %s\n", $logged ? ErrorHandler::getErrorFile($e) : 'Tracy is unable to log error');
            }
        });
    }
}

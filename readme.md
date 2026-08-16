# pavlista/nette-error-handler
Jednoduché rozšíření Nette které zajištuje zobrazení názvu souboru se zalogovanou exception při zobrazení chyby 500 v produkčním režimu (viz. obrázek níže).

![Nový vzhled chybové stránky 500](https://raw.githubusercontent.com/MichaelPavlista/nette-error-handler/master/docs/imgs/error.500.png)

## Požadavky
- PHP 8.2 – 8.5
- nette/application 3.2 – 3.3, nette/http 3.3, nette/utils 4.0.4 – 4.1, tracy/tracy 2.10 – 2.x
- nette/database 3.1.7 – 3.2 (volitelně, pouze pro `ErrorHandlerModule\Logger\DeadlockLogger`)

## Instalace
- Nainstalujte balíček přes příkaz: `composer require pavlista/nette-error-handler`.
- V konfiguraci nette zaregistrujte nový error presenter.
```neon
application:
    errorPresenter: ErrorHandler:Error
```
- Do souboru boostrap.php přidejte ihned pod `$configurator->enableTracy(__DIR__ . '/../log');` registraci error handleru:
```php
ErrorHandlerModule\ErrorHandler::register();
```

## Omezení
Vlastní chybovou šablonu předávejte **výhradně** přes `ErrorHandler::register()`:
```php
ErrorHandlerModule\ErrorHandler::register(__DIR__ . '/../app/error.500.phtml');
```

Nekombinujte modul s volbou `errorTemplate` v konfiguraci Tracy:
```neon
tracy:
    errorTemplate: ...   # nepoužívat společně s tímto modulem
```
DI kontejner tuto hodnotu nastavuje až po zavolání `ErrorHandler::register()` v bootstrapu.
Modul si při každé kritické chybě ověřuje, že šablona v Tracy odpovídá té, kterou zaregistroval —
při rozdílu se sám deaktivuje výjimkou `Tracy error template changed, error handler is disabled`
a chybová stránka se nevykreslí.

## Vývoj
Statická analýza běží v Dockeru nad oficiálními `php:*-cli` image (viz `docker/Dockerfile`),
takže lokálně stačí mít Docker a `make`:

| příkaz | co dělá |
|---|---|
| `make` | `composer update` + všechny kontroly |
| `make c` | všechny kontroly bez `composer update` |
| `make ps` | PHPStan (3 běhy: analýza pro PHP 8.2 i 8.5 a pojistný běh pod PHP 8.2) |
| `make pcs` / `make pcss` | PHPCS / PHPCS se souhrnem |
| `make r` | Rector (dry-run) |
| `make f` | automatické opravy (Rector + PHPCS) |
| `make psgb` | přegenerování PHPStan baseline |

Verzi PHP lze zvolit přes `PHP=85`, např. `make c PHP=85`. Default je nejnižší podporovaná verze.

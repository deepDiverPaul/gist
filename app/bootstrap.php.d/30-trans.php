<?php

use Silex\Provider\TranslationServiceProvider;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;

$app->register(new TranslationServiceProvider(), array(
    'locale' => 'en',
    'locale_fallback' => 'en',
    'locales' => array('en', 'fr', 'es', 'de', 'cn', 'pl'),
));

$app['translator'] = $app->extend('translator', function ($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    foreach ($app['locales'] as $locale) {
        $file = $app['root_path'].'/app/locales/'.$locale.'.yml';

        if (is_file($file)) {
            $translator->addResource('yaml', $file, $locale);
        }
    }

    return $translator;
});

$app['routes'] = $app->share($app->extend('routes', function ($routes, $app) {
    $routes->addPrefix('/{_locale}');
    $routes->addDefaults(array('_locale' => $app['locale_fallbacks'][0]));
    $routes->addRequirements(array('_locale' => implode('|', $app['locales'])));

    return $routes;
}));

/**
 * Redirect home on right locale page, regarding of request accept locale or
 * default fallback.
 */
$app->get('/', function (Request $request) use ($app) {
    $accept = AcceptHeader::fromString($request->headers->get('Accept-Language'));
    $cookieValue = $request->cookies->get('locale');

    if (!empty($cookieValue) && in_array($cookieValue, $app['locales'])) {
        $foundLocale = $cookieValue;
    } else {
        $foundLocale = $app['translator']->getLocale();

        foreach ($app['locales'] as $locale) {
            if ($cookieValue === $locale || $accept->has($locale)) {
                $foundLocale = $locale;
                break;
            }
        }
    }

    return new RedirectResponse($app['url_generator']->generate(
        'home',
        array('_locale' => $foundLocale)
    ));
});

$app->after(function (Request $request, Response $response) use ($app) {
    if ($response instanceof RedirectResponse) {
        return;
    }

    $value = $request->get('_locale');

    $cookie = new Cookie('locale', $value, strtotime('+1 month'));
    $response->headers->setCookie($cookie);
});

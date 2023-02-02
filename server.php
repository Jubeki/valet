<?php

require_once './cli/includes/require-drivers.php';
require_once './cli/Valet/Server.php';
require_once './cli/Valet/Valet.php';

use Valet\Drivers\ValetDriver;
use Valet\Server;

/**
 * Define the user's "~/.config/valet" path.
 */
define('VALET_HOME_PATH', Server::homePath(__FILE__));
define('VALET_STATIC_PREFIX', '41c270e4-5535-4daa-b23e-c269744c2f45');

/**
 * Load the Valet configuration.
 */
$valetConfig = json_decode(
    file_get_contents(VALET_HOME_PATH.'/config.json'), true
);

$server = new Server($valetConfig);

/**
 * Parse the URI and site / host for the incoming request.
 */
$uri = Server::uriFromRequestUri($_SERVER['REQUEST_URI']);
$siteName = $server->siteNameFromHttpHost($_SERVER['HTTP_HOST']);
$valetSitePath = $server->sitePath($siteName);

if (is_null($valetSitePath) && is_null($valetSitePath = $server->defaultSitePath())) {
    Server::show404();
}

$valetSitePath = realpath($valetSitePath);

/**
 * Find the appropriate Valet driver for the request.
 */
$valetDriver = ValetDriver::assign($valetSitePath, $siteName, $uri);

if (! $valetDriver) {
    Server::show404();
}

/**
 * ngrok uses the X-Original-Host to store the forwarded hostname.
 */
if (isset($_SERVER['HTTP_X_ORIGINAL_HOST']) && ! isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
    $_SERVER['HTTP_X_FORWARDED_HOST'] = $_SERVER['HTTP_X_ORIGINAL_HOST'];
}

/**
 * Attempt to load server environment variables.
 */
$valetDriver->loadServerEnvironmentVariables(
    $valetSitePath, $siteName
);

/**
 * Allow driver to mutate incoming URL.
 */
$uri = $valetDriver->mutateUri($uri);

/**
 * Determine if the incoming request is for a static file.
 */
$isPhpFile = pathinfo($uri, PATHINFO_EXTENSION) === 'php';

if ($uri !== '/' && ! $isPhpFile && $staticFilePath = $valetDriver->isStaticFile($valetSitePath, $siteName, $uri)) {
    return $valetDriver->serveStaticFile($staticFilePath, $valetSitePath, $siteName, $uri);
}

/**
 * Allow for drivers to take pre-loading actions (e.g. setting server variables).
 */
$valetDriver->beforeLoading($valetSitePath, $siteName, $uri);

/**
 * Attempt to dispatch to a front controller.
 */
$frontControllerPath = $valetDriver->frontControllerPath(
    $valetSitePath, $siteName, $uri
);

if (! $frontControllerPath) {
    if (isset($valetConfig['directory-listing']) && $valetConfig['directory-listing'] == 'on') {
        Server::showDirectoryListing($valetSitePath, $uri);
    }

    Server::show404();
}

chdir(dirname($frontControllerPath));

require $frontControllerPath;

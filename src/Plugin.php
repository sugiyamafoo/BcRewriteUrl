<?php
declare(strict_types=1);

namespace BcRewriteUrl;

use BaserCore\BcPlugin;
use BcRewriteUrl\Middleware\RewriteUrlMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;

class Plugin extends BcPlugin
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        require_once __DIR__ . '/Middleware/RewriteUrlMiddleware.php';
        return $middlewareQueue->insertBefore(RoutingMiddleware::class, new RewriteUrlMiddleware());
    }
}

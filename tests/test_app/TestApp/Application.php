<?php
declare(strict_types=1);

namespace App;

use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Cake\Routing\RouteBuilder;
use DatabaseBackup\Plugin as DatabaseBackup;

class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        $this->addPlugin(DatabaseBackup::class);
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue->add(new RoutingMiddleware($this));
    }

    public function routes(RouteBuilder $routes): void
    {
        //Do nothing
    }
}

<?php

namespace Froxlor\Core\Http\Middleware;

use Closure;
use Froxlor\Core\Support\Setting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureIsInstalled
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     * @throws \Exception
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Setting::get('core.initialized')) {
            return redirect()->route('init.index');
        }

        return $next($request);
    }
}

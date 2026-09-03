<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceUtf8Response
{
    public function handle(Request $request, Closure $next): Response
    {
        // For POST/PUT/PATCH/DELETE without Accept header, force JSON response
        if ($request->isMethod('POST') || $request->isMethod('PUT') || 
            $request->isMethod('PATCH') || $request->isMethod('DELETE')) {
            if (!$request->headers->has('Accept') || 
                !str_contains($request->header('Accept'), 'application/json')) {
                $request->headers->set('Accept', 'application/json');
            }
        }
        
        $response = $next($request);
        $response->headers->set('Content-Type', 'application/json; charset=utf-8', true);
        return $response;
    }
}

<?php

namespace NahidFerdous\Shield\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PermissionOrSelf
{
    public function handle(Request $request, Closure $next, $permission, $paramName = null, $attribute = null)
    {
        $user = $request->user();

        /**
         * 1️⃣ Resolve the model or numeric ID from route parameter
         */
        $param = $request->route($paramName);

        // Resolve model or numeric value
        if (is_object($param)) {
            $requestedId = $attribute
                ? data_get($param, $attribute)
                : data_get($param, 'id');
        } else {
            $requestedId = $param;
        }

        // Allow if same user
        if ((int) $requestedId === (int) $user->id) {
            return $next($request);
        }

        // Allow if permission is granted
        if ($user->can($permission)) {
            return $next($request);
        }

        abort(403, 'Not Authorized');
    }
}

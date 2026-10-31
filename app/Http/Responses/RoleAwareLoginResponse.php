<?php

namespace App\Http\Responses;

use App\Support\WorkspaceRoute;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class RoleAwareLoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false])
            : redirect()->route(WorkspaceRoute::for($request->user()));
    }
}

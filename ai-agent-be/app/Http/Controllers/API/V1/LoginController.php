<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\V1\UserLoginRequest;
use App\Http\Resources\V1\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends BaseApiController
{
    public function login(UserLoginRequest $request): JsonResponse
    {
        return $this->executeWithErrorHandling(function () use ($request): JsonResponse {
            /** @var User|null $user */
            $user = User::query()->where('email', $request->string('email')->toString())->first();

            if ($user === null || ! Hash::check($request->string('password')->toString(), $user->password)) {
                return $this->apiResponse('The provided credentials are incorrect.', statusCode: Response::HTTP_UNAUTHORIZED);
            }

            $authorization = [
                'token' => Auth::guard('api')->login($user),
                'type' => 'Bearer',
                'ttl' => (int) config('jwt.ttl'),
                'refresh_ttl' => (int) config('jwt.refresh_ttl'),
            ];

            return $this->apiResponse(
                'Success',
                new UserResource($user),
                extraData: ['authorization' => $authorization],
            );
        });
    }
}

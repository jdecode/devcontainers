<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserListRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function index(UserListRequest $request): AnonymousResourceCollection
    {
        $sortBy = $request->validated('sortBy', 'id');
        $orderBy = $request->validated('orderBy', 'asc');
        $perPage = $request->validated('perPage', config('constants.pagination.default_per_page'));
        return UserResource::collection(
            User::orderBy($sortBy, $orderBy)
                ->paginate($perPage)
                ->withQueryString()
        );
    }
}

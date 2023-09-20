<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserListRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\SearchService;
use App\Traits\ActivityLog;
use App\Traits\HttpResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class UserController extends Controller
{
    use ActivityLog;
    use HttpResponse;

    public function index(
        UserListRequest $request,
        SearchService $service
    ): AnonymousResourceCollection|JsonResponse {
        $sortBy = $request->validated('sortBy');
        $orderBy = $request->validated('orderBy');
        $perPage = $request->validated('perPage', config('constants.pagination.default_per_page'));
        $search = $request->validated('search');
        try {
            $builder = $service->search(User::class, $search);
            if ($sortBy && $orderBy) {
                $builder->orderBy($sortBy, $orderBy);
            }
            return UserResource::collection(
                $builder->paginate($perPage)->withQueryString()
            );
        } catch (Throwable $throwable) {
            $this->activity(
                __('messages.user.search_error'),
                Auth::user(),
                null,
                ['message' => $throwable->getMessage(), 'trace' => $throwable->getTraceAsString()]
            );
            return $this->response([], __('messages.user.search_error'), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

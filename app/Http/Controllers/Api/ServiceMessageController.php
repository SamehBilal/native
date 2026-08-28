<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreServiceMessageRequest;
use App\Http\Resources\ServiceMessageResource;
use App\Models\ServiceRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ServiceMessageController extends Controller
{
    public function index(Request $request, ServiceRequest $serviceRequest): JsonResponse
    {
        $this->authorize('view', $serviceRequest);
        abort_unless($serviceRequest->isAccepted(), 403, 'Chat opens once a request is accepted.');

        return response()->json([
            'data' => ServiceMessageResource::collection(
                $serviceRequest->messages()->oldest()->get()
            ),
        ]);
    }

    public function store(StoreServiceMessageRequest $request, ServiceRequest $serviceRequest): ServiceMessageResource
    {
        $this->authorize('view', $serviceRequest);
        abort_unless($serviceRequest->isAccepted(), 403, 'Chat opens once a request is accepted.');

        $message = $serviceRequest->messages()->create([
            'sender_id' => $request->user()->id,
            'body' => $request->string('body'),
        ]);

        return new ServiceMessageResource($message);
    }
}

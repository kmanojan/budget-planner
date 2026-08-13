<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\InvitationResource;
use App\Models\Invitation;
use App\Services\InvitationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvitationController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private InvitationService $service) {}

    /**
     * API-INV-01: List pending invitations for current user
     */
    public function index(Request $request): JsonResponse
    {
        $invitations = $this->service->getForUser($request->user());
        return $this->success(InvitationResource::collection($invitations));
    }

    /**
     * API-INV-02: Accept invitation
     */
    public function accept(Request $request, Invitation $invitation): JsonResponse
    {
        if ($invitation->receiver_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        if ($invitation->status->value !== 'pending') {
            return $this->error('Invitation already processed', 400);
        }

        $this->service->accept($invitation);
        return $this->success(null, 'Invitation accepted');
    }

    /**
     * API-INV-03: Reject invitation
     */
    public function reject(Request $request, Invitation $invitation): JsonResponse
    {
        if ($invitation->receiver_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        if ($invitation->status->value !== 'pending') {
            return $this->error('Invitation already processed', 400);
        }

        $this->service->reject($invitation);
        return $this->success(null, 'Invitation rejected');
    }
}

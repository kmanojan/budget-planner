<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\ImageUploadService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private ImageUploadService $imageService) {}

    /**
     * API-PRF-01: Get profile
     */
    public function show(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * API-PRF-02: Update profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        if ($request->hasFile('profile_image')) {
            // Delete old image
            if ($user->profile_image) {
                $this->imageService->delete($user->profile_image);
            }

            $path = $this->imageService->uploadProfileImage($request->file('profile_image'));
            $data['profile_image'] = Storage::url($path);
        }

        $user->update($data);

        return $this->success(new UserResource($user->fresh()), 'Profile updated');
    }

    /**
     * API-PRF-03: Update settings (language, theme)
     */
    public function updateSettings(Request $request): JsonResponse
    {
        $request->validate([
            'language' => ['sometimes', 'string', 'in:en,si,ta'],
            'theme' => ['sometimes', 'string', 'in:light,dark,system'],
        ]);

        $user = $request->user();
        $user->update($request->only(['language', 'theme']));

        return $this->success(new UserResource($user->fresh()), 'Settings updated');
    }

    /**
     * API-PRO-04: Update Device Token
     */
    public function updateDeviceToken(Request $request): JsonResponse
    {
        $request->validate([
            'device_token' => 'required|string',
        ]);

        $request->user()->update([
            'device_token' => $request->device_token,
        ]);

        return $this->success(null, 'Device token updated');
    }
}

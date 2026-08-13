<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\ShareAccountRequest;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Http\Requests\Account\UpdateAccountRequest;
use App\Http\Resources\AccountResource;
use App\Models\Account;
use App\Services\AccountService;
use App\Services\InvitationService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private AccountService $accountService,
        private InvitationService $invitationService
    ) {}

    /**
     * API-ACCT-01: List all accounts
     */
    public function index(Request $request): JsonResponse
    {
        $accounts = $this->accountService->getAllForUser($request->user());
        return $this->success(AccountResource::collection($accounts));
    }

    /**
     * API-ACCT-02: Create account
     */
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $this->accountService->create($request->validated(), $request->user());
        return $this->created(new AccountResource($account), 'Account created');
    }

    /**
     * API-ACCT-03: Get account detail
     */
    public function show(Request $request, Account $account): JsonResponse
    {
        if (!$this->accountService->userHasAccess($account, $request->user())) {
            return $this->error('Forbidden', 403);
        }

        $account->load('sharedUsers');
        return $this->success(new AccountResource($account));
    }

    /**
     * API-ACCT-04: Update account
     */
    public function update(UpdateAccountRequest $request, Account $account): JsonResponse
    {
        if (!$this->accountService->userCanEdit($account, $request->user())) {
            return $this->error('Forbidden', 403);
        }

        $account = $this->accountService->update($account, $request->validated());
        return $this->success(new AccountResource($account), 'Account updated');
    }

    /**
     * API-ACCT-05: Delete account
     */
    public function destroy(Request $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $this->accountService->delete($account);
        return $this->noContent('Account deleted');
    }

    /**
     * API-ACCT-06: Share account (creates invitation)
     */
    public function share(ShareAccountRequest $request, Account $account): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        if ($request->user_id == $request->user()->id) {
            return $this->error('Cannot share account with yourself', 400);
        }

        $invitation = $this->invitationService->createFromShare(
            $account->id,
            $request->user()->id,
            $request->user_id,
            $request->role
        );

        return $this->success($invitation, 'Invitation sent');
    }

    /**
     * API-ACCT-07: Remove shared user
     */
    public function removeSharedUser(Request $request, Account $account, int $userId): JsonResponse
    {
        if ($account->user_id !== $request->user()->id) {
            return $this->error('Forbidden', 403);
        }

        $this->accountService->removeSharedUser($account, $userId);
        return $this->success(null, 'Shared user removed');
    }

    /**
     * API-ACCT-08: Get shared accounts
     */
    public function shared(Request $request): JsonResponse
    {
        $accounts = $this->accountService->getSharedAccounts($request->user());
        return $this->success(AccountResource::collection($accounts));
    }

    /**
     * API-ACCT-09: Merge accounts
     */
    public function merge(Request $request): JsonResponse
    {
        $request->validate([
            'account_ids' => ['required', 'array', 'min:2'],
            'account_ids.*' => ['exists:accounts,id'],
        ]);

        $result = $this->accountService->mergeAccounts(
            $request->account_ids,
            $request->user()
        );

        return $this->success([
            'merge_group_id' => $result['merge_group_id'],
            'accounts' => AccountResource::collection($result['accounts']),
            'total_balance' => $result['total_balance'],
            'currency_code' => $result['currency_code'],
        ], 'Accounts merged');
    }

    /**
     * API-ACCT-10: Unmerge accounts
     */
    public function unmerge(Request $request, string $groupId): JsonResponse
    {
        $this->accountService->unmerge($groupId, $request->user());
        return $this->success(null, 'Accounts unmerged');
    }
}

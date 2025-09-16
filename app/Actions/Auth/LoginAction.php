<?php
namespace App\Actions\Auth;

use App\Enum\Role;
use App\Enum\StatusEnum;
use App\Helpers\SocketBroadcast;
use App\Models\Account\CompanyAccount;
use App\Models\Account\CompanyUser;
use App\Models\Account\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:8|max:50|regex:/^.*(?=.{3,})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$/',
            'role' => 'required'
        ]);

        if( !in_array($request->role, ['ba','admin'])){
            throw ValidationException::withMessages(['email' => 'The email you entered is incorrect',]);
        }
        $credential = $this->findUserByRole($request);
        $companyAccount = @$credential['companyAccount'];
        $companyUser = @$credential['companyUser'];
        $user = @$credential['user'] ?: $companyAccount;

        if (!$companyUser && !$user) {
            throw ValidationException::withMessages(['email' => 'The email you entered is incorrect',]);
        }

        $this->forceLogoutUser($user->id);

        $companyName = $companyAccount?->name;
        $companyId = $user->company_id ?: $companyUser->company_id;
        $sessionObject = [
            'id' => $user->id,
            'role' => $user->role,
            'name' => $user->name,
            'company_id' => $companyId,
            'lang' => $user->lang ?: 'en',
            'avatar' => asset($user->profile),
            'user_company' => (object) [
                'name' => $companyUser?->name ?: $user->name,
                'code' => $companyUser?->code,
                'profile' => asset($companyUser?->profile ?: $user->profile),
            ],
            'company_name' => $companyName,
            'company' => (object) [
                'id' => $companyId,
                'name' => $companyName
            ],
            'tenant_id' => $companyAccount->company->tenant_id
        ];

        session()->put(config('services.session-user-prefix'), (object) $sessionObject);
        return $user;
    }

    private function findUserByRole(Request $request)
    {
        $role = Role::from($request->role);
        $email = $request->email;
        $companyUser = null;
        $user = null;
        $companyAccount = null;


        $companyAccount = CompanyAccount::with(['company'])
            ->where('role', $role)
            ->where('email', $email)
            ->first();

        if (!$companyAccount) {
            if ($role === 'admin') {
                $adminByEmail = User::where('role', Role::Admin)
                        ->where('email', $request->email)
                        ->first();
                if ($adminByEmail) {
                        if (!$adminByEmail->email_verified_at) {
                            throw ValidationException::withMessages([
                                'email' => 'You have to confirm your account before continuing',
                            ]);
                        }
                        throw ValidationException::withMessages(['email' => 'Your account has not been registered with any Business Account']);
                }
            }
            throw ValidationException::withMessages(['email' => 'Your email was not found in our system or incorrect']);
        }

        if (!$company = $companyAccount->company) {
            throw ValidationException::withMessages(['email' => 'The email you entered is incorrect']);
        }


        if (!$company->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'You have to confirm your account before continuing',
            ]);
        }

        if ($companyAccount->role === Role::Admin) {

            if (!$user = $companyAccount->user)
                throw ValidationException::withMessages(['email' => 'The email you entered is incorrect',]);

            if (!$user->email_verified_at)
                throw ValidationException::withMessages([
                        'email' => 'You have to confirm your account before continuing',
                ]);

            $companyUser = CompanyUser::query()
                ->where('company_id', $company->id)
                ->where('user_id', $user->id)
                ->select(['status', 'id', 'company_id'])
                ->firstOrFail();
            if ($companyUser->status !== StatusEnum::Active) {
                throw ValidationException::withMessages(['email' => 'Your account is in active by business account']);
            }
        }

        if (!Hash::check($request->password, $companyAccount->password)) {
            throw ValidationException::withMessages(['password' => 'The password you entered does not match']);
        }

        $companyAccount->profile = $company->logo;
        $companyAccount->name = $company->name;

        return [
            'companyAccount' => $companyAccount,
            'companyUser' => $companyUser,
            'user' => $user
        ];
    }

    private function forceLogoutUser($userId)
    {
        SocketBroadcast::channel('force_logout')
            ->destination([$userId])
            ->send(['status' => 'approved']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetOtpEmailRequest;
use App\Http\Requests\UserActiveApiRequest;
use App\Models\User;
use App\Models\UserActive;
use App\Notifications\VerifyEmailRegisterApi;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login']]);
    }

    public function register(RegisterRequest $request)
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'name'      => $request->name,
                'email'     => $request->email,
                'password'  => bcrypt($request->password),
                'is_active' => 0,
            ]);
            $userActive = $this->createActiveUser($user);

            $user->notify(new VerifyEmailRegisterApi($userActive));
            DB::commit();
            return response()->json([
                'message'      => 'User successfully registered',
                'token_active' => $userActive->token,
            ], 201);
        } catch (\Exception $exception) {
            DB::rollBack();
            return $this->catchData($exception);
        }
    }

    public function login(LoginRequest $request)
    {
        $credentials = $request->all();
        $credentials['is_active'] = 1;
        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function userActive(UserActiveApiRequest $request)
    {
        try {
            $userActive = UserActive::where('token', $request->token)->where('otp', $request->otp)->orderBy('id')->firstOrFail();
            if (optional($userActive->user)->is_active) {
                return response()->json([
                    'message' => 'Tài khoản của bạn đã được active.Vùi lòng không thử lại.',
                ], 500);
            }
            if (Carbon::parse($userActive->exp_date)->greaterThan(Carbon::now())) {
                User::findOrFail($userActive->user_id)->update([
                    'is_active'         => 1,
                    'email_verified_at' => Carbon::now(),
                ]);
                return response()->json([
                    'message' => 'User active success',
                ], 200);
            }
            return response()->json([
                'message' => 'Otp has expired',
            ], 500);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->catchData($e);
        } catch (\Exception $exception) {
            return $this->catchData($exception);
        }
    }

    public function profile()
    {
        return response()->json(auth('api')->user());
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60
        ]);
    }

    public function resetOtpEmail(ResetOtpEmailRequest $request)
    {
        try {
            $user = User::whereEmail($request->email)->firstOrFail();
            $userActive = $this->createActiveUser($user);
            $user->notify(new VerifyEmailRegisterApi($userActive));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->catchData($e);
        } catch (\Exception $exception) {
            return $this->catchData($exception);
        }

    }

    protected function createActiveUser($user)
    {
        return UserActive::create([
            'user_id'  => $user->id,
            'token'    => substr(str_shuffle(str_repeat($x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(50 / strlen($x)))), 1, 50),
            'otp'      => mt_rand(str_repeat(0, 6) . 1, str_repeat(9, 6)),
            'exp_date' => Carbon::now()->addMinute(5),
        ]);
    }

    protected function catchData($e)
    {
        return response([
            'message' => $e->getMessage()
        ], intval($e->getCode()) > 0 ? $e->getCode() : 500 );
    }
}

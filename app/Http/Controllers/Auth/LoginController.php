<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\User;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Input;
use Laravel\Socialite\Facades\Socialite;
use mysql_xdevapi\Exception;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }


    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider($platform)
    {
        if (Input::get('invite')) {
            $redirectUrl = Input::get('redirect') ? route(Input::get('redirect'), ['inviteCode' => Input::get('invite')]) : '/';
        } else {
            $redirectUrl = Input::get('redirect') ? route(Input::get('redirect')) : '/';
        }

        session()->put('redirectUrl', $redirectUrl);
        return Socialite::driver($platform)->redirect();
    }

    protected function authenticated(Request $request, $user)
    {
        return redirect($request->get('next'));
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @param $platform
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleProviderCallback(Request $request, $platform)
    {
        if (!$request->has('oauth_token')) {
            if (!$request->has('code') || $request->has('denied')) {
                return redirect('/');
            }
        }

        $userData = Socialite::driver($platform)->stateless()->user();

        try {
            $user = $this->getExistingUser($userData, $platform);
        } catch (\Exception $e) {
            session()->flash('warning', 'Sorry, season 8 has started. Registrations are closed.');
            return redirect('/');
        }

        auth()->login($user);

        if (session()->get('redirectUrl')) {
            if ($user->hasPredictions()) {
                return redirect('/');
            }
            $redirectUrl = session()->get('redirectUrl');
            session()->forget('redirectUrl');
            return redirect($redirectUrl)->with('redirectToPrevious', true);
        }
        return redirect($this->redirectTo)->with('redirectToPrevious', true);
    }

    public function logout()
    {
        auth()->logout();
        return redirect($this->redirectTo)->with('redirectToPrevious', true);
    }

    /**
     * Try and get existing user and return it or create new one.
     *
     * @param $userData
     * @param string $platform
     * @return \App\User
     * @throws \Exception
     */
    public function getExistingUser($userData, $platform)
    {
        $user = User::where('social_id', $userData->id)->first();

        if (!$user) {
            if (config('app.lockdown')) {
                throw new \Exception('Lockdown in effect', 42);
            }
            $user = $this->registerNewUser($userData, $platform);
        }

        return $user;
    }

    /**
     * Register a new user with the data given and return
     *
     * @param $userData
     * @param string $platform
     * @return \App\User
     */
    public function registerNewUser($userData, $platform)
    {
        $user = User::create([
            'name' => $userData->name ?? $userData->nickname,
            'avatar' => $userData->avatar,
            'password' => bcrypt(str_random(30)),
            'social_id' => $userData->id,
            'platform' => $platform,
            'token' => $userData->token,
            'refresh_token' => $userData->refreshToken ?? ''
        ]);

        return $user;
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function login(): View
    {
        return view('auth.login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        $request->merge(['identifier' => $request->input('identifier', $request->input('email'))]);
        $data = $request->validate([
            'identifier' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);
        $digits = preg_replace('/\D/', '', $data['identifier']);
        $credentials = filter_var($data['identifier'], FILTER_VALIDATE_EMAIL)
            ? ['email' => strtolower($data['identifier']), 'password' => $data['password']]
            : ['username' => $digits, 'password' => $data['password']];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'identifier' => 'CPF, e-mail ou senha inválidos.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(Auth::user()->isStudent() ? route('student.portal') : route('courses.index'));
    }

    public function register(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $user = User::create($request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('courses.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

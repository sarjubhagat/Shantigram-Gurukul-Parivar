<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function show()
    {
        $user = auth()->user()->load('roles');
        return view('admin.profile.show', compact('user'));
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator, 'password')->withInput();
        }

        auth()->user()->update(['password' => $request->password]);

        return back()->with('password_success', 'Password updated successfully.');
    }
}

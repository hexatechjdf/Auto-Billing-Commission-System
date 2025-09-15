<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('admin.profile-edit', compact('user'));
    }

    // public function update(Request $request)
    // {
    //     $user = Auth::user();

    //     $validated = $request->validate([
    //         'name'             => 'required|string|max:255',
    //         'email'            => 'required|email|max:255|unique:users,email,' . $user->id,
    //         'current_password' => 'required|required_with:password|string',
    //         'password'         => 'nullable|confirmed|min:8',
    //     ]);

    //     // Always check current password
    //     if (! Hash::check($validated['current_password'], $user->password)) {
    //         return back()->withErrors(['current_password' => 'Your current password is incorrect.']);
    //     }

    //     $user->name  = $validated['name'];
    //     $user->email = $validated['email'];

    //     if (! empty($validated['password'])) {
    //         $user->password = bcrypt($validated['password']);
    //     }

    //     $user->save();

    //     return redirect()->route('admin.profile.edit')->with('success', 'Profile updated successfully.');
    // }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email,' . $user->id,
            'current_password' => 'required|string',
            'password'         => 'nullable|confirmed|min:8',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'errors'  => ['current_password' => ['Your current password is incorrect.']],
            ], 422);
        }

        $user->name  = $validated['name'];
        $user->email = $validated['email'];

        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully.',
        ]);
    }

}

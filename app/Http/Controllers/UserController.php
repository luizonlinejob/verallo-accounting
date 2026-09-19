<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    // Kuhaon ang listahan sa tanang users
    public function index()
    {
        $users = User::latest()->get()->map(function ($user) {
            return [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'created_at' => $user->created_at->timezone('Asia/Manila')->format('M d, Y - g:i A'),
            ];
        });

        return response()->json($users);
    }

    // Pag-add og bag-ong User/Account
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'min:8'],
            'role'     => 'required|in:superadmin,admin,encoder',
        ]);

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User account created successfully!',
            'user'    => $user
        ], 201);
    }

    // Pag-update sa Role/Privilege sa User
    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|in:superadmin,admin,encoder',
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'User role updated to ' . strtoupper($user->role) . '!'
        ]);
    }

    // Pag-delete sa User Account
    public function destroy(User $user)
    {
        // Dili ma-delete ang kaugalingon nga logged-in account
        if (auth()->id() === $user->id) {
            return response()->json([
                'status'  => 'error',
                'message' => 'You cannot delete your own account!'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'User account deleted successfully.'
        ]);
    }
}
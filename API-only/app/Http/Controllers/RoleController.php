<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    //
    public function index()
    {
        $roles = Role::with('users')->get();
        return response()->json([
            'roles' => $roles,
        ]);
    }
    // store
    public function store(Request $request, string $id)
    {
       $data = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'guard_name' => 'required|string|max:255',
        ]);

        $role = Role::create($data);
        // attach the role to the user
        $user = User::findOrFail($id);
        $user->roles()->attach($role->id);
        // return the role
        return response()->json([
            'role' => $role->users,
            'role_loaded' => $role->load('users'),
        ]);
    }
}

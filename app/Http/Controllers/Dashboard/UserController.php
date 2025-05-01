<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $rolse = \App\Models\Role::all();

        return inertia('dashboard/users/Index', [
            'roles' => $rolse,
        ]);
    }

    public function data(Request $request)
    {
        $users = \App\Models\User::query()
            ->leftJoin('roles', 'roles.id', 'users.id')
            ->select('users.*');

        $data = parsingDataTable([
            [
                'name' => 'name',
            ],
            [
                'name' => 'email',
            ],
            [
                'name' => 'role',
                'render' => function ($user) {
                    return $user->role?->name;
                },
                'filter' => function ($query) {
                    $query->orWhereHas('role', function ($subQuery) {
                        $search = request('search');
                        $subQuery->where('name', 'like', "%{$search}%");
                    });
                },
                'ordering' => function ($query) {
                    $query->orderBy('roles.name', request('order'));
                }
            ],
            [
                'name' => 'created_at',
                'render' => function ($user) {
                    return $user->created_at?->diffForHumans();
                },
            ]
        ], $users);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return response()->json($user);
    }

    public function show($id)
    {
        $user = \App\Models\User::findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $id,
            'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        $user = \App\Models\User::findOrFail($id);
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role_id,
            'password' => $request->password ? \Illuminate\Support\Facades\Hash::make($request->password) : $user->password,
        ]);

        return response()->json($user);
    }

    public function destroy($id)
    {
        $user = \App\Models\User::findOrFail($id);
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:users,id',
        ]);

        \App\Models\User::destroy($request->ids);

        return response()->json(['message' => 'Users deleted successfully']);
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OwenIt\Auditing\Models\Audit;

class RoleManagementController extends Controller
{
    public function index()
    {
        return inertia('dashboard/role-management/Index');
    }

    public function data(Request $request)
    {
        $roles = \App\Models\Role::query();
        
        $data = parsingDataTable([
            [
                'name' => 'name',
            ]
        ], $roles);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $slug = \Illuminate\Support\Str::slug($request->name);

        $role = \App\Models\Role::create([
            'name' => $request->name,
            'slug' => $slug,
            'guard_name' => 'web',
        ]);

        return response()->json($role);
    }

    public function show($id)
    {
        $role = \App\Models\Role::findOrFail($id);
        return response()->json($role);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $role = \App\Models\Role::findOrFail($id);
        $role->update([
            'name' => $request->name,
            'slug' => \Illuminate\Support\Str::slug($request->name),
        ]);

        return response()->json($role);
    }

    public function destroy($id)
    {
        $role = \App\Models\Role::findOrFail($id);
        $role->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 400);
        }

        \App\Models\Role::destroy($ids);

        return response()->json(['message' => 'Roles deleted successfully']);
    }

    public function auditAll()
    {
        $roles = Audit::query()
            ->with(['auditable', 'user'])
            ->where('auditable_type', \App\Models\Role::class)
            ->orderBy('created_at', 'desc')
            ->get();

        
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\DownloadJob;
use App\Models\DownloadExport;
use Illuminate\Http\Request;
use App\Models\Seminar as model;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SeminarController extends Controller
{
    private $folderPath = 'dashboard/seminars/';

    public function index()
    {
        return inertia($this->folderPath . 'Index');
    }

    public function data(Request $request)
    {
        $roles = model::query()
            ->leftJoin('users', 'seminars.user_id', '=', 'users.id')
            ->select('seminars.*');
        
        $data = parsingDataTable([
            [
                'name' => 'title',
            ],
            [
                'name' => 'description',
            ],
            [
                'name' => 'schedule',
                'render' => function ($row) {
                    return $row->schedule->format('Y-m-d H:i');
                }
            ],
            [
                'name' => 'created_by',
                'render' => function ($row) {
                    return $row->user?->name ?? null;
                },
                'filter' => function ($query) {
                    return $query->whereHas('user', function ($q){
                        $q->where('name', 'like', '%' . request('search') . '%');
                    });
                },
                'ordering' => function ($query) {
                    return $query->orderBy('users.name', 'asc');
                }
            ],
            [
                'name' => 'is_active',
            ]
        ], $roles);

        return response()->json($data);
    }

    public function create()
    {
        return inertia($this->folderPath . 'Create');
    }

    public function store(Request $request)
    {
        $validate = $request->validate(model::rules());

        $validate['user_id'] = auth()->user()->id;
        $seminar = model::create($validate);

        return response()->json($seminar);
    }

    public function show($id)
    {
        $seminar = model::findOrFail($id);

        $data = [
            'id' => $seminar->id,
            'title' => $seminar->title,
            'description' => $seminar->description,
            'schedule' => $seminar->schedule?->format('Y-m-d H:i') ?? null,
            'is_active' => $seminar->is_active,
            'additional_info' => $seminar->additional_info,
        ];

        return inertia($this->folderPath . 'Show', [
            'seminar' => $data,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate(model::rules());

        $seminar = model::findOrFail($id);
        $seminar->update($validate);

        return response()->json($seminar);
    }

    public function destroy($id)
    {
        $seminar = model::findOrFail($id);
        $seminar->delete();

        return response()->json(['message' => 'Role deleted successfully']);
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 400);
        }

        model::destroy($ids);

        return response()->json(['message' => 'Roles deleted successfully']);
    }

    public function updateStatus(Request $request, $id)
    {
        $validate = $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $seminar = model::findOrFail($id);
        $seminar->update(['is_active' => $request->input('is_active')]);

        return response()->json([
            'message' => 'Status updated successfully',
            'is_active' => $seminar->is_active,
        ]);
    }

    public function export(Request $request)
    {
        $validate = $request->validate([
            'headers' => 'required|array',
            'headers.*.value' => 'required|string',
            'headers.*.label' => 'required|string',
        ]);

        $downloadExport = DownloadExport::create([
            'name' => 'Seminar Download',
            'type' => 'export',
        ]);

        DownloadJob::dispatch($downloadExport->id, SeminarController::class, 'exportExec', [$validate['headers']]);

        return response()->json([
            'message' => 'Download request received successfully',
        ]);
    }

    public function exportExec($headers)
    {
        $seminars = model::query()
            ->with(['participants', 'user'])
            ->withCount('participants')
            ->get();

        $data = [];
        foreach ($seminars as $seminar) {
            $data[] = [
                'id' => $seminar->id,
                'title' => $seminar->title,
                'description' => $seminar->description,
                'schedule' => $seminar->schedule?->format('Y-m-d H:i') ?? null,
                'created_by' => $seminar->user?->name ?? null,
                'is_active' => $seminar->is_active ? 'Yes' : 'No',
                'participants_count' => $seminar->participants_count,
                'created_at' => $seminar->created_at?->format('Y-m-d H:i') ?? null,
                'additional_info' => collect($seminar->additional_info ?? [])->map(function ($item) {
                    return $item;
                })->implode(', '),
            ];
        }

        $filePath = 'seminars/exported/seminar_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        Excel::store(new \App\Exports\SeminarExport($data, $headers), $filePath, 'local');

        return $filePath;
    }

    public function import(Request $request)
    {
        $validate = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $filePath = $request->file('file')->store('seminars/imported', 'local');

        $downloadExport = DownloadExport::create([
            'name' => 'Seminar Import',
            'type' => 'import',
            'path' => $filePath,
        ]);

        DownloadJob::dispatch($downloadExport->id, SeminarController::class, 'importExec', [$filePath, auth()->user()->id]);

        return response()->json([
            'message' => 'Import request received successfully',
        ]);
    }

    public function importExec($filePath, $userId)
    {
        $seminars = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\SeminarImport, storage_path('app/private/' . $filePath));

        foreach ($seminars[0] as $key => $seminar) {
            if ($key > 1) {
                $isActive = strtolower($seminar[3]);
    
                model::create([
                    'title' => $seminar[0],
                    'description' => $seminar[1],
                    'schedule' => Date::excelToDateTimeObject($seminar[2]),
                    'is_active' => $isActive == 'active' ? 1 : ($isActive == 'inactive' ? 0 : null),
                    'user_id' => $userId,
                ]);
            }
        }

        return $filePath;
    }
}

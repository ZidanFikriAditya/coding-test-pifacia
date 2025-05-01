<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\DownloadJob;
use App\Models\DownloadExport;
use Illuminate\Http\Request;
use App\Models\Participant as model;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ParticipantController extends Controller
{
    private $folderPath = 'dashboard/participants/';

    public function index()
    {
        return inertia($this->folderPath . 'Index');
    }

    public function data(Request $request)
    {
        $models = model::query()
            ->join('seminars', 'participants.seminar_id', '=', 'seminars.id')
            ->select('participants.*');
        
        $data = parsingDataTable([
            [
                'name' => 'seminar',
                'render' => function ($row) {
                    return $row->seminar?->title ?? null;
                },
                'filter' => function ($query) {
                    return $query->whereHas('seminar', function ($q){
                        $q->where('title', 'like', '%' . request('search') . '%');
                    });
                },
                'ordering' => function ($query) {
                    return $query->orderBy('seminars.title', 'asc');
                }
            ],
            [
                'name' => 'name',
            ],
            [
                'name' => 'email',
            ],
            [
                'name' => 'registered_at',
                'render' => function ($row) {
                    return $row->registered_at?->format('Y-m-d H:i') ?? null;
                }
            ],
            [
                'name' => 'is_confirmed',
            ],
        ], $models);

        return response()->json($data);
    }

    public function create()
    {
        $seminars = \App\Models\Seminar::all();

        return inertia($this->folderPath . 'Create', [
            'seminars' => $seminars,
        ]);
    }

    public function store(Request $request)
    {
        $validate = $request->validate(model::rules());
        
        $participant = model::create($validate);

        return response()->json($participant);
    }

    public function show($id)
    {
        $participant = model::findOrFail($id);
        $seminars = \App\Models\Seminar::all();

        $data = [
            'id' => $participant->id,
            'seminar_id' => $participant->seminar_id,
            'name' => $participant->name,
            'email' => $participant->email,
            'registered_at' => $participant->registered_at?->format('Y-m-d H:i') ?? null,
            'is_confirmed' => $participant->is_confirmed,
            'extra_data' => $participant->extra_data,
        ];

        return inertia($this->folderPath . 'Show', [
            'participant' => $data,
            'seminars' => $seminars,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate(model::rules());

        $participant = model::findOrFail($id);
        $participant->update($validate);

        return response()->json($participant);
    }

    public function destroy($id)
    {
        $participant = model::findOrFail($id);
        $participant->delete();

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
            'is_confirmed' => 'required|boolean',
        ]);

        $participant = model::findOrFail($id);
        $participant->update(['is_confirmed' => $request->input('is_confirmed')]);

        return response()->json([
            'message' => 'Status updated successfully',
            'is_confirmed' => $participant->is_confirmed,
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
            'name' => 'Participant Download',
            'type' => 'export',
        ]);

        DownloadJob::dispatch($downloadExport->id, ParticipantController::class, 'exportExec', [$validate['headers']]);

        return response()->json([
            'message' => 'Download request received successfully',
        ]);
    }

    public function exportExec($headers)
    {
        $model = model::query()
            ->with(['seminar'])
            ->get();

        $data = [];
        foreach ($model as $item) {
            $data[] = [
                'id' => $item->id,
                'seminar' => $item->seminar?->title ?? null,
                'name' => $item->name,
                'email' => $item->email,
                'registered_at' => $item->registered_at?->format('Y-m-d H:i') ?? null,
                'is_confirmed' => $item->is_confirmed ? 'Yes' : 'No',
                'created_at' => $item->created_at?->format('Y-m-d H:i') ?? null,
                'extra_data' => collect($item->extra_data ?? [])->map(function ($item) {
                    return $item;
                })->implode(', '),
            ];
        }

        $filePath = 'seminars/exported/participants_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        Excel::store(new \App\Exports\SeminarExport($data, $headers), $filePath, 'local');

        return $filePath;
    }

    public function import(Request $request)
    {
        $validate = $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $filePath = $request->file('file')->store('participants/imported', 'local');

        $downloadExport = DownloadExport::create([
            'name' => 'Participant Import',
            'type' => 'import',
            'path' => $filePath,
        ]);

        DownloadJob::dispatch($downloadExport->id, ParticipantController::class, 'importExec', [$filePath, auth()->user()->id]);

        return response()->json([
            'message' => 'Import request received successfully',
        ]);
    }

    public function importExec($filePath, $userId)
    {
        $data = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\SeminarImport, storage_path('app/private/' . $filePath));

        foreach ($data[0] as $key => $item) {
            if ($key > 1) {
                $seminar = \App\Models\Seminar::where('title', 'like', '%' . $item[0] . '%')->first();

                if (!$seminar) {
                    continue;
                }

                $isActive = strtolower($item[4]);
    
                model::create([
                    'seminar_id' => $seminar->id,
                    'name' => $item[1],
                    'email' => $item[2],
                    'registered_at' => Date::excelToDateTimeObject($item[3]),
                    'is_active' => $isActive == 'yes' ? 1 : ($isActive == 'no' ? 0 : null),
                ]);
            }
        }

        return $filePath;
    }
}

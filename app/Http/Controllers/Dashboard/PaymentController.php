<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Jobs\DownloadJob;
use App\Models\DownloadExport;
use Illuminate\Http\Request;
use App\Models\Payment as model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PaymentController extends Controller
{
    private $folderPath = 'dashboard/payments/';

    public function index()
    {
        return inertia($this->folderPath . 'Index');
    }

    public function data(Request $request)
    {
        $models = model::query()
            ->join('participants', 'payments.participant_id', '=', 'participants.id')
            ->select('payments.*');
        
        $data = parsingDataTable([
            [
                'name' => 'participant_name',
                'render' => function ($row) {
                    return $row->participant?->name ?? null;
                },
                'filter' => function ($query) {
                    return $query->whereHas('participant', function ($q){
                        $q->where('name', 'like', '%' . request('search') . '%');
                    });
                },
                'ordering' => function ($query) {
                    return $query->orderBy('participants.name', 'asc');
                }
            ],
            [
                'name' => 'participant_email',
                'render' => function ($row) {
                    return $row->participant?->email ?? null;
                },
                'filter' => function ($query) {
                    return $query->whereHas('participant', function ($q){
                        $q->where('email', 'like', '%' . request('search') . '%');
                    });
                },
                'ordering' => function ($query) {
                    return $query->orderBy('participants.email', 'asc');
                }
            ],
            [
                'name' => 'uploaded_at',
                'render' => function ($row) {
                    return $row->uploaded_at?->format('Y-m-d H:i') ?? null;
                }
            ],
            [
                'name' => 'file_path',
                'render' => function ($row) {
                    return $row->file_path ? asset('storage/' . $row->file_path) : null;
                }
            ],
            [
                'name' => 'is_verified',
            ],
        ], $models);

        return response()->json($data);
    }

    public function create()
    {
        $participants = \App\Models\Participant::all();

        return inertia($this->folderPath . 'Create', [
            'participants' => $participants,
        ]);
    }

    public function store(Request $request)
    {
        $validate = $request->validate(model::rules());

        if ($request->hasFile('file_path')) {
            $file = $request->file('file_path');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads', $filename, 'public');
            $validate['file_path'] = 'uploads/' . $filename;
        } else {
            return response()->json(['message' => 'File is required'], 422);
        }
        
        $payment = model::create($validate);

        return response()->json($payment);
    }

    public function show($id)
    {
        $payment = model::findOrFail($id);

        $data = [
            'id' => $payment->id,
            'participant_id' => $payment->participant_id,
            'file_path' => $payment->file_path ? asset('storage/' . $payment->file_path) : null,
            'uploaded_at' => $payment->uploaded_at?->format('Y-m-d H:i') ?? null,
            'is_verified' => $payment->is_verified,
            'metadata' => $payment->metadata,
            'participant' => [
                'name' => $payment->participant?->name,
                'email' => $payment->participant?->email,
            ],
        ];

        return response()->json($data);
    }

    public function edit($id)
    {
        $payment = model::findOrFail($id);
        $participants = \App\Models\Participant::all();

        $data = [
            'id' => $payment->id,
            'participant_id' => $payment->participant_id,
            'file_path' => $payment->file_path ? asset('storage/' . $payment->file_path) : null,
            'uploaded_at' => $payment->uploaded_at?->format('Y-m-d H:i') ?? null,
            'is_verified' => $payment->is_verified,
            'metadata' => $payment->metadata,
        ];

        return inertia($this->folderPath . 'Show', [
            'payment' => $data,
            'participants' => $participants,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validate = $request->validate(model::rules());

        $payment = model::findOrFail($id);

        if ($request->hasFile('file_path')) {
            if ($payment->file_path) {
                Storage::disk('public')->delete($payment->file_path);
            }

            $file = $request->file('file_path');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('uploads', $filename, 'public');
            $validate['file_path'] = 'uploads/' . $filename;
        }

        $payment->update($validate);

        return response()->json($payment);
    }

    public function destroy($id)
    {
        $payment = model::findOrFail($id);
        $payment->delete();

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
            'is_verified' => 'required|boolean',
        ]);

        $payment = model::findOrFail($id);
        $payment->update(['is_verified' => $request->input('is_verified')]);

        return response()->json([
            'message' => 'Status updated successfully',
            'is_verified' => $payment->is_confirmed,
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
            'name' => 'Payment Download',
            'type' => 'export',
        ]);

        DownloadJob::dispatch($downloadExport->id, PaymentController::class, 'exportExec', [$validate['headers']]);

        return response()->json([
            'message' => 'Download request received successfully',
        ]);
    }

    public function exportExec($headers)
    {
        $model = model::query()
            ->with(['participant'])
            ->get();

        $data = [];
        foreach ($model as $item) {
            $data[] = [
                'id' => $item->id,
                'participant_name' => $item->participant?->name ?? null,
                'participant_email' => $item->participant?->email ?? null,
                'file_path' => $item->file_path ? asset('storage/' . $item->file_path) : null,
                'uploaded_at' => $item->uploaded_at?->format('Y-m-d H:i') ?? null,
                'is_verified' => $item->is_verified ? 'Yes' : 'No',
                'created_at' => $item->created_at?->format('Y-m-d H:i') ?? null,
                'metadata' => collect($item->metadata ?? [])->map(function ($item) {
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
            'name' => 'Payment Import',
            'type' => 'import',
            'path' => $filePath,
        ]);

        DownloadJob::dispatch($downloadExport->id, PaymentController::class, 'importExec', [$filePath, auth()->user()->id]);

        return response()->json([
            'message' => 'Import request received successfully',
        ]);
    }

    public function importExec($filePath, $userId)
    {
        $data = \Maatwebsite\Excel\Facades\Excel::toArray(new \App\Imports\SeminarImport, storage_path('app/private/' . $filePath));

        foreach ($data[0] as $key => $item) {
            if ($key > 1) {
                $participant = \App\Models\Participant::where('email', trim($item[0] ?? ''))->first();

                if (!$participant) {
                    continue;
                }
    
                model::create([
                    'participant_id' => $participant->id,
                    'uploaded_at' => now(),
                ]);
            }
        }

        return $filePath;
    }
}

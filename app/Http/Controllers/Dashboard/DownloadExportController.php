<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DownloadExportController extends Controller
{
    private $folderPath = 'dashboard/downloads/';

    public function index()
    {
        return inertia($this->folderPath . 'Index');
    }

    public function data(Request $request)
    {
        $models = \App\Models\DownloadExport::query();

        $data = parsingDataTable([
            [
                'name' => 'name',
            ],
            [
                'name' => 'link',
                'render' => function ($row) {
                    return $row->path ? route('dashboard.downloads.download', $row->id) : null;
                }
            ],
            [
                'name' => 'created_at',
                'render' => function ($row) {
                    return $row->created_at?->format('Y-m-d H:i') ?? null;
                }
            ],
            [
                'name' => 'finished_at',
                'render' => function ($row) {
                    return $row->finished_at?->format('Y-m-d H:i') ?? null;
                }
            ],
            [
                'name' => 'type',
                'render' => function ($row) {
                    return $row->type === 'import' ? 'Import' : 'Export';
                }
            ],
            [
                'name' => 'status',
            ]
        ], $models);

        return response()->json($data);
    }

    public function destroy($id)
    {
        $model = \App\Models\DownloadExport::findOrFail($id);

        if ($model->path) {
            Storage::disk('local')->delete($model->path);
        }

        $model->delete();

        return response()->json(['message' => 'Download export deleted successfully.']);
    }

    public function bulkDestroy(Request $request)
    {
        $ids = $request->input('ids');

        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided.'], 400);
        }

        foreach ($ids as $id) {
            $model = \App\Models\DownloadExport::findOrFail($id);

            if ($model->path) {
                Storage::disk('local')->delete($model->path);
            }

            $model->delete();
        }

        return response()->json(['message' => 'Download exports deleted successfully.']);
    }

    public function download($id)
    {
        $model = \App\Models\DownloadExport::findOrFail($id);

        if ($model->path) {
            $filePath = storage_path('app/private/' . $model->path);
            if (file_exists($filePath)) {
                return response()->download($filePath);
            }
        }

        return response()->json(['message' => 'File not found.'], 404);
    }
}

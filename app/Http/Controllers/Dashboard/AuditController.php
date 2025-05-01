<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Participant;
use App\Models\Seminar;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index()
    {
        return inertia('dashboard/audits/Index');
    }

    public function data($slug = null, $id = null)
    {
        $slugToModel = [
            'seminars' => Seminar::class,
            'participants' => Participant::class,
            'users' => \App\Models\User::class,
            'payments' => \App\Models\Payment::class,
        ];

        $modelToMenu = [
            Seminar::class => 'Seminar',
            Participant::class => 'Participant',
            \App\Models\User::class => 'User',
            \App\Models\Payment::class => 'Payment',
        ];

        $audits = \OwenIt\Auditing\Models\Audit::query()
            ->with(['user', 'auditable'])
            ->leftJoin('users', 'audits.user_id', '=', 'users.id')
            ->when(request('search') ?? false, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->whereHas('user', function ($q) use ($search) {
                        $q->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhere('auditable_type', 'like', '%' . $search . '%');
                });
            })
            ->when($slugToModel[$slug] ?? null, function ($query) use ($slugToModel, $slug) {
                return $query->where('auditable_type', $slugToModel[$slug]);
            })
            ->when($id, function ($query) use ($id) {
                return $query->where('auditable_id', $id);
            })
            ->when(request('order_by') != 'user', function ($query) {
                return $query->orderBy(request('order_by'), request('order') ?? 'asc');
            })
            ->when(request('order_by') == 'user', function ($query) {
                return $query->orderBy('users.id', request('order') ?? 'asc');
            })
            ->orderBy('created_at', 'desc')
            ->select('audits.*')
            ->paginate(request('per_page') ?? 7);

        $data = [];
        foreach ($audits as $key => $audit) {
            $data[$key] = [
                'id' => $audit->id,
                'event' => \Illuminate\Support\Str::title($audit->event),
                'user' => $audit->user?->id == auth()->user()->id ? 'You' : $audit->user?->name,
                'created_at' => $audit->created_at->format('Y-m-d H:i'),
                'human_readable' => $audit->created_at->diffForHumans(),
                'note' => \Illuminate\Support\Str::title($audit->event) . ' ' . ($modelToMenu[$audit->auditable_type] ?? ''),
            ];
        }    

        return response()->json([
            'data' => $data,
            'meta' => [
                'total' => $audits->total(),
                'lastPage' => $audits->lastPage(),
                'currentPage' => $audits->currentPage(),
                'perPage' => $audits->perPage(),
            ],
        ]);
    }
}

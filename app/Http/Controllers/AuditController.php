<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use OwenIt\Auditing\Models\Audit;

class AuditController extends Controller
{
    public function index(Request $request) 
    {
        $request->validate([
            'page' => 'integer'
        ]);

        $audits = [];

        foreach (Audit::orderBy('created_at', 'asc')->get() as $audit)
        {
            $user = $audit->user;

            $audits[] = [
                'date' => $audit->created_at->format('Y-m-d'),
                'time' => $audit->created_at->format('H:m A'),
                'user_role' => ucfirst($user->role),
                'user_personnel_number' => $user->personnel_number,
                'user_name' => $user->full_name,
                'action' => strtoupper(substr($audit->event, 0, -1)),
                'resource' => $this->getResource($audit->auditable_type),
                'old_values' => $audit->old_values,
                'new_values' => $audit->new_values
            ];
        }

        $page = $request->input('page',1);
        $data = array_slice($audits, ($page - 1) * 15, 15);
        $paginator = new LengthAwarePaginator(
            $data, 
            count($audits), 
            15, 
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $paginator;
    }

    public function getResource(string $auditableType)
    {
        return preg_replace('/(?<!^)(?=[A-Z])/', ' ', class_basename($auditableType));
    }
}

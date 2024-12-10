<?php

namespace App\Http\Controllers;

use App\Traits\AuditTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Audit;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Carbon\Carbon;

class AuditController extends Controller
{
    use AuditTrait;

    public function index(Request $request) 
    {
        $request->validate([
            'page' => 'integer',
            'resource' => 'string|in:user,patient',
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from'
        ]);

        $page = $request->input('page',1);
        $limit = 20;
        $offset = ($page - 1) * $limit;

        $auditsInformation = $this->getAudits($request, $offset, $limit);

        $paginator = new LengthAwarePaginator(
            $auditsInformation['data'], 
            $auditsInformation['totalQuery'], 
            $limit, 
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );
        $paginator->appends($request->query());

        return $paginator;
    }

    public function downloadAudits(Request $request)
    {
        $request->validate([
            'resource' => 'string|in:user,patient',
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from'
        ]);

        $auditsInformation = $this->getAudits($request, null, null);

        $date = now()->format('Y-m-d');

        $pdf = SnappyPdf::loadView('audits', ['audits' => $auditsInformation['data']])
                        ->setPaper('a4', 'landscape')
                        ->setOptions(['margin-top' => 20, 'margin-bottom' => 10, 'margin-left' => 10, 'margin-right' => 10])
                        ->setOption('header-html', view()->make('document_header')->render())
                        ->setOption('footer-html', view()->make('document_footer')->render())
                        ->setOption('enable-local-file-access', true);

        return $pdf->download("printmed_audits_retrieved_at_{$date}.pdf");
    }

    public function getAudits(Request $request, int|null $offset, int|null $limit)
    {
        $auditsQuery = Audit::query();

        if ($request->filled('resource')) {
            switch($request->resource) {
                case 'user':
                    $auditsQuery->whereBlind('auditable_type', 'auditable_type_index', 'App\Models\User');
                    break; 
                case 'patient':
                    $auditsQuery->whereBlind('auditable_type', 'auditable_type_index', 'App\Models\Patient')
                                ->orWhereBlind('auditable_type', 'auditable_type_index', 'App\Models\Consultation');
                    break;
            }
        }

        if ($request->filled('date_from')) {
            $dateFrom = Carbon::parse($request->date_from)->startOfDay();
            $auditsQuery->where('created_at', '>=', $dateFrom);
        }

        if ($request->filled('date_until')) {
            $dateUntil = Carbon::parse($request->date_until)->endOfDay();
            $auditsQuery->where('created_at', '<=', $dateUntil);
        }
        $auditsQuery->orderBy('created_at', 'desc');

        $totalQuery = 0;
        $auditsGet = collect();

        if ($offset !== null && $limit !== null) {
            $auditsQueryClone = clone $auditsQuery;
            $totalQuery = $auditsQueryClone->count();

            $auditsGet = $auditsQuery->offset($offset)->limit($limit)->get();
        } else {
            $auditsGet = $auditsQuery->get();
        }

        $audits = [];

        foreach ($auditsGet as $audit)
        {
            $audits[] = $this->formatAudit($audit);     // formats audit information
        } 

        $auditsInformation = [
            'data' => $audits,
            'totalQuery' => $totalQuery
        ];

        return $auditsInformation;
    }

    // public function getRequestResourceClass(string $resource)
    // {
    //     $requestResourceClass = null;

    //     switch ($resource) {
    //         case 'user':
    //             $requestResourceClass = 'App\Models\User';
    //             break; 
    //         case 'patient':
    //             $requestResourceClass = 'App\Models\Patient';
    //             break;
    //         case 'consultation':
    //             $requestResourceClass = 'App\Models\Consultation';
    //             break;
    //     }

    //     return $requestResourceClass;
    // }
}

<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class RegistrationController extends Controller
{
    use CommonMethodsTrait;

    public function index(Request $request) {
        $request->validate([
            'search' => 'string',
            'sex' => 'string',
            'sort_by' => 'string|in:last_name,follow_up_date',
            'sort_direction' => 'string|in:asc,desc'
        ]);

        $query = Registration::query()->where('created_at', '>', now()->startOfDay());
        
        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->WhereBlind('full_name', 'full_name_index', $search)
                ->orWhereBlind('first_name', 'first_name_index', $search)
                ->orWhereBlind('last_name', 'last_name_index', $search);
            });
        }

        if($request->filled('sex'))
        {
            $query->whereBlind('sex', 'sex_index', $request->sex);
        }

        $query->orderBy('updated_at', 'desc');
        $registrations = $query->get();

        if (count($registrations) > 0)
        {
            if($request->filled('sort_by') && in_array($request->sort_by, ['last_name', 'follow_up_date'])) 
            {
                $isDesc = $request->input('sort_direction') == 'desc';

                $registrations = $registrations->sortBy($request->sort_by, SORT_REGULAR, $isDesc)->values();
            } 

            $page = $request->input('page',1);
            $data = array_slice($registrations->toArray(), ($page - 1) * 15, 15);
            $paginator = new LengthAwarePaginator(
                $data, 
                count($registrations), 
                15, 
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
            $paginator->appends($request->query());

            return $paginator;
        }

        return response()->json(['registrations' => null]);
    }

    public function store(Request $request) {
        $fields = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'string|max:20',
            'birthdate' => 'date|date_format:Y-m-d',
            'birthplace' => 'string',
            'sex' => 'string|max:6|nullable',
            'address' => 'string|max:255',
            'civil_status' => 'string|max:20',
            'religion' => 'string|max:100',
            'phone_number' => 'string|max:12',
            'email' => 'email|max:100',
        ]);
        $fields['full_name'] = $this->getFullName($request->first_name, $request->last_name);

        $registration = Registration::create($fields);

        return $registration;
    }

    public function show(Registration $registration) {
        return $registration;
    }
}

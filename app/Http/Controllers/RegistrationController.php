<?php

namespace App\Http\Controllers;

use App\Events\RegistrationNew;
use App\Models\Registration;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    use CommonMethodsTrait;

    public function index(Request $request) {
        $request->validate([
            'search' => 'string',   // registration id, full name
        ]);

        $query = Registration::query()->where('created_at', '>', now()->startOfDay());
        
        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->WhereBlind('full_name', 'full_name_index', $search)
                ->orWhereBlind('registration_id', 'registration_id_index', $search);
            });
        }

        $query->orderBy('updated_at', 'desc');
        $registrations = $query->select('id', 'registration_id', 'first_name', 'last_name', 'birthdate', 'sex')->paginate();

        return $registrations;
    }

    public function store(Request $request) {
        $fields = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'birthplace' => 'required|string',
            'sex' => 'required|string|max:6',
            'house_number' => 'nullable|string|max:20',
            'street' => 'nullable|string|max:20',
            'barangay' => 'required|string|max:20',
            'city' => 'required|string|max:20',
            'province' => 'required|string|max:20',
            'postal_code' => 'nullable|int|max:4',
            'civil_status' => 'required|string|max:20',
            'religion' => 'nullable|string|max:100',
            'phone_number' => 'nullable|string|max:12',
            'email' => 'nullable|email|max:100',
        ]);
        $fields['full_name'] = $this->getFullName($request->first_name, $request->last_name);

        $latestRegistration = Registration::select('id')->latest()->first();
        $id = $latestRegistration ? $latestRegistration->id : 0;
        $fields['registration_id'] = (string)random_int(100000, 999999) . str_pad($id, 4, '0', STR_PAD_LEFT);

        $registration = Registration::create($fields);

        // pusher event
        event(new RegistrationNew($registration));

        return $registration;
    }

    public function show(Registration $registration) {
        return $registration;
    }
}

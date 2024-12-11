<?php

namespace App\Http\Controllers;

use App\Events\RegistrationNew;
use App\Models\Registration;
use App\Traits\CommonMethodsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RegistrationController extends Controller
{
    use CommonMethodsTrait;

    public function index(Request $request) {
        $request->validate([
            'search' => 'string',   // registration id, full name, first name, last name
        ]);

        $query = Registration::query()->where('created_at', '>', now()->startOfDay());
        
        if ($request->filled('search')) 
        {
            $search = $request->search;

            $query->where(function($q) use ($search) {
                $q->WhereBlind('full_name', 'full_name_index', $search)
                ->orWhereBlind('first_name', 'first_name_index', $search)
                ->orWhereBlind('last_name', 'last_name_index', $search)
                ->orWhereBlind('registration_id', 'registration_id_index', $search);
            });
        }

        $query->orderBy('updated_at', 'desc');
        $registrations = $query->paginate(20);

        return $registrations;
    }

    public function store(Request $request) {
        $fields = $request->validate([
            'first_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'last_name'=> 'required|string|max:100',
            'suffix' => 'nullable|string|max:20',
            'birthdate' => 'required|date|date_format:Y-m-d',
            'birthplace' => 'string',
            'sex' => 'required|string|max:6',
            'house_number' => 'required|string|max:50',
            'street' => 'nullable|string|max:20',
            'barangay' => 'required|string|max:50',
            'barangay_code' => 'required|string|max:10',
            'city' => 'required|string|max:50',
            'city_code' => 'required|string|max:10',
            'province' => 'required|string|max:50',
            'province_code' => 'required|string|max:10',
            'region' => 'required|string|max:50',
            'region_code' => 'required|string|max:10',
            'postal_code' => 'nullable|int|digits_between:1,4',
            'civil_status' => 'required|string|max:20',
            'religion' => 'nullable|string|max:100',
            'phone_number' => 'required|string|size:10',
            'email' => 'nullable|email|max:100',
            'payment_method' => 'required|string|in:Cash,HMO',
            'hmo' => 'required_if:payment_method,HMO|string',
        ]);
        $fields['full_name'] = "{$request->first_name} {$request->last_name}";

        $latestRegistration = Registration::select('id')->latest()->first();
        $id = $latestRegistration ? $latestRegistration->id : 0;
        $fields['registration_id'] = (string)random_int(100000, 999999) . substr(str_pad($id, 4, '0', STR_PAD_LEFT), 0, 4);

        $registration = Registration::create($fields);

        // pusher event
        RegistrationNew::dispatch($registration);

        return response()->json(['registration_id' => $registration->registration_id]);
    }

    public function show(Registration $registration) {
        return $registration;
    }
}

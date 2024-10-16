<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::query();

        //narrows the query with date
        if($request->filled('date'))
        {
            $query->where('date', $request->date);
        }

        //narrows the query with sex
        if($request->filled('date_from'))
        {
            $query->where('date', $request->date);
        }
    }

    public function show(Payment $payment)
    {
        return $payment;
    }

    public function update(Request $request, Payment $payment)
    {
        $user = $request->user();

        //can only update 3 times - from 'Not yet paid' to 'Paid' to 'Not yet paid' to 'Paid'
        if ($payment->update_count < 3)
        {
            if ($payment->status == 'Not yet paid') {
                $payment->status = 'Paid';
            } else if ($payment->status == 'Paid') {
                $payment->status = 'Not yet paid';
            }

            $payment->approved_by_id = $user->id;
            $payment->approved_by_name = $user->full_name;
            $payment->update_count++;

            return $payment;
        }

        return response()->json([
            'success' => false,
            'message' => 'You can only update the payment statud three times.'
        ], 403);
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment record deleted'
        ], 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        //must indicate whether you're fetching all payment records or just today
        $request->validate([
            'filter' => 'required|in:all,today',
            'date_from' => 'date',
            'date_until' => 'date|after:date_from',
            'method' => 'string',
            'status' => 'string',
            'department' => 'string'
        ]);

        $query = $request->filter == 'all' ? Payment::query() : Payment::query()->where('date', Carbon::now()->toDateString());

        if ($request->filter == 'all') 
        {
            //narrows the query with date_from and date_until
            if($request->filled('date_from'))
            {
                $query->where('date', '>=', $request->date_from);
            }

            if($request->filled('date_until'))
            {
                $query->where('date', '<=', $request->date_until);
            }
        }

        //narrows the query with payment method
        if ($request->filled('method'))
        {
            $query->where('method', $request->input('method'));
        }

        //narrows the query with payment status
        if ($request->filled('status'))
        {
            $query->where('status', $request->status);
        }

        //narrows the query with department
        if ($request->filled('department'))
        {
            $query->where('department', $request->input('department'));
        }

        //prder the query by date and time
        $query->orderBy('date')->orderBy('time');

        $payments = $query->paginate(30);
        $payments->appends($request->all());

        return $payments;
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

            $payment->save();

            return $payment;
        }

        return response()->json([
            'success' => false,
            'message' => 'You can only update the payment status three times.'
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

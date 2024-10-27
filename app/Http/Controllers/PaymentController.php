<?php

namespace App\Http\Controllers;

use App\AuditAction;
use App\Events\ModelAction;
use App\Events\PaymentUpdated;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from',
            'order_by_is_paid' => 'boolean',
            'method' => 'string|in:cash,hmo',
            'is_paid' => 'boolean',
            'department_id' => 'integer|exists:departments,id',
        ]);

        $user = $request->user();

        // if user is physician, it will get all payment records for its consultation records
        // if user is secretary, it will get all payment records for its department
        // if user is admin, it get all patient records
        $query = $user->role == 'physician' 
            ? $user->payments()
            : ($user->role == 'secretary'
                ? Payment::query()->where('department_id', $user->department_id)
                : Payment::query());

        if ($user->role == 'admin' && $request->filled('department_id'))
        {
            $query->where('department_id', $request->department_id);
        }

        if($request->filled('date_from'))
        {
            $query->where('created_at', '>=', $request->date_from);
        }

        if($request->filled('date_until'))
        {
            $query->where('created_at', '<=', $request->date_until);
        }

        if ($request->filled('method'))
        {
            $query->where('method', $request->input('method'));
        }

        $paymentTotalQuery = clone $query;
        $paymentTotal = $paymentTotalQuery->select('amount', 'is_paid')->get();

        $paidTotal = 0;
        $unpaidTotal = 0;

        foreach($paymentTotal as $payment)
        {
            if ($payment->is_paid){
                $paidTotal += $payment->amount;
            } else{
                $unpaidTotal += $payment->amount;
            }
        }

        if ($request->filled('is_paid'))
        {
            $query->where('is_paid', $request->is_paid);
        }

        $query->orderBy('updated_at', 'desc');

        if ($request->filled('order_by_is_filled') && $request->order_by_is_filled === true)
        {
            $query->orderBy('is_paid', 'desc');
        }

        $payments = $query->paginate(20);
        $payments->appends($request->all());

        return response()->json([
            'paid_total' => $paidTotal,
            'unpaid_total' => $unpaidTotal,
            'total' => $paidTotal + $unpaidTotal,
            'payments' => $payments
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        $fields = $request->validate([
            'amount' => 'integer',
            'method' => 'string|in:cash,hmo',
            'hmo' => 'string',
            'is_paid' => 'boolean'
        ]);

        $originalData = $payment->toArray();

        $payment->update($fields);

        // web socket for updated payment
        event(new PaymentUpdated($payment));

        // audit update of payment
        event(new ModelAction(AuditAction::UPDATE, $request->user(), $payment, $originalData, $request));

        return $payment;
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();

        return response()->json([
            'message' => 'Payment record deleted'
        ], 200);
    }

    public function getTotal(Request $request)
    {
        $request->validate([
            'department_id' => 'integer|exists:departments,id',
            'date_from' => 'date|date_format:Y-m-d',
            'date_until' => 'date|date_format:Y-m-d|after_or_equal:date_from',
        ]);

        $query = Payment::query();

        if($request->filled('department_id'))
        {
            $query->where('department_id', $request->department_id);
        }

        if($request->filled('date_from'))
        {
            $query->where('created_at', '>=', $request->date_from);
        }

        if($request->filled('date_until'))
        {
            $query->where('created_at', '<=', $request->date_until);
        }

        $payments = $query->select('amount', 'is_paid')->get();

        $paidTotal = 0;
        $unpaidTotal = 0;

        foreach($payments as $payment)
        {
            if ($payment->is_paid){
                $paidTotal += $payment->amount;
            } else{
                $unpaidTotal += $payment->amount;
            }
        }
        
        return response()->json([
            'paid_total' => $paidTotal,
            'unpaid_total' => $unpaidTotal,
            'total' => $paidTotal + $unpaidTotal
        ]);
    }
}

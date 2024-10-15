<?php

namespace App\Http\Controllers;

use App\Events\QueueUpdated;
use App\Models\Queue;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (in_array($user->role, ['secretary', 'physician']))
        {
            return Queue::select('department', 'total', 'current', 'waiting', 'completed')->where('department', $user->department)->first();
        }

        return Queue::select('department', 'total')->all();
    }

    public function store(Request $request)
    {
        $request->validate([
            'department' => 'required|string|max:50|unique:queues'
        ]);

        $queue = Queue::create(['department' => $request->department]);

        return $queue;
    }

    public function incrementQueueTotal(Request $request) 
    {
        $request->validate([
            'department' => 'required|string|exists:queues'
        ]);

        $queue = Queue::where('department', $request->department)->first();
        if ($queue->total == 0) {
            $queue->increment('current');
        }
        $queue->increment('total');
        $queue->increment('waiting');
        $queue->save();

        event(new QueueUpdated($queue));

        return $queue;
    }

    public function incrementQueueCurrent(Request $request) 
    {
        $request->validate([
            'department' => 'required|string|exists:queues'
        ]);

        $queue = Queue::where('department', $request->department)->first();
        $queue->increment('current');
        $queue->increment('completed');
        if ($queue->waiting > 0) {
            $queue->waiting--;
        }

        event(new QueueUpdated($queue));

        return $queue;
    }

    // public function clearQueue() 

    public function destroy(Queue $queue)
    {
        $queue->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queue department successfully deleted.'
        ], 200);
    }
}

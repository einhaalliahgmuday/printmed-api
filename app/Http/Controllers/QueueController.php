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
            'department' => 'required|string|max:50'
        ]);

        if (!Queue::where('department', $request->department)->first())
        {
            return Queue::create(['department' => $request->department]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Department already exists in queue.'
        ], 409);
    }

    public function incrementQueueTotal(Request $request) 
    {
        $request->validate([
            'department' => 'required|string|exists:queues'
        ]);

        $queue = Queue::where('department', $request->department)->first();
        if ($queue->total == 0) {
            $queue->current++;
        } 
        if ($queue->total > 0 && $queue->current == null) {
            $total = $queue->total;
            $queue->current = $total+1;
        }
        if ($queue->total > 0 && $queue->total != $queue->completed) {
            $queue->waiting++;
        }
        $queue->total++;

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
        if ($queue->completed < $queue->total)
        {
            if ($queue->current != $queue->completed) {
                $queue->completed++;
            }
            $queue->save();
            if ($queue->current == $queue->total) {
                $queue->current = null;
            } else if ($queue->current < $queue->total) {
                $queue->current++;
            }
            if ($queue->waiting > 0) {
                $queue->waiting--;
            }
            $queue->save();

            event(new QueueUpdated($queue));

            return $queue;
        }

        return response()->json([
            'success' => false,
            'message' => 'Queue is completed or there is no number in queue, cannot increment current.',
            'queue' => $queue
        ]);
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

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
        //current increments to 1 when queue is new or first number is added to queue total, the current becomes 1 too
        if ($queue->total == 0) {
            $queue->current++;
        } 
        //queue current returns to null when all queue is completed
        //thus, when new number is added to queue after completed, that is,
        //queue total is greater than 0 (has started) and current is null (as completed),
        //current becomes the same number as queue total++
        if ($queue->total > 0 && $queue->current == null) {
            $total = $queue->total;
            $queue->current = $total+1;
        }
        //if queue total is greater than 0 (has started) and queue total is NOT 
        //yet completed, queue waiting increments;
        //therefore, if queue total has just started or will increment from completed,
        //queue waiting will not increment, but the current will in the same number as total
        if ($queue->total > 0 && $queue->total != $queue->completed) {
            $queue->waiting++;
        }
        //where total increments
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
        //queue current will not increment if already completed
        if ($queue->completed < $queue->total)
        {
            //queue completed increments as current increments, 
            //given that they're not equal
            if ($queue->current != $queue->completed) {
                $queue->completed++;
            }
            //if queue current is equal to total, that is, the current is the last number
            //queue current returns to null
            if ($queue->current == $queue->total) {
                $queue->current = null;
            } 
            //if queue current is less than total, that is the only time it increments
            else if ($queue->current < $queue->total) {
                $queue->current++;
            }
            //as queue current increments, waiting decrements
            //given that waiting is not 0
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

    public function clearQueue(Request $request) 
    {
        $request->validate([
            'department' => 'required|string|exists:queues'
        ]);

        $queue = Queue::where('department', $request->department)->first();
        
        $queue->total = null;
        $queue->current = null;
        $queue->completed = null;
        $queue->waiting = null;
        $queue->save();

        event(new QueueUpdated($queue));

        return response()->json([
            'success' => true,
            'message' => 'Queue is cleared.',
            'queue' => $queue
        ], 200);
    }

    public function destroy(Queue $queue)
    {
        $queue->delete();

        return response()->json([
            'success' => true,
            'message' => 'Queue department successfully deleted.'
        ], 200);
    }
}

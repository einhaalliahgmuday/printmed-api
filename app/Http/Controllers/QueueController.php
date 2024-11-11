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
            return Queue::where('department_id', $user->department_id)->first() ?: response()->json([]);
        }

        // if user is queue manager
        return Queue::select('id', 'department_id', 'total')->get() ?: response()->json([]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'department_id' => 'required|integer|exists:departments,id'
        ]);

        if (!Queue::where('department_id', $request->department_id)->first())
        {
            return Queue::create(['department_id' => $request->department_id]);
        }

        return response()->json([
            'message' => 'Department already exists in queue.'
        ], 409);
    }

    // must clear queue before deleting
    public function destroy(Queue $queue)
    {
        if ($queue->total !== null) {
            return response()->json([
                'message' => 'Queue  cannot be deleted. Clear the queue first.'
            ], 403);
        }

        $queue->delete();

        return response()->json([
            'message' => 'Queue successfully deleted.'
        ], 200);
    }

    public function clear(Queue $queue) 
    {
        $queue->total = null;
        $queue->current = null;
        $queue->completed = null;
        $queue->waiting = null;
        $queue->save();

        event(new QueueUpdated($queue));

        return $queue;
    }

    public function incrementTotal(Queue $queue) 
    {
        //current increments to 1 when queue is NEW; when FIRST NUMBER is added to queue total, the current becomes 1 too
        if ($queue->total == 0) {
            $queue->current++;
        } 

        //queue current returns to null once all queue is completed
        //thus, when new number is added to queue after completed, that is,
        //queue total is greater than 0 (has started) and current is null (as completed),
        //current jumps to the same number as queue total+1
        if ($queue->total > 0 && $queue->current == null) {
            $total = $queue->total;
            $queue->current = $total+1; 
        }
        
        // if queue total HAS STARTED BUT NOT YET COMPLETED, queue waiting increments;
        // if queue total has JUST started or will increment from completed,
        //queue waiting will not increment, the current will in the same number as total
        if ($queue->total > 0 && $queue->total != $queue->completed) {
            $queue->waiting++;
        }
        
        $queue->total++;

        $queue->save();

        event(new QueueUpdated($queue));

        return $queue;
    }

    public function incrementCurrent(Queue $queue, Request $request) 
    {
        $user = $request->user();
        if ($queue->department_id !== $user->department_id)
        {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        //queue current will not increment if no number in queue or already completed
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
            'message' => 'Queue is completed or there is no number in queue, cannot increment current.',
        ], 400);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Queue;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        return Department::all();
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:50|unique:departments,name'
        ]);

        $department = Department::create($fields);

        return $department;
    }

    public function update(Request $request, Department $department)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:50|unique:departments,name'
        ]);

        $department->update($fields);

        return $department;
    }

    public function destroy(Department $department)
    {
        if ($department->users_count > 0) {
            return response()->json([
                'message' => 'You cannot delete delete this department.'
            ]);
        }

        $department->delete();

        return response()->json(['message' => 'Department successfully deleted.']);
    }
}

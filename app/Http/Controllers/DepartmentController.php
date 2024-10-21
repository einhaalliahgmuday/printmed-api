<?php

namespace App\Http\Controllers;

use App\Models\Department;
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
            'name' => 'required|string|max:100|unique:departments,name'
        ]);

        $department = Department::create($fields);

        return $department;
    }

    public function update(Request $request, Department $department)
    {
        $fields = $request->validate([
            'name' => 'string|max:100|unique:departments,name'
        ]);

        $department->update($fields);

        return $department;
    }

    public function destroy(Department $department)
    {
        $department->delete();
    }
}

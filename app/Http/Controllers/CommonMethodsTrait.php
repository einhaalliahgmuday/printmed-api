<?php 

namespace App\Http\Controllers;

use Illuminate\Http\Request;

trait CommonMethodsTrait
{
    private function getFullName(Request $request) 
    {
        $fullName = null;
        if ($request->filled('suffix') && $request->filled('middle_name')) {
            $fullName = $request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name . ', ' . $request->suffix;
        } else if ($request->filled('middle_name')) {
            $fullName = $request->first_name . ' ' . $request->middle_name . ' ' . $request->last_name;
        } else if ($request->filled('suffix')) {
            $fullName = $request->first_name . ' ' . $request->last_name . ', ' . $request->suffix;
        } else {
            $fullName = $request->first_name . ' ' . $request->last_name;
        }

        return $fullName;
    }
}
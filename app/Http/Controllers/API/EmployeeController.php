<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $query = Employee::with('user.roles');

        // Apply filters
        if ($request->has('department')) {
            $query->where('department', $request->department);
        }

        if ($request->has('position')) {
            $query->where('position', $request->position);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $employees = $query->latest()->paginate(10);

        return response()->json($employees);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|email|unique:employees,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'required|string|max:100',
            'department' => 'required|string|max:100',
            'hire_date' => 'required|date',
            'salary' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'create_user' => 'boolean',
            'user_email' => 'required_if:create_user,true|email|unique:users,email',
            'user_password' => 'required_if:create_user,true|string|min:8',
            'role_ids' => 'required_if:create_user,true|array',
            'role_ids.*' => 'exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            $userId = null;

            // Create user account if requested
            if ($request->create_user) {
                $user = User::create([
                    'name' => $request->first_name . ' ' . $request->last_name,
                    'email' => $request->user_email,
                    'password' => Hash::make($request->user_password),
                    'is_active' => true,
                ]);

                // Assign roles
                $user->roles()->attach($request->role_ids);

                $userId = $user->id;
            }

            // Create employee record
            $employee = Employee::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'position' => $request->position,
                'department' => $request->department,
                'hire_date' => $request->hire_date,
                'salary' => $request->salary,
                'address' => $request->address,
                'is_active' => true,
                'user_id' => $userId,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Employee created successfully',
                'employee' => $employee->load('user.roles')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function show(Employee $employee)
    {
        return response()->json($employee->load('user.roles'));
    }

    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'sometimes|required|string|max:100',
            'last_name' => 'sometimes|required|string|max:100',
            'email' => 'sometimes|required|email|unique:employees,email,' . $employee->id,
            'phone' => 'nullable|string|max:20',
            'position' => 'sometimes|required|string|max:100',
            'department' => 'sometimes|required|string|max:100',
            'hire_date' => 'sometimes|required|date',
            'salary' => 'nullable|numeric|min:0',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $employee->update($request->all());

        // If employee has a user account and status is changed, update user status too
        if ($employee->user_id && $request->has('is_active')) {
            $employee->user()->update(['is_active' => $request->is_active]);
        }

        return response()->json([
            'message' => 'Employee updated successfully',
            'employee' => $employee->fresh()->load('user.roles')
        ]);
    }

    public function destroy(Employee $employee)
    {
        try {
            DB::beginTransaction();

            // If employee has a user account, delete it too
            if ($employee->user_id) {
                $employee->user()->delete();
            }

            $employee->delete();

            DB::commit();

            return response()->json(['message' => 'Employee deleted successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }
}
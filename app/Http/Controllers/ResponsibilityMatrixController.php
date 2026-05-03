<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ResponsibilityMatrixEntry;
use App\Models\User;
use App\Models\VAPLab;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ResponsibilityMatrixController extends Controller
{
    public function index()
    {
        $entries = ResponsibilityMatrixEntry::query()
            ->with([
                'department:id,name',
                'lab:id,name',
                'responsibleUser:id,name',
                'accountableUser:id,name',
            ])
            ->latest()
            ->get();

        return Inertia::render('Responsibilities/Index', [
            'entries' => $entries,
            'departments' => Department::query()->select('id', 'name')->orderBy('name')->get(),
            'labs' => VAPLab::query()->select('id', 'name', 'department_id')->orderBy('name')->get(),
            'users' => User::query()->select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'lab_id' => ['nullable', 'exists:labs,id'],
            'process_area' => ['required', 'string', 'max:255'],
            'activity' => ['required', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'accountable_user_id' => ['nullable', 'exists:users,id'],
            'consulted_roles' => ['nullable', 'string', 'max:1000'],
            'informed_roles' => ['nullable', 'string', 'max:1000'],
            'evidence_requirement' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date'],
        ]);

        ResponsibilityMatrixEntry::query()->create($validated);

        return back()->with('success', 'Entrada da matriz de responsabilidades registada.');
    }

    public function update(Request $request, ResponsibilityMatrixEntry $responsibilityMatrix)
    {
        $validated = $request->validate([
            'department_id' => ['nullable', 'exists:departments,id'],
            'lab_id' => ['nullable', 'exists:labs,id'],
            'process_area' => ['required', 'string', 'max:255'],
            'activity' => ['required', 'string', 'max:255'],
            'responsible_user_id' => ['nullable', 'exists:users,id'],
            'accountable_user_id' => ['nullable', 'exists:users,id'],
            'consulted_roles' => ['nullable', 'string', 'max:1000'],
            'informed_roles' => ['nullable', 'string', 'max:1000'],
            'evidence_requirement' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['sometimes', 'boolean'],
            'effective_from' => ['nullable', 'date'],
            'effective_until' => ['nullable', 'date'],
        ]);

        $responsibilityMatrix->update($validated);

        return back()->with('success', 'Entrada da matriz atualizada.');
    }

    public function destroy(ResponsibilityMatrixEntry $responsibilityMatrix)
    {
        $responsibilityMatrix->delete();

        return back()->with('success', 'Entrada da matriz arquivada.');
    }
}

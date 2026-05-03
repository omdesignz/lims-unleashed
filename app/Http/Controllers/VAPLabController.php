<?php

namespace App\Http\Controllers;

use App\Models\VAPLab;
use App\Models\User;
use App\Models\Department;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;

class VAPLabController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Gate::authorize('view-any', VAPLab::class);
        
        $query = VAPLab::with(['supervisor', 'technicalHead', 'department'])
            // ->byTenant()
            ->latest();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('room_no', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $labs = $query->paginate(20)->withQueryString();

        return Inertia::render('VAPLabs/Index', [
            'labs' => $labs,
            'filters' => $request->only(['search']),
            'stats' => [
                'total' => VAPLab::count(),
                'active' => VAPLab::active()->count(),
                'with_supervisor' => VAPLab::whereNotNull('supervisor_id')->count(),
            ]
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        Gate::authorize('create', VAPLab::class);
        
        $supervisors = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['supervisor', 'admin']);
            })
            ->select('id', 'name', 'email')
            ->get();

        $technicalHeads = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['technical_head', 'admin']);
            })
            ->select('id', 'name', 'email')
            ->get();

        $departments = Department::select('id', 'name', 'code')
            ->get();

        return Inertia::render('VAPLabs/Create', [
            'supervisors' => $supervisors,
            'technicalHeads' => $technicalHeads,
            'departments' => $departments,
            'labsCount' => VAPLab::count(),
            'activeLabsCount' => VAPLab::active()->count(),
            'lastUpdated' => now()->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Gate::authorize('create', VAPLab::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:labs,code',
            'room_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:labs,email',
            'supervisor_id' => 'nullable|exists:users,id',
            'technical_head_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        // $validated['tenant_id'] = Auth::user()->tenant_id;

        VAPLab::create($validated);

        return redirect()->route('vap-labs.labs.index')
            ->with('success', 'Lab created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(VAPLab $lab)
    {
        Gate::authorize('view', $lab);
        
        $lab->load(['supervisor', 'technicalHead', 'department', 'parentLab', 'subLabs']);

        return Inertia::render('VAPLabs/Show', [
            'lab' => $lab,
            'stats' => [
                'total_equipment' => 0,
                'active_tests' => 0,
                'staff_count' => 0,
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(VAPLab $lab)
    {
        // Gate::authorize('update', $lab);
        
        $supervisors = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['supervisor', 'admin']);
            })
            ->select('id', 'name', 'email')
            ->get();

        $technicalHeads = User::whereHas('roles', function ($q) {
                $q->whereIn('name', ['technical_head', 'admin']);
            })
            ->select('id', 'name', 'email')
            ->get();

        $departments = Department::select('id', 'name', 'code')
            ->get();

        return Inertia::render('VAPLabs/Edit', [
            'lab' => $lab,
            'supervisors' => $supervisors,
            'technicalHeads' => $technicalHeads,
            'departments' => $departments,
            'labsCount' => VAPLab::count(),
            'activeLabsCount' => VAPLab::active()->count(),
            'lastUpdated' => $lab->updated_at->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, VAPLab $lab)
    {
        // Gate::authorize('update', $lab);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:255|unique:labs,code,' . $lab->id,
            'room_no' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'contact' => 'nullable|string|max:255',
            'extension' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:labs,email,' . $lab->id,
            'supervisor_id' => 'nullable|exists:users,id',
            'technical_head_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        $lab->update($validated);

        return redirect()->route('vap-labs.labs.index')
            ->with('success', 'Lab updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VAPLab $lab)
    {
        // Gate::authorize('delete', $lab);
        
        $lab->delete();

        return redirect()->route('vap-labs.labs.index')
            ->with('success', 'Lab deleted successfully.');
    }

    /**
     * Restore a soft-deleted lab.
     */
    public function restore($id)
    {
        $lab = VAPLab::withTrashed()->findOrFail($id);
        // Gate::authorize('restore', $lab);
        
        $lab->restore();

        return redirect()->route('vap-labs.labs.index')
            ->with('success', 'Lab restored successfully.');
    }

    /**
     * Get labs for dropdown/select options
     */
    public function getLabOptions()
    {
        // Gate::authorize('view-any', VAPLab::class);
        
        $labs = VAPLab::active()
            ->select('id', 'name', 'code')
            ->get()
            ->map(function ($lab) {
                return [
                    'value' => $lab->id,
                    'label' => "{$lab->name} ({$lab->code})",
                ];
            });

        return response()->json($labs);
    }
}
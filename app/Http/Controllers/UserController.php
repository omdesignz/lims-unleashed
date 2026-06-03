<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPasswordRequest;
use App\Http\Requests\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Permission;
use App\Models\PersonnelQualification;
use App\Models\Role;
use App\Models\User;
use App\Settings\GeneralSettings;
use App\Support\PdfResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use PDF;

class UserController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function indexPayload(bool $openCreate = false): array
    {
        $users = User::query()
            ->with(['personnelQualifications'])
            ->when(request()->input('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when(request()->input('filter'), function ($query, $filter) {
                if ($filter === 'trashed') {
                    $query->withTrashed();
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $userCollection = $users->getCollection();

        return [
            'record' => UserResource::collection($users),
            'slideOverEdit' => false,
            'openCreate' => $openCreate,
            'fields' => [
                [
                    'name' => trans('gestlab.general.labels.users.name'),
                    'value' => 'name',
                ],
                [
                    'name' => trans('gestlab.general.labels.users.email'),
                    'value' => 'email',
                ],
                [
                    'name' => trans('gestlab.general.labels.users.username'),
                    'value' => 'username',
                ],
            ],
            'model' => User::MENU_NAME,
            'abilities' => method_exists(User::class, 'getAbilities') ? collect(User::ABILITIES)->map(function ($item) {
                return $item.'_'.User::MENU_NAME;
            }) : collect(config('gestlab.default_abilities'))->map(function ($item) {
                return $item.'_'.User::MENU_NAME;
            }),
            'query' => request()->only(['search', 'trashed']),
            'competenceSummary' => [
                'tracked_users' => $userCollection->filter(fn (User $user) => $user->personnelQualifications->isNotEmpty())->count(),
                'expired_qualifications' => $userCollection->sum(fn (User $user) => collect($user->competenceSummary()['qualifications'])->where('status', 'expired')->count()),
                'expiring_soon' => $userCollection->sum(fn (User $user) => collect($user->competenceSummary()['qualifications'])->whereIn('status', ['expiring_critical', 'expiring_soon'])->count()),
                'ready_for_renewal' => $userCollection->sum(fn (User $user) => collect($user->competenceSummary()['qualifications'])->where('renewal_readiness', 'ready_for_review')->count()),
                'missing_evidence' => $userCollection->sum(fn (User $user) => collect($user->competenceSummary()['qualifications'])->where('renewal_readiness', 'missing_evidence')->count()),
            ],
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        abort_if(! auth()->user()->can('view_users'), 403, '');

        return Inertia::render('Users/Index', $this->indexPayload());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        abort_if(! auth()->user()->can('add_users'), 403, '');

        return Inertia::render('Users/Index', $this->indexPayload(true));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        abort_if(! auth()->user()->can('add_users'), 403, '');

        // dd($request->all());

        DB::transaction(function () use ($request): void {

            // Persiste data to DB
            $user = User::create($request->safe()->except(['departments']));

            $user->departments()->sync(collect($request->departments)->pluck('department_id')->unique()->toArray());

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_created'),
            ],
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_if(! auth()->user()->can('edit_users'), 403, '');

        // Find the record
        $record = User::with(['departments', 'permissions', 'roles', 'personnelQualifications.department', 'personnelQualifications.qualifiedBy:id,name'])->findOrFail($id);

        $qualifications = $record->personnelQualifications
            ->sortBy([
                fn (PersonnelQualification $qualification) => $qualification->monitoringStatus(),
                fn (PersonnelQualification $qualification) => $qualification->authorized_until?->timestamp ?? PHP_INT_MAX,
            ])
            ->values();

        // Return Inertia View with record data
        return Inertia::render('Users/Edit', [
            'record' => [
                'id' => $record->id,
                'name' => $record->name,
                'username' => $record->username,
                'gender' => $record->gender,
                'email' => $record->email,
                'dob' => $record->dob?->format('Y-m-d'),
                'profile_photo_url' => $record->profile_photo_url,
                'signature_url' => $record->signature_url,
                'primary_phone' => $record->primary_phone,
                'secondary_phone' => $record->secondary_phone,
                'id_number' => $record->id_number,
                'departments' => collect($record->departments)->map(function ($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['name'],
                    ];
                })->toArray(),
                'permissions' => collect($record->permissions)->map(function ($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['label'],
                    ];
                })->toArray(),
                'roles' => collect($record->roles)->map(function ($item) {
                    return [
                        'value' => $item['id'],
                        'label' => $item['label'],
                    ];
                })->toArray(),
                'personnel_qualifications' => $qualifications->map(function ($qualification) {
                    return [
                        'id' => $qualification->id,
                        'capability' => $qualification->capability,
                        'department_id' => $qualification->department ? [
                            'value' => $qualification->department->id,
                            'label' => $qualification->department->name,
                        ] : null,
                        'authorized_from' => $qualification->authorized_from?->format('Y-m-d'),
                        'authorized_until' => $qualification->authorized_until?->format('Y-m-d'),
                        'training_completed_at' => $qualification->training_completed_at?->format('Y-m-d'),
                        'training_reference' => $qualification->training_reference,
                        'notes' => $qualification->notes,
                        'is_active' => $qualification->is_active,
                        'monitoring_status' => $qualification->monitoringStatus(),
                        'renewal_readiness' => $qualification->renewalReadiness(),
                        'days_until_expiry' => $qualification->daysUntilExpiry(),
                        'follow_up_due_at' => $qualification->followUpDueAt()?->toDateString(),
                        'follow_up_state' => $qualification->followUpState(),
                        'qualified_by' => $qualification->qualifiedBy?->name,
                    ];
                })->values()->toArray(),
            ],
            'competenceSummary' => $record->competenceSummary(),
            'permissions' => collect(Permission::all())->map(function ($item) {
                return [
                    'value' => $item['id'],
                    'label' => $item['label'],
                ];
            })->toArray(),
            'roles' => collect(Role::all())->map(function ($item) {
                return [
                    'value' => $item['id'],
                    'label' => $item['label'],
                ];
            })->toArray(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, $id)
    {
        abort_if(! auth()->user()->can('edit_users'), 403, '');

        // dd(collect($request->departments)->pluck('department_id')->unique()->toArray());

        DB::transaction(function () use ($request, $id): void {

            tap(User::findOrFail($id), function ($record) use ($request) {

                $record->update($request->safe()->except(['departments']));

                $record->departments()->sync(collect($request->departments)->pluck('department_id')->unique()->toArray());

                // $record->syncRoles(Role::find($request->roles));

                // $record->syncPermissions(Permission::find($request->roles));

                $record->syncPermissions(collect($request->permissions)->unique()->toArray());
                $record->syncRoles(collect($request->roles)->unique()->toArray());

                $record->personnelQualifications()->delete();

                foreach ($request->input('personnel_qualifications', []) as $qualification) {
                    $record->personnelQualifications()->create(array_merge($qualification, [
                        'qualified_by_id' => auth()->id(),
                    ]));
                }

            });

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function setPass(UserPasswordRequest $request, $id)
    {

        DB::transaction(function () use ($request, $id): void {

            User::findOrFail($id)->forceFill([
                'password' => Hash::make($request->password),
            ])->save();

        });

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function setSignature()
    {

        // dd(auth()->user()->id);

        User::findOrFail(auth()->user()->id)->addMediaFromBase64(request()->signature)
            ->usingFileName(auth()->user()->id.'_signature.png')
            ->toMediaCollection('signature');

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function setDashboardHeader()
    {

        // dd(request()->photo);

        request()->validate([
            'photo' => 'required|file|image|max:5120',
        ]);

        User::findOrFail(auth()->user()->id)->addMedia(request()->photo)
            ->preservingOriginal()
            ->toMediaCollection('dashboard_header_image');

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_updated'),
            ],
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function unsetSignature()
    {

        // dd(request()->all());

        User::findOrFail(auth()->user()->id)->getMedia('signature')->first()?->delete();

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy()
    {
        abort_if(! auth()->user()->can('delete_users'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and delete the record
        foreach (User::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->delete();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_deleted'),
            ],
        ]);
    }

    /**
     * restore the specified resource from storage.
     */
    public function restore()
    {
        abort_if(! auth()->user()->can('restore_users'), 403, '');

        request()->validate([
            'recordIds' => ['required', 'array'],
        ]);
        // Find and restore the record
        foreach (User::withTrashed()->findOrFail(request('recordIds')) as $record) {
            $record->restore();
        }

        return redirect()->back()->with([
            'toast' => [
                'title' => trans('gestlab.toasts.notification'),
                'message' => trans('gestlab.toasts.record_successfully_restored'),
            ],
        ]);
    }

    public function toggleActiveStatus(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        if ($user->is_active) {
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('Activou o usuário '.$user->name);

            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.record_successfully_activated'),
                ],
            ]);

        } else {
            activity()
                ->performedOn($user)
                ->causedBy(auth()->user())
                ->log('Desactivou o usuário '.$user->full_name);

            return redirect()->back()->with([
                'toast' => [
                    'title' => trans('gestlab.toasts.notification'),
                    'message' => trans('gestlab.toasts.record_successfully_deactivated'),
                ],
            ]);

        }

        return back();
    }

    /**
     * Display a listing of the resource.
     */
    public function profile()
    {
        // Get any required data

        // Load Inertia view

        return Inertia::render('Profile/Show', [
            'name' => auth()->user()->name,
            'username' => auth()->user()->username,
            'phone' => auth()->user()->phone,
            'alternate_phone' => auth()->user()->alternate_phone,
            'id_number' => auth()->user()->id_number,
            'gender' => auth()->user()->gender,
            'dob' => auth()->user()->dob->format('Y-m-d'),
            'email' => auth()->user()->email,
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function help()
    {
        // Get any required data

        // Load Inertia view

        return Inertia::render('User/Help', []);
    }

    /**
     * @return array<string, mixed>
     */
    private function userManualFontOptions(): array
    {
        $regularFont = $this->firstExistingFont([
            resource_path('fonts/Century Gothic.ttf'),
            storage_path('app/fonts/Century Gothic.ttf'),
            $this->homeFontPath('Library/Fonts/Century Gothic.ttf'),
            '/Library/Fonts/Century Gothic.ttf',
            '/System/Library/Fonts/Supplemental/Century Gothic.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Century_Gothic.ttf',
        ]);

        if ($regularFont === null) {
            return [];
        }

        $boldFont = $this->firstExistingFont([
            resource_path('fonts/Century Gothic Bold.ttf'),
            storage_path('app/fonts/Century Gothic Bold.ttf'),
            $this->homeFontPath('Library/Fonts/Century Gothic Bold.ttf'),
            $this->homeFontPath('Library/Fonts/ufonts.com_century-gothic-bold.ttf'),
            '/Library/Fonts/Century Gothic Bold.ttf',
            '/System/Library/Fonts/Supplemental/Century Gothic Bold.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Century_Gothic_Bold.ttf',
        ]) ?? $regularFont;

        $fontDirectories = (new ConfigVariables)->getDefaults()['fontDir'];
        $fontData = (new FontVariables)->getDefaults()['fontdata'];

        return [
            'fontDir' => array_values(array_unique([
                ...$fontDirectories,
                dirname($regularFont),
                dirname($boldFont),
            ])),
            'fontdata' => $fontData + [
                'centurygothic' => [
                    'R' => basename($regularFont),
                    'B' => basename($boldFont),
                ],
            ],
            'default_font' => 'centurygothic',
        ];
    }

    private function homeFontPath(string $relativePath): ?string
    {
        $homeDirectory = rtrim((string) ($_SERVER['HOME'] ?? getenv('HOME') ?: ''), DIRECTORY_SEPARATOR);

        return $homeDirectory !== '' ? $homeDirectory.DIRECTORY_SEPARATOR.$relativePath : null;
    }

    /**
     * @param  array<int, string|null>  $paths
     */
    private function firstExistingFont(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (is_string($path) && $path !== '' && is_file($path)) {
                return str_replace('\\', '/', $path);
            }
        }

        return null;
    }

    public function manualPdf(GeneralSettings $settings)
    {
        $pdf = PDF::loadView('PDFs.user-manual', [
            'settings' => $settings,
            'generatedAt' => now(),
        ], [], [
            ...[
                'format' => 'A4',
                'orientation' => 'P',
                'margin_top' => 14,
                'margin_bottom' => 18,
                'margin_left' => 12,
                'margin_right' => 12,
                'margin_footer' => 8,
                'title' => 'Manual do Utilizador',
                'author' => auth()->user()?->name,
                'display_mode' => 'fullpage',
            ],
            ...$this->userManualFontOptions(),
        ]);

        return PdfResponse::download($pdf, 'manual-do-utilizador-gestlab.pdf');
    }

    /**
     * Display a listing of the resource.
     */
    public function security()
    {
        // Get any required data

        // Load Inertia view

        return Inertia::render('User/Security', []);
    }

    /**
     * Display the specified resource.
     */
    public function impersonate()
    {
        abort_if(! auth()->user()->can('impersonate_users'), 403, '');

        // dd(collect(request()->id)->first()['id']);
        // dd(request()->id);

        $originalId = auth()->user()->id;

        session()->put('impersonate', $originalId);

        auth()->loginUsingId(request()->id);

        return to_route('dashboard');
    }

    /**
     * Display the specified resource.
     */
    public function leave()
    {

        if (! session()->has('impersonate')) {
            abort(403);
        }

        auth()->loginUsingId(session('impersonate'));

        session()->forget('impersonate');

        return to_route('dashboard');
    }

    public function getUser()
    {
        $data = [];

        if (request()->has('q')) {
            $search = request()->q;

            $data = DB::table('users')
                ->select('users.*')
                ->where('name', 'LIKE', "%$search%")
                ->orWhere('email', 'LIKE', "%$search%")
                ->get();
        }

        return response()->json($data);
    }
}

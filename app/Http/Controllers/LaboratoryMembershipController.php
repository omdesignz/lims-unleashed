<?php

namespace App\Http\Controllers;

use App\Actions\ManageLaboratoryMembership;
use App\Http\Requests\LaboratoryMembershipRequest;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\RedirectResponse;

class LaboratoryMembershipController extends Controller
{
    public function __construct(private readonly SampleLaboratoryAccess $access) {}

    public function store(LaboratoryMembershipRequest $request, ManageLaboratoryMembership $membership): RedirectResponse
    {
        $membership->join($request->user()->id, $this->access->activeLabId(), $request->validated('email'));

        return redirect()->back()->with('toast', ['title' => 'Equipa do laboratório', 'message' => 'Adesão ao laboratório confirmada.']);
    }

    public function destroy(LaboratoryMembershipRequest $request, int $user, ManageLaboratoryMembership $membership): RedirectResponse
    {
        $membership->remove($request->user()->id, $this->access->activeLabId(), $user);

        return redirect()->back()->with('toast', ['title' => 'Equipa do laboratório', 'message' => 'Adesão removida. A conta partilhada e a evidência foram preservadas.']);
    }
}

<?php

namespace App\Http\Controllers;

use App\Actions\IssuePortalServiceInvitation;
use App\Http\Requests\IssuePortalServiceInvitationRequest;
use App\Services\SampleLaboratoryAccess;
use Illuminate\Http\RedirectResponse;

class PortalServiceInvitationController extends Controller
{
    public function store(IssuePortalServiceInvitationRequest $request, SampleLaboratoryAccess $access, IssuePortalServiceInvitation $issue): RedirectResponse
    {
        $issue->execute($access->activeLabId(), $request->user()->id, $request->validated());

        return back()->with('toast', ['title' => 'Convite emitido', 'message' => 'O convite está disponível na conta do portal durante 30 dias.']);
    }
}

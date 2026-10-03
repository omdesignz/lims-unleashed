<?php

namespace App\Actions;

use App\Services\ProposalPdfDocument;
use App\Services\PublicProposalAccess;

class DownloadPublicProposalPdf
{
    public function __construct(private readonly PublicProposalAccess $access, private readonly ProposalPdfDocument $document) {}

    /** @return array{content: string, renderer: string, filename: string, fingerprint: string} */
    public function execute(string $hash): array
    {
        $proposal = $this->access->read($hash);
        $rendered = $this->document->render($proposal);
        $this->document->assertUnchanged($this->access->current($proposal), $rendered['fingerprint']);

        return $rendered;
    }
}

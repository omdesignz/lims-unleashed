<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class ProposalLocalizationTest extends TestCase
{
    public function test_all_explicit_proposal_translation_keys_are_defined(): void
    {
        $files = [
            resource_path('js/Pages/VAPProposals/Index.vue'),
            resource_path('js/Pages/VAPProposals/Create.vue'),
            resource_path('js/Pages/VAPProposals/Edit.vue'),
            resource_path('js/Pages/VAPProposals/Show.vue'),
            resource_path('js/Pages/VAPProposalTemplates/Index.vue'),
            resource_path('js/Pages/VAPProposalTemplates/Create.vue'),
            resource_path('js/Pages/VAPProposalTemplates/Edit.vue'),
            resource_path('js/Pages/VAPProposalTemplates/Show.vue'),
            resource_path('js/Components/proposal-template/studio-workbench.vue'),
        ];

        $missingKeys = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);

            preg_match_all('/(?:\\$t|trans)\(\s*[\'\"](gestlab\.[^\'\"]+)[\'\"]/', $source, $matches);

            foreach (array_unique($matches[1]) as $key) {
                if (! str_ends_with($key, '.') && ! Lang::has($key, 'pt')) {
                    $missingKeys[] = sprintf('%s: %s', basename($file), $key);
                }
            }
        }

        $this->assertSame([], $missingKeys, "Missing Portuguese proposal translations:\n".implode("\n", $missingKeys));
    }
}

<?php

namespace Tests\Unit;

use App\Support\LabelStudioLogoSource;
use Tests\TestCase;

class LabelStudioLogoSourceTest extends TestCase
{
    public function test_public_raster_assets_are_embedded_without_exposing_file_paths(): void
    {
        $source = new LabelStudioLogoSource;
        $png = file_get_contents(public_path('images/governo_novo.png'));
        $jpeg = file_get_contents(public_path('images/sqna_invoice.jpg'));

        $this->assertIsString($png);
        $this->assertIsString($jpeg);
        $this->assertSame('data:image/png;base64,'.base64_encode($png), $source->resolve('/images/governo_novo.png'));
        $this->assertSame('data:image/jpeg;base64,'.base64_encode($jpeg), $source->resolve('images/sqna_invoice.jpg'));
        $this->assertSame('data:image/png;base64,'.base64_encode($png), $source->resolve('data:image/png;base64,'.base64_encode($png)));
    }

    public function test_remote_private_and_non_raster_sources_are_rejected(): void
    {
        $source = new LabelStudioLogoSource;

        foreach ([
            'https://example.test/logo.png',
            'file:///etc/passwd',
            '../.env',
            '/../../.env',
            'images/../../../.env',
            'data:image/svg+xml;base64,'.base64_encode('<svg/>'),
            'data:image/png;base64,'.base64_encode('not an image'),
            'index.php',
            'data:image/png;base64,'.base64_encode(str_repeat('x', 2_000_001)),
        ] as $path) {
            $this->assertNull($source->resolve($path), $path);
        }
    }
}

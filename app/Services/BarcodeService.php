<?php

namespace App\Services;

use Milon\Barcode\DNS1D;
use Illuminate\Support\Facades\Storage;

class BarcodeService
{
    protected $barcode;

    public function __construct()
    {
        $this->barcode = new DNS1D();
    }

    public function generateCode128($data, $width = 2, $height = 30)
    {
        return $this->barcode->getBarcodePNG($data, 'C128', $width, $height);
    }

    public function generateCode39($data, $width = 2, $height = 30)
    {
        return $this->barcode->getBarcodePNG($data, 'C39', $width, $height);
    }

    public function generateAndSave($data, $type = 'C128', $filename = null)
    {
        $filename = $filename ?? 'barcode_' . time() . '.png';
        $path = 'barcodes/' . $filename;
        
        $barcode = $this->barcode->getBarcodePNG($data, $type);
        $image = base64_decode($barcode);
        
        Storage::disk('public')->put($path, $image);
        
        return $path;
    }

    public function generateForItem($item)
    {
        $data = $item->barcode ?: $item->code ?: $item->id;
        
        if (!$data) {
            $data = 'ITEM-' . str_pad($item->id, 8, '0', STR_PAD_LEFT);
        }
        
        return $this->generateAndSave($data, 'C128', 'item_' . $item->id . '.png');
    }

    public function generateBase64($data, $type = 'C128')
    {
        return $this->barcode->getBarcodePNG($data, $type);
    }
}
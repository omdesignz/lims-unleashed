<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Writer\Html;

class TiptapController extends Controller
{

public function import(Request $request)
{
    $request->validate(['file' => 'required|mimes:docx']);
    $filePath = $request->file('file')->store('uploads');
    $phpWord = IOFactory::load(storage_path('app/' . $filePath));
    
    $html = IOFactory::createWriter($phpWord, 'HTML')->getContent();
    return response()->json(['html' => $html]);
}

public function export(Request $request)
{
    $html = $request->input('html');
    $phpWord = new PhpWord();
    $section = $phpWord->addSection();
    Html::addHtml($section, $html);

    $filePath = 'exports/document.docx';
    $phpWord->save(storage_path($filePath));
    return response()->download(storage_path($filePath));
}

}

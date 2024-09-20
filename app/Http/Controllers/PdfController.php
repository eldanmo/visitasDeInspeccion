<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PdfController extends Controller
{
    public function show()
    {
        $filePath = public_path('pdfjs/web/compressed.tracemonkey-pldi-09.pdf');

        if (file_exists($filePath)) {
            return response()->file($filePath);
        } else {
            abort(404, 'Archivo no encontrado.');
        }
    }
}

<?php

namespace App\Http\Controllers;
use App\Models\VisitaInspeccion; 

use Illuminate\Http\Request;

class InformeController extends Controller
{
    public function consultar()
    {
        return view('consultar_informe', ['informes' => VisitaInspeccion::paginate(10)]);
    }
}

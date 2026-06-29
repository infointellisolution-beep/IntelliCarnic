<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Familia;
use App\Models\Articulo;

class VenderController extends Controller
{
    public function tactil()
    {
        $familias = Familia::all();
        $articulos = Articulo::with('familia')->get();
        $settings = \App\Models\Setting::values();

        return view('vender.tactil', compact('familias', 'articulos', 'settings'));
    }

    public function normal()
    {
        $articulos = Articulo::all();
        $settings = \App\Models\Setting::values();
        
        return view('vender.normal', compact('articulos', 'settings'));
    }
}

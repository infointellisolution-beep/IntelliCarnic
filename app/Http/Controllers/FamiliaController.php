<?php

namespace App\Http\Controllers;

use App\Models\Familia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FamiliaController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'familia_nombre' => ['required', 'string', 'max:120', Rule::unique('familias', 'nombre')],
            'familia_descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        Familia::create([
            'nombre' => $data['familia_nombre'],
            'descripcion' => $data['familia_descripcion'] ?? null,
        ]);

        return redirect()
            ->route('articulos.index')
            ->with('status', 'Familia creada correctamente.');
    }
}
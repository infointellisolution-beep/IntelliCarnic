<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ConfiguracionController extends Controller
{
    private const PROTECTED_ADMIN_EMAIL = 'admin@gmail.com';

    public function index(): View
    {
        return view('configuracion.index', [
            'settings' => Setting::values(),
            'users' => User::query()->orderBy('name', 'asc')->get(),
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'unidad_peso' => ['required', Rule::in(['kg', 'lb'])],
            'iva_global_enabled' => ['nullable', 'boolean'],
            'iva_global_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        Setting::setValue('unidad_peso', $data['unidad_peso']);
        Setting::setValue('iva_global_enabled', $request->boolean('iva_global_enabled') ? '1' : '0');
        Setting::setValue('iva_global_rate', $data['iva_global_rate']);

        return redirect()
            ->route('configuracion.index')
            ->with('status', 'Configuración general guardada correctamente.');
    }

    public function updateEmpresa(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'empresa_nombre' => ['nullable', 'string', 'max:255'],
            'empresa_direccion' => ['nullable', 'string', 'max:500'],
            'empresa_correo' => ['nullable', 'email', 'max:255'],
            'empresa_celular' => ['nullable', 'string', 'max:50'],
        ]);

        Setting::setValue('empresa_nombre', $data['empresa_nombre'] ?? '');
        Setting::setValue('empresa_direccion', $data['empresa_direccion'] ?? '');
        Setting::setValue('empresa_correo', $data['empresa_correo'] ?? '');
        Setting::setValue('empresa_celular', $data['empresa_celular'] ?? '');

        if ($request->boolean('empresa_logo_remove')) {
            $oldLogo = Setting::getValue('empresa_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }
            Setting::setValue('empresa_logo', '');
        }

        if ($request->hasFile('empresa_logo')) {
            $request->validate([
                'empresa_logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
            ]);

            $oldLogo = Setting::getValue('empresa_logo');
            if ($oldLogo) {
                Storage::disk('public')->delete($oldLogo);
            }

            $path = $request->file('empresa_logo')->store('empresa', 'public');
            Setting::setValue('empresa_logo', $path);
        }

        return redirect()
            ->route('configuracion.index', ['tab' => 'empresa'])
            ->with('status', 'Información de la empresa guardada correctamente.');
    }

    public function storeUser(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('configuracion.index')
            ->with('status', 'Usuario creado correctamente.');
    }

    public function updateUser(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()
            ->route('configuracion.index', ['tab' => 'users'])
            ->with('status', 'Usuario actualizado correctamente.');
    }

    public function destroyUser(User $user): RedirectResponse
    {
        if ($this->isProtectedAdmin($user)) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'users'])
                ->withErrors(['user' => 'El usuario administrador no se puede eliminar.']);
        }

        if (Auth::id() === $user->id) {
            return redirect()
                ->route('configuracion.index', ['tab' => 'users'])
                ->withErrors(['user' => 'No puedes eliminar tu propio usuario mientras tienes la sesión activa.']);
        }

        User::destroy($user->getKey());

        return redirect()
            ->route('configuracion.index', ['tab' => 'users'])
            ->with('status', 'Usuario eliminado correctamente.');
    }

    private function isProtectedAdmin(User $user): bool
    {
        return strtolower($user->email) === self::PROTECTED_ADMIN_EMAIL;
    }
}
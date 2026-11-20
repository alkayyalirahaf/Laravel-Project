<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Models\Airline;
use App\Models\AirlineAdmin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AirlineAdminController
{
    public function index()
    {
        $airlineAdmins = AirlineAdmin::with(['user', 'airline'])->latest()->paginate(15);

        return view('super-admin.airline-admins.index', compact('airlineAdmins'));
    }

    public function create()
    {
        $airlines = Airline::orderBy('name')->get();

        return view('super-admin.airline-admins.create', compact('airlines'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'airline_id' => ['required', 'exists:airlines,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            AirlineAdmin::create([
                'user_id' => $user->id,
                'airline_id' => $data['airline_id'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
            ]);
        });

        return redirect()->route('admin.airline-admins')->with('success', 'Airline admin created.');
    }

    public function edit(AirlineAdmin $airlineAdmin)
    {
        $airlineAdmin->load('user');
        $airlines = Airline::orderBy('name')->get();

        return view('super-admin.airline-admins.edit', compact('airlineAdmin', 'airlines'));
    }

    public function update(Request $request, AirlineAdmin $airlineAdmin)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($airlineAdmin->user_id)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'airline_id' => ['required', 'exists:airlines,id'],
            'phone' => ['nullable', 'string', 'max:30'],
            'job_title' => ['nullable', 'string', 'max:100'],
        ]);

        DB::transaction(function () use ($data, $airlineAdmin) {
            $airlineAdmin->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if (!empty($data['password'])) {
                $airlineAdmin->user->update(['password' => $data['password']]);
            }

            $airlineAdmin->update([
                'airline_id' => $data['airline_id'],
                'phone' => $data['phone'] ?? null,
                'job_title' => $data['job_title'] ?? null,
            ]);
        });

        return redirect()->route('admin.airline-admins')->with('success', 'Airline admin updated.');
    }

    public function destroy(AirlineAdmin $airlineAdmin)
    {
        DB::transaction(function () use ($airlineAdmin) {
            $user = $airlineAdmin->user;
            $airlineAdmin->delete();

            if ($user && !$user->customer && !$user->superAdmin) {
                $user->delete();
            }
        });

        return redirect()->route('admin.airline-admins')->with('success', 'Airline admin deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = Company::first() ?? new Company();

        return view('company.edit', compact('company'));
    }

    public function update(Request $request)
    {
        $company = Company::first() ?? new Company();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'representative_name' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'invoice_registration_number' => ['nullable', 'string', 'regex:/\AT[0-9]{13}\z/'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_branch' => ['nullable', 'string', 'max:255'],
            'bank_account_type' => ['nullable', 'string', 'max:10'],
            'bank_account_number' => ['nullable', 'string', 'max:20'],
            'bank_account_holder' => ['nullable', 'string', 'max:255'],
            'seal_image' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('seal_image')) {
            if ($company->seal_image_path) {
                Storage::disk('public')->delete($company->seal_image_path);
            }
            $data['seal_image_path'] = $request->file('seal_image')->store('seals', 'public');
        }

        unset($data['seal_image']);

        if ($company->exists) {
            $company->update($data);
        } else {
            Company::create($data);
        }

        return redirect()->route('company.edit')->with('status', '事業者情報を保存しました');
    }
}
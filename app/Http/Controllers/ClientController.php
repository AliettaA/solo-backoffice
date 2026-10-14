<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ClientController extends Controller
{
    public function index()
    {
        $clients = Client::orderBy('name')->paginate(20);
        return view('clients.index', compact('clients'));
    }

    public function create()
    {
        return view('clients.form', ['client' => new Client()]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        Client::create($data);
        return redirect()->route('clients.index')->with('status', '取引先を登録しました');
    }

    public function edit(Client $client)
    {
        return view('clients.form', compact('client'));
    }

    public function update(Request $request, Client $client)
    {
        $data = $this->validateData($request, $client);
        $client->update($data);
        return redirect()->route('clients.index')->with('status', '取引先を更新しました');
    }

    public function destroy(Client $client)
    {
        if ($client->payments()->exists() || \App\Models\LumberInvoice::where('client_id', $client->id)->exists()) {
            throw ValidationException::withMessages(['name' => '取引で使用中のため削除できません。']);
        }
        $client->delete();
        return redirect()->route('clients.index')->with('status', '取引先を削除しました');
    }

    private function validateData(Request $request, ?Client $client = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('clients', 'name')->ignore($client?->id)],
            'honorific' => ['nullable', 'string', 'max:10'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'address' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'invoice_registration_number' => ['nullable', 'string', 'regex:/\AT[0-9]{13}\z/'],
            'memo' => ['nullable', 'string'],
        ], [
            'invoice_registration_number.regex' => '登録番号は半角のTと13桁の数字で入力してください。',
        ]);
    }
}
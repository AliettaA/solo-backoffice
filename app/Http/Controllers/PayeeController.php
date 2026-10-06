<?php

namespace App\Http\Controllers;

use App\Models\Payee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PayeeController extends Controller
{
    public function index()
    {
        $payees = Payee::orderBy('name')->get();

        return view('payees.index', compact('payees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('payees', 'name')],
            'memo' => ['nullable', 'string'],
        ]);

        Payee::create($data);

        return back()->with('status', '支払先を追加しました');
    }

    public function update(Request $request, Payee $payee)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('payees', 'name')->ignore($payee->id)],
            'memo' => ['nullable', 'string'],
        ]);

        $payee->update($data);

        return back()->with('status', '支払先を更新しました');
    }

    public function destroy(Payee $payee)
    {
        if (\App\Models\JournalEntryLine::where('counterparty_name', $payee->name)->exists()) {
            throw ValidationException::withMessages(['name' => '振替伝票で使用中の支払先は削除できません。']);
        }
        $payee->delete();

        return back()->with('status', '支払先を削除しました');
    }
}
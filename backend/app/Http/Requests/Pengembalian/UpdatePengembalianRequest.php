<?php

namespace App\Http\Requests\Pengembalian;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tanggal_pengembalian' => 'required|date',
            'kondisi_buku' => 'required|string|max:255',
            'denda' => 'nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'tanggal_pengembalian' => 'Tanggal pengembalian wajib diisi.',
            'kondisi_kembali' => 'Kondisi barang kembali',
            'denda' => 'Denda harus berupa angka.',
        ];
    }
}

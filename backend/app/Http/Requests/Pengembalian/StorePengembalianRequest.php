<?php

namespace App\Http\Requests\Pengembalian;

use illuminate\Foundation\Http\FormRequest;
use illuminate\Validation\Rule;

class StorePengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_peminjaman' => ['required', 'integer', Rule::exists('peminjaman', 'id')],
            'tanggal_pengembalian' => ['required', 'date'],
            'kondisi_barang' => ['required', Rule::in(['baik', 'rusak'])],
            'denda' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_peminjaman' => 'ID Peminjaman',
            'tanggal_pengembalian' => 'Tanggal Pengembalian',
            'kondisi_barang' => 'Kondisi Barang Kembali',
            'denda' => 'Denda',
        ];
    }
}

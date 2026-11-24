<?php

namespace App\Http\Requests;

use App\Support\ProductSorting;
use Illuminate\Foundation\Http\FormRequest;

class BrandShowRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** Ommaviy GET sahifa: begona qiymat 302/422 emas, jimgina standart saralashga tushadi. */
    public function rules(): array
    {
        return [];
    }

    public function sort(): string
    {
        return ProductSorting::resolve($this->query('sort'));
    }
}

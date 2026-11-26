<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductViewService
{
    /** Ko'rishlar soni: bir sessiyada bir marta, updated_at ga va observerlarga tegmaydi. */
    public function record(Product $product, Request $request): void
    {
        if (! $request->hasSession()) {
            return;
        }

        $seen = (array) $request->session()->get('viewed_products', []);

        if (in_array($product->id, $seen, true)) {
            return;
        }

        DB::table('products')->where('id', $product->id)->increment('views');
        $request->session()->push('viewed_products', $product->id);

        $product->setAttribute('views', (int) $product->views + 1)->syncOriginalAttribute('views');
    }
}

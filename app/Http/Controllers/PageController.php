<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/** Statik sahifalar (Refund, Delivery, Privacy, Terms, Security). Matn config/pages.php da. */
class PageController extends Controller
{
    public function __invoke(string $page): View
    {
        $data = config('pages.pages.'.$page);
        abort_unless(is_array($data), 404);

        $replace = [
            ':phone' => (string) config('pages.support_phone'),
            ':email' => (string) (config('pages.support_email') ?: config('pages.support_phone')),
            ':hours' => (string) config('pages.refund_hours'),
        ];

        array_walk_recursive($data, function (&$value) use ($replace): void {
            if (is_string($value)) {
                $value = strtr($value, $replace);
            }
        });

        $others = collect(config('pages.pages'))
            ->except($page)
            ->map(fn (array $item, string $slug): array => ['title' => $item['title'], 'url' => route('pages.show', $slug)])
            ->values()
            ->all();

        return view('pages.static', [
            'page' => $data,
            'others' => $others,
            'updatedAt' => config('pages.updated_at'),
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => route('home')],
                ['label' => $data['title'], 'url' => null],
            ],
        ]);
    }
}

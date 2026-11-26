<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeaderNavTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_has_a_single_nav_row_in_the_expected_order(): void
    {
        Category::factory()->create(['name' => 'Bakery', 'slug' => 'bakery']);

        $response = $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['All Departments', 'Sahifalar', 'Katalog', 'data-fit-pills', 'Bakery', 'Special Deals %', 'cursor-toggle'], false);

        $this->assertSame(1, substr_count($response->getContent(), 'data-fit-pills'));
    }
}

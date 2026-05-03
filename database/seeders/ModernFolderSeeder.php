<?php

namespace Database\Seeders;

use App\Models\ModernFolder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ModernFolderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ModernFolder::query()->delete();

        ModernFolder::factory()
        ->has(
            ModernFolder::factory()->state([
                'name' => 'Boots',
                'path' => null,
                'slug' => 'boots',
            ])
            ->has(
                ModernFolder::factory()->state([
                    'name' => 'New In',
                    'path' => null,
                    'slug' => 'shoes-boots-new-in',
                ]), 'children'
            )
            ->has(
                ModernFolder::factory()->state([
                    'name' => 'Sale',
                    'path' => null,
                    'slug' => 'shoes-boots-sale',
                ]), 'children'
            ),
            'children'
        )
        ->has(
            ModernFolder::factory()->state([
                'name' => 'Formal',
                'path' => null,
                'slug' => 'formal',
            ]), 'children'
        )
        ->create([
            'name' => 'Shoes',
            'path' => null,
            'slug' => 'shoes',
        ]);


        ModernFolder::factory()
        ->has(
            ModernFolder::factory()->state([
                'name' => 'Outdoor',
                'path' => null,
                'slug' => 'outdoor',
            ])
            ->has(
                ModernFolder::factory()->state([
                    'name' => 'New In',
                    'path' => null,
                    'slug' => 'jackets-outdoor-new-in',
                ]), 'children'
            )
            ->has(
                ModernFolder::factory()->state([
                    'name' => 'Sale',
                    'path' => null,
                    'slug' => 'jackets-outdoor-sale',
                ]), 'children'
            ),
            'children'
        )
        ->has(
            ModernFolder::factory()->state([
                'name' => 'Winter',
                'path' => null,
                'slug' => 'jackets-winter',
            ]), 'children'
        )
        ->create([
            'name' => 'Jackets',
            'path' => null,
            'slug' => 'jackets',
        ]);
    }
}

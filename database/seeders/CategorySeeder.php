<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::first()->categories()->createMany([
            [
                'name' => 'Alimentação',
                'type' => 'expense',
            ],
            [
                'name' => 'Transporte',
                'type' => 'expense',
            ],
            [
                'name' => 'Lazer',
                'type' => 'expense',
            ],
            [
                'name' => 'Saúde',
                'type' => 'expense',
            ],
            [
                'name' => 'Educação',
                'type' => 'expense',
            ],
            [
                'name' => 'Outros',
                'type' => 'expense',
            ],
            [
                'name' => 'Salário',
                'type' => 'income',
            ],
            [
                'name' => 'Freelance',
                'type' => 'income',
            ],
            [
                'name' => 'Investimentos',
                'type' => 'income',
            ],
            [
                'name' => 'Outros',
                'type' => 'income',
            ],
        ]);
    }
}

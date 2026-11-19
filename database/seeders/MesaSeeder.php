<?php

namespace Database\Seeders;

use App\Enums\ZonaMesa;
use App\Models\Mesa;
use Illuminate\Database\Seeder;

class MesaSeeder extends Seeder
{
    public function run(): void
    {
        // numero => capacidad (igual que la vista general)
        $mesas = [1 => 4, 2 => 2, 3 => 2, 4 => 4, 5 => 2, 6 => 3, 7 => 4, 8 => 4, 9 => 2, 10 => 2, 11 => 4, 12 => 4];

        foreach ($mesas as $numero => $capacidad) {
            Mesa::updateOrCreate(
                ['numero' => $numero],
                [
                    'capacidad' => $capacidad,
                    'zona' => $numero > 9 ? ZonaMesa::Terraza : ZonaMesa::Interior,
                    'activa' => true,
                ],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            // Estrutura inicial da Careca Locadora
            CarecaFoundationSeeder::class,

            // Frota
            FleetCategorySeeder::class,
            FleetMasterCatalogSeeder::class,
            AssetClassificationRuleSeeder::class,

            // Checklist / inspeção
            InspectionDiagramSeeder::class,

            // Financeiro
            BankSeeder::class,

            // Compras, estoque e cadastros auxiliares
            ProcurementMasterDataSeeder::class,
        ]);
    }
}
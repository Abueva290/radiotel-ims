<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        // Only the admin is seeded. All other accounts are created by the admin.
        DB::table('users')->insert([
            'name' => 'Edwin Belgado',
            'email' => 'ebelgado@radiotel.ph',
            'role' => 'admin',
            'password' => Hash::make('password'),
            'status' => 'active',
            'must_change_password' => false,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        // Fixed suppliers (0 days = cash supplier)
        $suppliers = [
            ['Motorola Solutions PH',    'Alex Tan',    60],
            ['Kenwood Philippines Inc.', 'Bong Cruz',   60],
            ['Hytera PH Distributor',    'Clara Sy',    60],
            ['Radio Parts Depot Davao',  'Danny Lim',    0],
            ['Icom Asia Pacific',        'Erwin Reyes', 60],
        ];
        foreach ($suppliers as [$name, $contact, $terms]) {
            DB::table('suppliers')->insert([
                'name' => $name, 'contact_person' => $contact, 'credit_terms_days' => $terms,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Products: name, brand, category, unit_cost, selling_price, stock, reorder
        $products = [
            ['Motorola DP4801e UHF',         'Motorola', 'two_way_radio', 28500, 34800, 4,  3],
            ['Kenwood TK-3501 UHF',          'Kenwood',  'two_way_radio',  8200, 11500, 2,  3],
            ['Hytera PD682 UHF',             'Hytera',   'two_way_radio', 22000, 28000, 6,  2],
            ['Icom IC-F3003 VHF',            'Icom',     'two_way_radio',  7800, 10500, 1,  3],
            ['Alinco DJ-500T Dual Band',     'Alinco',   'two_way_radio',  5500,  7800, 8,  2],
            ['Versa VR-5600 VHF/UHF',        'Versa',    'two_way_radio',  4200,  6200, 3,  3],
            ['Antenna Gain 7dBi VHF',        'Generic',  'antenna',        1200,  2000, 12, 5],
            ['Coax Cable RG-58 (per meter)', 'Generic',  'accessory',        35,    60, 85, 20],
            ['Repeater Controller Module',   'Generic',  'accessory',     14500, 19000, 0,  2],
            ['Replacement Battery BP-298',   'Icom',     'part',           1800,  2800, 5,  4],
        ];
        foreach ($products as [$name, $brand, $cat, $cost, $price, $qty, $reorder]) {
            DB::table('products')->insert([
                'name' => $name, 'brand' => $brand, 'category' => $cat,
                'unit_cost' => $cost, 'selling_price' => $price,
                'stock_qty' => $qty, 'reorder_level' => $reorder, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        // Customers
        $customers = [
            ['DOLE Region XI',            'Ms. Bautista'],
            ['GMA Network Davao Bureau',  'Ms. Flores'],
            ['Davao City Police Office',  'Lt. Reyes'],
            ['Tagum City LGU',            'Engr. Manalo'],
            ['Mindanao Gold Star Bus',    'Mr. Salcedo'],
            ['Samal Island Resort Group', 'Mr. Dacay'],
        ];
        foreach ($customers as [$name, $contact]) {
            DB::table('customers')->insert([
                'name' => $name, 'contact_person' => $contact, 'status' => 'active',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }
}
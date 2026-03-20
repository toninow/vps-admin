<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'warehouse', 'value' => 'CARPETANA', 'type' => 'string'],
            ['key' => 'tax_rule_id', 'value' => '1', 'type' => 'integer'],
            ['key' => 'prestashop_default_tax_rate', 'value' => '21.00', 'type' => 'decimal'],
        ];

        foreach ($settings as $item) {
            Setting::updateOrCreate(
                ['key' => $item['key']],
                ['value' => $item['value'], 'type' => $item['type']]
            );
        }
    }
}

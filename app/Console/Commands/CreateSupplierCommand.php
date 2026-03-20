<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateSupplierCommand extends Command
{
    protected $signature = 'suppliers:create
                            {name : Nombre del proveedor (ej. ADAGIO, GEWA)}
                            {--code= : Código (por defecto: nombre en mayúsculas sin espacios)}
                            {--slug= : Slug único (por defecto: nombre en minúsculas con guiones)}';

    protected $description = 'Crea un proveedor activo para usar en importaciones de prueba (ej. suppliers:create ADAGIO).';

    public function handle(): int
    {
        $name = trim($this->argument('name'));
        if ($name === '') {
            $this->error('Indica el nombre del proveedor: php artisan suppliers:create ADAGIO');
            return self::FAILURE;
        }

        $slug = $this->option('slug') ?: Str::slug($name);
        $code = $this->option('code') ?: strtoupper(Str::slug($name, ''));

        $supplier = Supplier::firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'code' => $code,
                'is_active' => true,
            ]
        );

        if ($supplier->wasRecentlyCreated) {
            $this->info('Proveedor creado: ' . $supplier->name . ' (id=' . $supplier->id . ', slug=' . $supplier->slug . ', code=' . ($supplier->code ?? '-') . ')');
        } else {
            $this->comment('El proveedor ya existía: ' . $supplier->name . ' (id=' . $supplier->id . ').');
        }

        return self::SUCCESS;
    }
}

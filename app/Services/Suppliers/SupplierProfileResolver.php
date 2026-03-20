<?php

namespace App\Services\Suppliers;

use App\Models\Supplier;
use App\Models\SupplierImport;
use App\Services\Suppliers\Profiles\BaseSupplierProfile;
use App\Services\Suppliers\Profiles\GenericSupplierProfile;
use App\Services\Suppliers\Profiles\AdagioSupplierProfile;
use App\Services\Suppliers\Profiles\AlgamSupplierProfile;
use App\Services\Suppliers\Profiles\AlhambraSupplierProfile;
use App\Services\Suppliers\Profiles\DaddarioSupplierProfile;
use App\Services\Suppliers\Profiles\EarproSupplierProfile;
use App\Services\Suppliers\Profiles\EnriqueKellerSupplierProfile;
use App\Services\Suppliers\Profiles\EuromusicaSupplierProfile;
use App\Services\Suppliers\Profiles\FenderSupplierProfile;
use App\Services\Suppliers\Profiles\GewaSupplierProfile;
use App\Services\Suppliers\Profiles\HonsuySupplierProfile;
use App\Services\Suppliers\Profiles\KnoblochSupplierProfile;
use App\Services\Suppliers\Profiles\LudwigNlSupplierProfile;
use App\Services\Suppliers\Profiles\MadridMusicalSupplierProfile;
use App\Services\Suppliers\Profiles\OrtolaSupplierProfile;
use App\Services\Suppliers\Profiles\RitmoSupplierProfile;
use App\Services\Suppliers\Profiles\SambaSupplierProfile;
use App\Services\Suppliers\Profiles\TicoSupplierProfile;
use App\Services\Suppliers\Profiles\VallestradeSupplierProfile;
use App\Services\Suppliers\Profiles\YamahaSupplierProfile;
use App\Services\Suppliers\Profiles\ZentralmediaSupplierProfile;

/**
 * Resuelve el perfil de proveedor para importaciones.
 *
 * Proveedores lógicos soportados por el resolver (orden alfabético):
 * - adagio
 * - algam
 * - alhambra
 * - daddario
 * - earpro
 * - enrique_keller
 * - euromusica
 * - fender
 * - gewa
 * - honsuy
 * - knobloch (incluye KNOBLOCH1, KNOBLOCH2, KNOBLOCH3)
 * - ludwig_nl
 * - madridmusical
 * - ortola
 * - ritmo
 * - samba
 * - tico
 * - vallestrade
 * - yamaha
 * - zentralmedia
 * - generic (fallback cuando no coincide ningún proveedor conocido)
 */
class SupplierProfileResolver
{
    /** @var array<string, BaseSupplierProfile> */
    protected array $profiles;

    public function __construct()
    {
        $this->profiles = [];

        $this->register(new GenericSupplierProfile());
        $this->register(new AdagioSupplierProfile());
        $this->register(new AlgamSupplierProfile());
        $this->register(new AlhambraSupplierProfile());
        $this->register(new DaddarioSupplierProfile());
        $this->register(new EarproSupplierProfile());
        $this->register(new EnriqueKellerSupplierProfile());
        $this->register(new EuromusicaSupplierProfile());
        $this->register(new FenderSupplierProfile());
        $this->register(new GewaSupplierProfile());
        $this->register(new HonsuySupplierProfile());
        $this->register(new KnoblochSupplierProfile());
        $this->register(new LudwigNlSupplierProfile());
        $this->register(new MadridMusicalSupplierProfile());
        $this->register(new OrtolaSupplierProfile());
        $this->register(new RitmoSupplierProfile());
        $this->register(new SambaSupplierProfile());
        $this->register(new TicoSupplierProfile());
        $this->register(new VallestradeSupplierProfile());
        $this->register(new YamahaSupplierProfile());
        $this->register(new ZentralmediaSupplierProfile());
    }

    protected function register(BaseSupplierProfile $profile): void
    {
        $this->profiles[$profile->getLogicalCode()] = $profile;
    }

    public function resolve(Supplier $supplier, SupplierImport $import): BaseSupplierProfile
    {
        $logical = $this->inferLogicalCode($supplier, $import);

        if (isset($this->profiles[$logical])) {
            return $this->profiles[$logical];
        }

        return $this->profiles['generic'];
    }

    protected function inferLogicalCode(Supplier $supplier, SupplierImport $import): string
    {
        $name = mb_strtolower($supplier->name ?? '');
        $filename = mb_strtolower(pathinfo($import->filename_original ?? '', PATHINFO_FILENAME));

        $checks = [
            'adagio' => fn() => str_contains($name, 'adagio') || str_contains($filename, 'adagio'),
            'algam' => fn() => str_contains($name, 'algam') || str_contains($filename, 'algam'),
            'alhambra' => fn() => str_contains($name, 'alhambra') || str_contains($filename, 'alhambra'),
            'daddario' => fn() => str_contains($name, 'daddario') || str_contains($name, "d'addario") || str_contains($filename, 'daddario'),
            'earpro' => fn() => str_contains($name, 'earpro') || str_contains($filename, 'earpro'),
            'enrique_keller' => fn() => str_contains($name, 'keller') || str_contains($name, 'enrique keller') || str_contains($filename, 'keller') || str_contains($filename, 'enrique'),
            'euromusica' => fn() => str_contains($name, 'euromusica') || str_contains($filename, 'euromusica'),
            'fender' => fn() => str_contains($name, 'fender') || str_contains($filename, 'fender'),
            'gewa' => fn() => str_contains($name, 'gewa') || str_contains($filename, 'gewa'),
            'honsuy' => fn() => str_contains($name, 'honsuy') || str_contains($filename, 'honsuy'),
            'knobloch' => fn() => str_contains($name, 'knobloch') || preg_match('/knobloch[123]?/i', $filename),
            'ludwig_nl' => fn() => (str_contains($name, 'ludwig') && str_contains($name, 'nl')) || (str_contains($filename, 'ludwig') && str_contains($filename, 'nl')),
            'madridmusical' => fn() => (str_contains($name, 'madrid') && str_contains($name, 'musical')) || str_contains($filename, 'madridmusical') || (str_contains($filename, 'madrid') && str_contains($filename, 'musical')),
            'ortola' => fn() => str_contains($name, 'ortola') || str_contains($filename, 'ortola'),
            'ritmo' => fn() => str_contains($name, 'ritmo') || str_contains($filename, 'ritmo'),
            'samba' => fn() => str_contains($name, 'samba') || str_contains($filename, 'samba'),
            'tico' => fn() => str_contains($name, 'tico') || str_contains($filename, 'tico'),
            'vallestrade' => fn() => str_contains($name, 'vallestrade') || str_contains($filename, 'vallestrade'),
            'yamaha' => fn() => str_contains($name, 'yamaha') || str_contains($filename, 'yamaha'),
            'zentralmedia' => fn() => str_contains($name, 'zentralmedia') || str_contains($name, 'zentral media') || str_contains($filename, 'zentralmedia') || str_contains($filename, 'zentral'),
        ];

        foreach ($checks as $code => $test) {
            if ($test()) {
                return $code;
            }
        }

        return 'generic';
    }
}

<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DuplicateProductGroupController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\NormalizationController;
use App\Http\Controllers\MobileIntegrationController;
use App\Http\Controllers\MasterProductController;
use App\Http\Controllers\MpsfpCatalogController;
use App\Http\Controllers\MpsfpImportController;
use App\Http\Controllers\MpsfpSupplierController;
use App\Http\Controllers\NormalizedProductController;
use App\Http\Controllers\ProductEanIssueController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectMpsfpController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\SupplierImportController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Raíz: siempre muestra el login
Route::get('/', [LoginController::class, 'create'])->name('root.login');

Route::middleware('guest')->group(function () {
    Route::get('login.php', [LoginController::class, 'legacy'])->name('login.legacy');
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
});

Route::post('logout', [LoginController::class, 'destroy'])
    ->name('logout')
    ->middleware('auth');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('users', UserController::class)
        ->middleware('can:users.view');

    Route::get('projects/mpsfp', [ProjectMpsfpController::class, 'dashboardBySlug'])
        ->name('projects.mpsfp.entry');
    // MPSFP dentro de /projects/{id}
    Route::get('projects/{project}/MPFSP', [ProjectMpsfpController::class, 'dashboard'])
        ->name('projects.mpfsp.dashboard');
    Route::get('projects/{project}/MPSFP', [ProjectMpsfpController::class, 'dashboard'])
        ->name('projects.mpsfp.dashboard');
    Route::get('projects/{project}/MPSFP/proveedores', [MpsfpSupplierController::class, 'index'])
        ->name('projects.mpsfp.suppliers.index');
    Route::get('projects/{project}/MPSFP/proveedores/nuevo', [MpsfpSupplierController::class, 'create'])
        ->name('projects.mpsfp.suppliers.create');
    Route::post('projects/{project}/MPSFP/proveedores', [MpsfpSupplierController::class, 'store'])
        ->name('projects.mpsfp.suppliers.store');
    Route::get('projects/{project}/MPSFP/proveedores/{supplier}', [MpsfpSupplierController::class, 'show'])
        ->name('projects.mpsfp.suppliers.show');
    Route::get('projects/{project}/MPSFP/proveedores/{supplier}/editar', [MpsfpSupplierController::class, 'edit'])
        ->name('projects.mpsfp.suppliers.edit');
    Route::put('projects/{project}/MPSFP/proveedores/{supplier}', [MpsfpSupplierController::class, 'update'])
        ->name('projects.mpsfp.suppliers.update');
    Route::delete('projects/{project}/MPSFP/proveedores/{supplier}', [MpsfpSupplierController::class, 'destroy'])
        ->name('projects.mpsfp.suppliers.destroy');
    Route::get('projects/{project}/MPSFP/importaciones', [MpsfpImportController::class, 'index'])
        ->name('projects.mpsfp.imports.index');
    Route::get('projects/{project}/MPSFP/importaciones/nueva', [MpsfpImportController::class, 'create'])
        ->name('projects.mpsfp.imports.create');
    Route::post('projects/{project}/MPSFP/importaciones', [MpsfpImportController::class, 'store'])
        ->name('projects.mpsfp.imports.store');
    Route::get('projects/{project}/MPSFP/importaciones/{import}', [MpsfpImportController::class, 'show'])
        ->name('projects.mpsfp.imports.show');
    Route::get('projects/{project}/MPSFP/importaciones/{import}/preview', [MpsfpImportController::class, 'preview'])
        ->name('projects.mpsfp.imports.preview');
    Route::get('projects/{project}/MPSFP/importaciones/{import}/mapping', [MpsfpImportController::class, 'mapping'])
        ->name('projects.mpsfp.imports.mapping');
    Route::post('projects/{project}/MPSFP/importaciones/{import}/mapping', [MpsfpImportController::class, 'saveMapping'])
        ->name('projects.mpsfp.imports.mapping.store');
    Route::post('projects/{project}/MPSFP/importaciones/{import}/process', [MpsfpImportController::class, 'process'])
        ->name('projects.mpsfp.imports.process');
    Route::post('projects/{project}/MPSFP/importaciones/{import}/cancel', [MpsfpImportController::class, 'cancel'])
        ->name('projects.mpsfp.imports.cancel');
    Route::post('projects/{project}/MPSFP/importaciones/{import}/normalize', [MpsfpImportController::class, 'normalize'])
        ->name('projects.mpsfp.imports.normalize');
    Route::get('projects/{project}/MPSFP/importaciones/{import}/status', [MpsfpImportController::class, 'status'])
        ->name('projects.mpsfp.imports.status');
    Route::delete('projects/{project}/MPSFP/importaciones/{import}', [MpsfpImportController::class, 'destroy'])
        ->name('projects.mpsfp.imports.destroy');
    Route::get('projects/{project}/MPSFP/normalizados', [MpsfpCatalogController::class, 'normalizedIndex'])
        ->name('projects.mpsfp.normalized.index');
    Route::get('projects/{project}/MPSFP/normalizados/{normalizedProduct}', [MpsfpCatalogController::class, 'normalizedShow'])
        ->name('projects.mpsfp.normalized.show');
    Route::get('projects/{project}/MPSFP/cruce-proveedores', [MpsfpCatalogController::class, 'crossSupplierIndex'])
        ->name('projects.mpsfp.cross-suppliers.index');
    Route::post('projects/{project}/MPSFP/normalizados/{normalizedProduct}/categorias/sugerencias/{suggestion}/aceptar', [MpsfpCatalogController::class, 'acceptCategorySuggestion'])
        ->name('projects.mpsfp.categories.accept');
    Route::delete('projects/{project}/MPSFP/normalizados/{normalizedProduct}/categorias/sugerencias/{suggestion}', [MpsfpCatalogController::class, 'dismissCategorySuggestion'])
        ->name('projects.mpsfp.categories.dismiss');
    Route::delete('projects/{project}/MPSFP/normalizados/{normalizedProduct}', [MpsfpCatalogController::class, 'normalizedDestroy'])
        ->name('projects.mpsfp.normalized.destroy');
    Route::get('projects/{project}/MPSFP/categorias/revision', [MpsfpCatalogController::class, 'categoryReviewIndex'])
        ->name('projects.mpsfp.categories.review');
    Route::post('projects/{project}/MPSFP/categorias/revision/bulk-apply', [MpsfpCatalogController::class, 'bulkApplyCategorySuggestions'])
        ->name('projects.mpsfp.categories.bulk-apply');
    Route::get('projects/{project}/MPSFP/maestros', [MpsfpCatalogController::class, 'masterIndex'])
        ->name('projects.mpsfp.master.index');
    Route::post('projects/{project}/MPSFP/maestros/bulk-approve', [MpsfpCatalogController::class, 'masterBulkApprove'])
        ->name('projects.mpsfp.master.bulk-approve');
    Route::post('projects/{project}/MPSFP/maestros/bulk-unapprove', [MpsfpCatalogController::class, 'masterBulkUnapprove'])
        ->name('projects.mpsfp.master.bulk-unapprove');
    Route::get('projects/{project}/MPSFP/maestros/{masterProduct}', [MpsfpCatalogController::class, 'masterShow'])
        ->name('projects.mpsfp.master.show');
    Route::post('projects/{project}/MPSFP/maestros/{masterProduct}/approve', [MpsfpCatalogController::class, 'masterApprove'])
        ->name('projects.mpsfp.master.approve');
    Route::post('projects/{project}/MPSFP/maestros/{masterProduct}/unapprove', [MpsfpCatalogController::class, 'masterUnapprove'])
        ->name('projects.mpsfp.master.unapprove');
    Route::delete('projects/{project}/MPSFP/maestros/{masterProduct}', [MpsfpCatalogController::class, 'masterDestroy'])
        ->name('projects.mpsfp.master.destroy');
    Route::get('projects/{project}/MPSFP/exportacion', [MpsfpCatalogController::class, 'exportIndex'])
        ->name('projects.mpsfp.export.index');
    Route::get('projects/{project}/MPSFP/exportacion/csv', [MpsfpCatalogController::class, 'exportDownload'])
        ->name('projects.mpsfp.export.download');
    Route::get('projects/{project}/MPSFP/{section}', [ProjectMpsfpController::class, 'section'])
        ->where('section', 'proveedores|normalizados|maestros|ean|duplicados|categorias|exportacion')
        ->name('projects.mpsfp.section');

    // Permisos gestionados vía Policy/authorize en el controlador (ProjectPolicy),
    // evitando bloquear accesos cuando el usuario solo está asignado a un proyecto.
    Route::resource('projects', ProjectController::class);

    Route::resource('mobile-integrations', MobileIntegrationController::class)
        ->parameters(['mobile-integrations' => 'mobileIntegration'])
        ->middleware('can:mobile_integrations.view');

    Route::get('activity-logs', [ActivityLogController::class, 'index'])
        ->name('activity-logs.index')
        ->middleware('can:logs.view');

    // Fase 2: proveedores e importaciones
    Route::resource('suppliers', SupplierController::class)
        ->middleware('can:suppliers.view');

    Route::get('imports', [SupplierImportController::class, 'index'])->name('imports.index')->middleware('can:imports.view');
    Route::get('imports/create', [SupplierImportController::class, 'create'])->name('imports.create')->middleware('can:imports.create');
    Route::post('imports', [SupplierImportController::class, 'store'])->name('imports.store')->middleware('can:imports.create');
    Route::get('imports/{import}', [SupplierImportController::class, 'show'])->name('imports.show')->middleware('can:imports.view');
    Route::get('imports/{import}/preview', [SupplierImportController::class, 'preview'])->name('imports.preview')->middleware('can:imports.view');
    Route::get('imports/{import}/mapping', [SupplierImportController::class, 'mapping'])->name('imports.mapping')->middleware('can:imports.process');
    Route::post('imports/{import}/mapping', [SupplierImportController::class, 'saveMapping'])->name('imports.mapping.store')->middleware('can:imports.process');
    Route::post('imports/{import}/process', [SupplierImportController::class, 'process'])->name('imports.process')->middleware('can:imports.process');
    Route::post('imports/{import}/cancel', [SupplierImportController::class, 'cancel'])->name('imports.cancel')->middleware('can:imports.process');
    Route::post('imports/{import}/normalize', [NormalizationController::class, 'runForImport'])->name('imports.normalize')->middleware('can:imports.process');
    Route::get('imports/{import}/status', [SupplierImportController::class, 'status'])->name('imports.status')->middleware('can:imports.view');
    Route::delete('imports/{import}', [SupplierImportController::class, 'destroy'])->name('imports.destroy')->middleware('can:imports.process');
    Route::get('normalization-runs/{run}', [NormalizationController::class, 'showRunStatus'])
        ->name('normalization-runs.show')
        ->middleware('can:imports.view');

    // Productos normalizados
    Route::get('products/normalized', [NormalizedProductController::class, 'index'])->name('products.normalized.index')->middleware('can:products.view');
    Route::get('products/normalized/{normalizedProduct}', [NormalizedProductController::class, 'show'])->name('products.normalized.show')->middleware('can:products.view');
    Route::delete('products/normalized/{normalizedProduct}', [NormalizedProductController::class, 'destroy'])->name('products.normalized.destroy')->middleware('can:products.edit');

    // Productos maestros
    Route::get('products/master', [MasterProductController::class, 'index'])->name('products.master.index')->middleware('can:master_products.view');
    Route::post('products/master/bulk-approve', [MasterProductController::class, 'bulkApprove'])
        ->name('products.master.bulk-approve')
        ->middleware('can:master_products.approve');
    Route::post('products/master/bulk-unapprove', [MasterProductController::class, 'bulkUnapprove'])
        ->name('products.master.bulk-unapprove')
        ->middleware('can:master_products.approve');
    Route::get('products/master/{masterProduct}', [MasterProductController::class, 'show'])->name('products.master.show')->middleware('can:master_products.view');
    Route::post('products/master/{masterProduct}/approve', [MasterProductController::class, 'approve'])
        ->name('products.master.approve')
        ->middleware('can:master_products.approve');
    Route::post('products/master/{masterProduct}/unapprove', [MasterProductController::class, 'unapprove'])
        ->name('products.master.unapprove')
        ->middleware('can:master_products.approve');
    Route::delete('products/master/{masterProduct}', [MasterProductController::class, 'destroy'])
        ->name('products.master.destroy')
        ->middleware('can:master_products.delete');

    // Categorías
    Route::resource('categories', CategoryController::class)
        ->middleware('can:categories.view');

    // Árbol maestro (ES) - evita redirecciones del proxy del portal
    Route::get('categorias', [CategoryController::class, 'treeIndex'])
        ->name('categories.tree_es')
        ->middleware('can:categories.view');

    // Incidencias EAN
    Route::get('ean-issues', [ProductEanIssueController::class, 'index'])->name('ean-issues.index')->middleware('can:ean.view');
    Route::post('ean-issues/bulk-resolve', [ProductEanIssueController::class, 'bulkResolve'])->name('ean-issues.bulk-resolve')->middleware('can:ean.resolve');
    Route::get('ean-issues/{eanIssue}', [ProductEanIssueController::class, 'show'])->name('ean-issues.show')->middleware('can:ean.view');

    // Duplicados
    Route::get('duplicates', [DuplicateProductGroupController::class, 'index'])->name('duplicates.index')->middleware('can:duplicates.view');
    Route::get('duplicates/{duplicate}', [DuplicateProductGroupController::class, 'show'])->name('duplicates.show')->middleware('can:duplicates.view');

    // Exportación PrestaShop
    Route::get('export', [ExportController::class, 'index'])->name('export.index')->middleware('can:export.view');
    Route::get('export/csv', [ExportController::class, 'download'])->name('export.download')->middleware('can:export.download');

    // Configuración
    Route::get('settings', [SettingController::class, 'index'])->name('settings.index')->middleware('can:settings.view');
});

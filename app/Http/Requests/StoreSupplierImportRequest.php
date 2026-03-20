<?php

namespace App\Http\Requests;

use App\Services\Import\FileTypeDetector;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSupplierImportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        if ($user->canAccessMpsfpSection('importaciones', 'create')) {
            return true;
        }

        $project = $this->route('project');

        return $project && $project->slug === 'mpsfp' && $user->can('view', $project);
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'catalog_year' => ['nullable', 'integer', 'between:2020,' . (now()->year + 2)],
            'file' => [
                'required',
                'file',
                'max:512000',
            ],
        ];
    }

    /**
     * Validación adicional: extensión permitida y, si hay archivo, tipo coherente.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $file = $this->file('file');
            if (! $file instanceof \Illuminate\Http\UploadedFile) {
                return;
            }

            $detector = app(FileTypeDetector::class);
            $extension = strtolower($file->getClientOriginalExtension() ?? '');

            if (! $detector->isExtensionAllowed($extension)) {
                $validator->errors->add('file', 'La extensión del archivo no está permitida. Use: ' . implode(', ', $detector->allowedExtensions()));
                return;
            }

            $path = $file->getRealPath();
            if ($path && is_readable($path)) {
                $detected = $detector->detectFromPath($path);
                if ($detected === $detector::TYPE_XLSX && ! in_array($extension, ['xlsx', 'xls'], true)) {
                    $validator->errors->add('file', 'El contenido del archivo parece ser Excel pero la extensión no es xlsx/xls.');
                }
                if ($detected === $detector::TYPE_XML && $extension !== 'xml') {
                    $validator->errors->add('file', 'El contenido del archivo parece ser XML pero la extensión no es xml.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Debe seleccionar un proveedor.',
            'supplier_id.exists' => 'El proveedor seleccionado no es válido.',
            'catalog_year.integer' => 'El año de catálogo debe ser numérico.',
            'catalog_year.between' => 'El año de catálogo no es válido.',
            'file.required' => 'Debe seleccionar un archivo.',
            'file.file' => 'El archivo no es válido.',
            'file.max' => 'El archivo no puede superar 500 MB.',
        ];
    }
}

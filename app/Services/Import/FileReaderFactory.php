<?php

namespace App\Services\Import;

class FileReaderFactory
{
    public function __construct(
        protected FileTypeDetector $detector,
        protected CsvFileReader $csvReader,
        protected XlsxFileReader $xlsxReader,
        protected XmlFileReader $xmlReader
    ) {}

    /**
     * Devuelve el reader adecuado para el tipo de archivo.
     */
    public function getReaderForType(string $fileType): FileReaderInterface
    {
        return match (strtolower($fileType)) {
            FileTypeDetector::TYPE_XLSX => $this->xlsxReader,
            FileTypeDetector::TYPE_XML => $this->xmlReader,
            default => $this->csvReader,
        };
    }
}

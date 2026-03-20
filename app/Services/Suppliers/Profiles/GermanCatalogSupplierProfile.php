<?php

namespace App\Services\Suppliers\Profiles;

/**
 * Perfil base para catálogos en alemán. No registrar en el resolver; extender por Zentralmedia, etc.
 */
abstract class GermanCatalogSupplierProfile extends GenericSupplierProfile
{
    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'strichcode',
        ]);
        $base['name'] = array_merge($base['name'], [
            'bezeichnung', 'titel', 'artikelbezeichnung', 'produktname',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'kurzbeschreibung', 'beschreibung',
        ]);
        $base['description'] = array_merge($base['description'], [
            'beschreibung', 'langbeschreibung', 'detail', 'inhalt',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marke', 'hersteller',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'artikelnummer', 'artnr', 'referenz', 'bestellnummer',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'bestand', 'menge', 'lager', 'verfugbar',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'ek', 'einkaufspreis', 'nettopreis',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'vk', 'verkaufspreis', 'uvp', 'preis', 'bruttopreis',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'kategorie', 'kategoriebezeichnung', 'warengruppe', 'bereich', 'gruppe',
        ]);
        $base['tags'] = array_merge($base['tags'], [
            'schlagworte', 'keywords',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'bild', 'bildurl', 'bilder', 'foto', 'image url', 'produktbild',
        ]);

        return $base;
    }
}

<?php

namespace App\Services\Suppliers\Profiles;

/**
 * Perfil base para catálogos en francés. No registrar en el resolver; extender por Algam, etc.
 */
abstract class FrenchCatalogSupplierProfile extends GenericSupplierProfile
{
    public function getHeaderAliases(): array
    {
        $base = parent::getHeaderAliases();

        $base['ean13'] = array_merge($base['ean13'], [
            'code barre',
        ]);
        $base['name'] = array_merge($base['name'], [
            'nom', 'nom produit', 'designation', 'libelle', 'titre',
        ]);
        $base['summary'] = array_merge($base['summary'], [
            'resume', 'description courte',
        ]);
        $base['description'] = array_merge($base['description'], [
            'description longue', 'detail', 'fiche',
        ]);
        $base['brand'] = array_merge($base['brand'], [
            'marque', 'fabricant',
        ]);
        $base['supplier_reference'] = array_merge($base['supplier_reference'], [
            'reference', 'code article', 'ref fournisseur', 'modele',
        ]);
        $base['quantity'] = array_merge($base['quantity'], [
            'quantite', 'quantité', 'qte', 'dispo', 'available',
        ]);
        $base['cost_price'] = array_merge($base['cost_price'], [
            'prix achat', 'cout', 'p.a.',
        ]);
        $base['price_tax_incl'] = array_merge($base['price_tax_incl'], [
            'prix vente', 'pv', 'prix', 'ttc',
        ]);
        $base['category_path_export'] = array_merge($base['category_path_export'], [
            'categorie', 'famille', 'section', 'rayon', 'groupe', 'type',
        ]);
        $base['tags'] = array_merge($base['tags'], [
            'mots cles', 'mots-clés',
        ]);
        $base['image_urls'] = array_merge($base['image_urls'], [
            'image', 'url image', 'photo', 'image1', 'image2',
        ]);

        return $base;
    }
}

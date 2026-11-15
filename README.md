# PARFOLYS : boutique WordPress + WooCommerce

Boutique de parfums, soins parfumés et senteurs pour la maison, sur **parfolys.fr** (Hostinger).

## Contenu du dépôt

- `wp-content/themes/parfolys/` : thème enfant Astra (le thème parent Astra reste à jour via WordPress).
  - `front-page.php` : accueil (hero produit réel, deux univers équivalents, sélections, Beauté et soins, cadeaux, conseils, services).
  - `page-templates/template-univers.php` : entrée « Parfums & Beauté » avec deux parcours (Parfums, Corps & Bain).
  - `page-templates/template-marques.php` : liste des marques (taxonomie WooCommerce `product_brand`).
  - `inc/layout.php` : en-tête (logo, recherche produits, navigation, panier) et pied de page, branchés sur les hooks Astra.
  - `inc/shop.php` : filtres de catégorie (sous-catégories, famille olfactive, concentration, format, marque, disponibilité), cartes produit, fiche produit, panier et commande (avertissement « colis séparés »), états vides.
  - `inc/product-fields.php` : onglet admin « Fiche PARFOLYS » (notes olfactives, INCI, précautions, utilisation, durée de combustion, délai de préparation confirmé). Rien ne s'affiche si le champ est vide.
  - `inc/customizer.php` : réglages Apparence > Personnaliser > PARFOLYS (produit du hero, photos des blocs, informations de service, message colis séparés, e-mail de contact).
  - `inc/contact.php` : formulaire de contact natif (shortcode `[parfolys_contact]`).
  - `assets/fonts/` : Libre Baskerville et Montserrat auto-hébergées (pas d'appel Google Fonts).
- `brand/logos/` : logos originaux fournis (PDF vectoriels) et leurs exports WebP sans perte (300 dpi, pixels identiques au rendu PDF).
- `brand/kit/` : kit officiel issu du Brand Book Notion (SVG vectoriels, PNG, favicons). `favicon_512` est l'icône WordPress.

## Sources appliquées

Export Notion PARFOLYS V2 (pages 01 à 06 et 07) lu intégralement : tokens du Brand Book (boutons 6 px, cartes 8 px, #666666, #767676, #E5E5E5, conteneur 1200 px), textes de la Carta Editorial (univers, À propos, microtextes), séquence d'accueil, filtres, fiche et panier du Caderno WooCommerce.

## Configuration WooCommerce en place (préproduction)

- Site en mode maintenance Hostinger, indexation désactivée.
- France, EUR, prix TTC, TVA 20 %, commande invitée autorisée, compte client facultatif.
- Catégories : Parfums, Corps & Bain, Maison parfumée (Bougies parfumées, Diffuseurs, Parfums d'ambiance), Cadeaux.
- Sous-catégories : Soins du corps, Bain et douche ; Bougies parfumées, Diffuseurs à bâtonnets, Parfums d'ambiance ; Cadeaux : Parfum, Beauté, Maison, Coffrets (affichées seulement si elles contiennent des produits).
- Attributs : Pour (Femme, Homme, Mixte), Contenance, Famille olfactive (Floral, Frais, Boisé, Gourmand), Concentration.
- Classes d'expédition : `colis-beaute` et `colis-maison` (détection automatique des colis séparés).
- **Éléments de test à retirer avant la mise en ligne** : 4 produits privés « [TEST] », moyen de paiement « Commande test » (chèque renommé), tarif de livraison à 0 € « à confirmer », commandes de test.

## Déploiement du thème

Zipper `wp-content/themes/parfolys`, l'envoyer sur le serveur puis le décompresser dans `wp-content/themes/` (fait ici via Novamira). Les versions CSS/JS sont horodatées automatiquement.

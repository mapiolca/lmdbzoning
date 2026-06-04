# lmdbzoning

Module externe Dolibarr pour calculer et stocker une zone géographique à partir d'une adresse, d'un point de référence et d'un profil de zones.

## Compatibilité

- Dolibarr v20+
- PHP 8.0+
- MySQL/MariaDB
- Multicompany
- Installation prévue dans `htdocs/custom/lmdbzoning`
- Identifiant de module : `450021`

## Fonctionnalités

- Points de référence avec coordonnées et statut de géocodage.
- Profils de zonage avec zones ordonnées et bornes de distance.
- Calcul Haversine à vol d'oiseau.
- Cache de géocodage par hash d'adresse normalisée.
- Fournisseur par défaut : Géoplateforme/BAN `https://data.geopf.fr/geocodage/search`.
- Résultats génériques par objet Dolibarr via `element_type` / `fk_element`.
- Forçage manuel avec motif obligatoire.
- Application optionnelle de catégories natives Dolibarr lorsque le type d'objet est supporté.
- API PHP `LmdbZoningService` et API REST `LmdbZoningApi`.
- Cron de recalcul des résultats en attente.
- Page de compatibilité centralisée.

## Installation

1. Copier ou cloner ce dépôt dans `htdocs/custom/lmdbzoning`.
2. Aller dans **Accueil > Configuration > Modules/Applications**.
3. Activer `lmdbzoning`.
4. Ouvrir la page de configuration du module.
5. Lancer l'assistant initial si le profil `MAINT_PV_RES_1_9KWC` doit être créé.

Le géocodage automatique est désactivé par défaut. Les coordonnées peuvent être saisies manuellement ou géocodées explicitement depuis la fiche d'un point de référence.

## API PHP

```php
dol_include_once('/lmdbzoning/class/lmdbzoningservice.class.php');

$service = new LmdbZoningService($db);
$result = $service->calculateZoneForObject(
	'powerplantpv',
	$object->id,
	'MAINT_PV_RES_1_9KWC',
	$conf->entity
);

if ($result['status'] === 'ok') {
	$service->applyZoneCategoryToObject('powerplantpv', $object->id, $result);
}
```

## Données

Tables principales :

- `llx_lmdbzoning_referencepoint`
- `llx_lmdbzoning_profile`
- `llx_lmdbzoning_profile_zone`
- `llx_lmdbzoning_geocode_cache`
- `llx_lmdbzoning_object_zone`
- `llx_lmdbzoning_calculation_log`

Chaque table métier porte `entity`.

## Intégrations

- `powerplantpv` : lecture optionnelle de l'adresse d'installation, affichage d'un bloc de résultat si un zonage existe, et recalcul via les triggers `POWERPLANTPV_POWERPLANT_CREATE` / `POWERPLANTPV_POWERPLANT_MODIFY`.
- Catégories centrales : si `LMDBZONING_AUTO_APPLY_CATEGORY` est activé, les catégories de zonage sont synchronisées dans la liaison native `categorie_powerplant` sans modifier les autres catégories manuelles.
- `pricelist` : doit lire `lmdbzoning_object_zone` ou `LmdbZoningService::getObjectZone()`, sans recalculer la distance.
- `timesheetweek` : supporté comme `element_type`; aucune modification automatique des temps en V1.

## Hors périmètre V1

- Distance routière.
- Cartographie interactive.
- Optimisation de tournées.
- Facturation automatique des kilomètres.
- Primes automatiques dans `timesheetweek`.
- Modification du core Dolibarr.

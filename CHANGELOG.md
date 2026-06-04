# Changelog

## 1.0.0 - 2026-06-04

- Création du module externe `lmdbzoning`.
- Ajout du descripteur Dolibarr `modLmdbZoning` avec numéro `450021`.
- Ajout des objets métier, tables SQL, API PHP, API REST, hooks, triggers, cron, pages admin et traductions.
- Ajout du fournisseur de géocodage Géoplateforme/BAN désactivé par défaut.
- Ajout de l'assistant initial pour le profil `MAINT_PV_RES_1_9KWC`.
- Affichage de la distance calculée seule sur les fiches zonées et agrégation des distances des centrales liées pour catégoriser les documents.
- Synchronisation des catégories de zonage des centrales `powerplantpv` via les triggers `POWERPLANTPV_POWERPLANT_CREATE` et `POWERPLANTPV_POWERPLANT_MODIFY`, avec liaison native `categorie_powerplant`.

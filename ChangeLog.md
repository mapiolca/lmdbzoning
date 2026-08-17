# Changelog

## Unreleased

- Ajout d'une catégorisation automatique configurable indépendamment pour chaque type d'objet actif, avec migration conservatrice de l'ancien réglage global.
- Ajout de l'onglet interne **À propos**, des ressources de maintenance et des informations de licence.
- Mise en conformité des sept identifiants de permissions avec la plage du module `450022`, avec migration idempotente des attributions utilisateurs et groupes.
- Ajout des traductions de la nouvelle interface dans les cinq langues livrées.
- Alignement de la documentation sur l'identifiant de module `450022` et ajout de la licence GPLv3+.

## 1.0.0 - 2026-06-04

- Création du module externe `lmdbzoning`.
- Ajout du descripteur Dolibarr `modLmdbZoning` avec numéro `450022`.
- Ajout des objets métier, tables SQL, API PHP, API REST, hooks, triggers, cron, pages admin et traductions.
- Ajout du fournisseur de géocodage Géoplateforme/BAN désactivé par défaut.
- Ajout de l'assistant initial pour le profil `MAINT_PV_RES_1_9KWC`.
- Affichage de la distance calculée seule sur les fiches zonées et agrégation des distances des centrales liées pour catégoriser les documents.
- Synchronisation des catégories de zonage des centrales `powerplantpv` via les triggers `POWERPLANTPV_POWERPLANT_CREATE` et `POWERPLANTPV_POWERPLANT_MODIFY`, avec liaison native `categorie_powerplant`.

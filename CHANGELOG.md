# Changelog

## Unreleased

- Ajout d'un multiselect de catégories contrat sur la fiche contrat via hook, avec conservation des catégories issues du zonage.
- Correction de l'affichage des catégories dans la fiche contrat, juste après la date, et suppression d'un warning PHP 8.2 sur les hooks.

## 1.0.0 - 2026-06-03

- Création du module externe `lmdbzoning`.
- Ajout du descripteur Dolibarr `modLmdbZoning` avec numéro `450021`.
- Ajout des objets métier, tables SQL, API PHP, API REST, hooks, triggers, cron, pages admin et traductions.
- Ajout du fournisseur de géocodage Géoplateforme/BAN désactivé par défaut.
- Ajout de l'assistant initial pour le profil `MAINT_PV_RES_1_9KWC`.

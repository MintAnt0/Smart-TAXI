# Smart Taxi

Application web de réservation de taxis, développée en PHP et MySQL pour un service fictif de transport basé à Arras (marque **Elite Taxi**). Le client crée un compte, se connecte, saisit un trajet, visualise l'itinéraire sur une carte et obtient immédiatement la distance, la durée et le prix de sa course avant de confirmer sa réservation.

Projet réalisé dans le cadre de la formation BTS CIEL (année 2024-2025). Les guides de l'ANSSI fournis en annexe servent de référence en matière de sécurité.

## Sommaire

- [Fonctionnalités](#fonctionnalités)
- [Technologies](#technologies)
- [Architecture du projet](#architecture-du-projet)
- [Base de données](#base-de-données)
- [Calcul du prix](#calcul-du-prix)
- [Sécurité](#sécurité)
- [Installation](#installation)
- [Configuration](#configuration)
- [Parcours utilisateur](#parcours-utilisateur)
- [Limites connues et pistes d'amélioration](#limites-connues-et-pistes-damélioration)
- [Documentation de référence](#documentation-de-référence)
- [Auteur](#auteur)

## Fonctionnalités

**Compte client**
- Inscription avec validation des champs : tous les champs obligatoires, adresse e-mail valide, numéro de téléphone valide, mot de passe d'au moins 12 caractères, confirmation du mot de passe, unicité de l'adresse e-mail.
- Mot de passe stocké sous forme de hash (`password_hash`), jamais en clair.
- Connexion avec vérification du hash (`password_verify`) et ouverture de session PHP (identifiant, nom, prénom et e-mail).
- Message d'erreur volontairement identique pour un e-mail inconnu et un mauvais mot de passe.

**Réservation**
- Formulaire : identité et coordonnées du client, adresses de départ et d'arrivée, date et heure, option 5e passager, nombre de bagages.
- Géocodage des adresses et calcul d'itinéraire avec l'API TomTom (recherche, routage).
- Affichage sur une carte interactive (TomTom Maps SDK) des marqueurs de départ et d'arrivée et du tracé.
- Estimation immédiate de la distance, de la durée et du prix, avec le détail des options.
- Enregistrement de la réservation en base dans une transaction : en cas d'erreur, tout est annulé (rollback).

**Pages d'information et contact**
- Accueil et présentation des services, page À propos, grille de tarifs officiels 2024 (taxis non parisiens).
- Conditions d'utilisation et politique de confidentialité (RGPD).
- Formulaire de contact avec envoi d'e-mail en SMTP via PHPMailer.

## Technologies

| Couche | Technologie |
| --- | --- |
| Back-end | PHP 7 ou supérieur, PDO |
| Base de données | MySQL ou MariaDB |
| Front-end | HTML5, CSS3, JavaScript natif |
| Cartographie et itinéraires | TomTom Maps SDK for Web 6.19.0, API Search (géocodage) et Routing |
| E-mail | PHPMailer 6.10.0 (SMTP) |
| Prototype annexe | Node.js (`http`, `mysql`) |

## Architecture du projet

```
.
├── Home page.html                  # Page d'accueil statique
├── Home.php                        # Accueil et réservation (carte, calcul, enregistrement)
├── Inscription.php                 # Création de compte client
├── Login.php                       # Connexion et ouverture de session
├── Se connecter_Inscription.html   # Maquette statique de l'inscription
├── Login.html                      # Maquette statique de la connexion
├── Deconnexion.html                # Page de bienvenue après connexion
├── A propos.html                   # Présentation de l'entreprise
├── Aproposd-1.php                  # Version PHP de la page À propos (ancien gabarit)
├── Tarifs.html                     # Tarifs officiels 2024
├── Contac.html                     # Formulaire de contact
├── contact^test.php                # Envoi du message avec PHPMailer
├── Conditions d_utilisattion.html  # Conditions d'utilisation
├── Confidantialite.html            # Politique de confidentialité
├── Style.css                       # Feuille de style commune
├── Script.js                       # Logique côté client : carte, calcul, prix
├── server.js                       # Prototype Node.js de réservation (non utilisé)
├── PHPMailer-master/               # Bibliothèque d'envoi d'e-mails
└── Smart taxi/
    └── Annexes_projets_CIEL_2024-2025/   # Guides ANSSI de référence (PDF)
```

### Fonctionnement de la réservation

1. Le client remplit le formulaire de `Home.php` et clique sur le calcul d'itinéraire.
2. Une requête AJAX (`?action=calculate`) appelle le serveur, qui géocode les deux adresses, interroge l'API de routage TomTom et renvoie la distance, la durée et le prix au format JSON.
3. `Script.js` affiche le tracé sur la carte et les résultats, puis propose la confirmation.
4. À la confirmation, le formulaire est envoyé en POST. Le serveur recalcule tout de son côté (le prix n'est jamais repris du navigateur), puis enregistre la réservation dans une transaction.

## Base de données

Base attendue : `smart-taxi-project`. Le dépôt ne contient pas de script SQL ; les tables et colonnes ci-dessous sont celles utilisées par le code.

| Table | Colonnes utilisées | Rôle |
| --- | --- | --- |
| `User` | `ID_USER`, `ID_TYPE_USER`, `NOM_USER`, `PRENOM_USER`, `EMAIL_USER`, `MOT_DE_PASSE_USER`, `TELEPHONE_USER`, `DATE_CREATION_USER` | Comptes clients (type `CLI`) |
| `Supplement` | `ID_SUPPLEMENT`, `SUPPLEMENT_BAGAGE`, `MAJORATION_NUIT`, `MAJORATION_RETOUR_VIDE`, `MAJORATION_FERIE_OU_DIMANCHE` | Suppléments appliqués à une course |
| `Tarification` | `ID_TARIFICATION`, `ID_TYPE_TARIFICATION`, `ID_SUPPLEMENT`, `PRIX_DE_BASE`, `PRIX_PAR_KM`, `PRIX_PAR_H` | Tarif appliqué (type 1 : standard) |
| `Reservation` | `ID_TARIFICATION`, `ADRESSE_DEPART`, `ADRESSE_ARRIVEE`, `DISTANCE_COURSE`, `TEMPS_COURSE`, `DATE`, `PRIX_TOTAL_COURSE`, `NBR_CLIENTS`, `NBR_BAGAGES` | Courses réservées |

Lors d'une réservation, une ligne est créée dans `Supplement`, puis dans `Tarification`, puis dans `Reservation`, dans une même transaction.

## Calcul du prix

Le prix est calculé côté serveur à partir de la distance fournie par l'API de routage :

```
prix = prise en charge + (distance en km x tarif au km) + options
```

| Élément | Valeur |
| --- | --- |
| Prise en charge | 4,40 € |
| Tarif au km, course jusqu'à 5 km | 2,50 € |
| Tarif au km, course de 5 à 20 km | 2,00 € |
| Tarif au km, course de 20 à 50 km | 1,75 € |
| Tarif au km, course de plus de 50 km | 1,50 € |
| 5e passager | + 4,00 € |
| Bagage | + 2,00 € par bagage |

Le tarif au km dépend de la tranche dans laquelle tombe la distance totale, puis s'applique à l'ensemble du trajet. Il s'agit d'un modèle simplifié, distinct de la grille officielle affichée sur la page Tarifs.

## Sécurité

Mesures mises en œuvre dans le code :

- **Injections SQL** : toutes les requêtes passent par des requêtes préparées PDO avec paramètres liés, avec émulation désactivée sur les pages d'inscription et de réservation.
- **Mots de passe** : hachage avec `password_hash`, vérification avec `password_verify`, longueur minimale de 12 caractères.
- **XSS** : échappement des sorties avec `htmlspecialchars` et assainissement des champs saisis avant traitement.
- **Validation des entrées** : contrôle côté serveur de l'e-mail (`FILTER_VALIDATE_EMAIL`), du téléphone (expression régulière), des champs obligatoires et des valeurs numériques.
- **Intégrité des données** : prix recalculé côté serveur et écritures en transaction avec rollback.
- **Énumération de comptes** : message de connexion identique pour un e-mail inconnu et un mot de passe erroné.

Les guides ANSSI présents dans `Smart taxi/Annexes_projets_CIEL_2024-2025/` (mesures préventives prioritaires, sécurité des sites web côté navigateur, IoT, 802.1x) servent de référence.

## Installation

### Prérequis

- PHP 7.0 ou supérieur, avec l'extension `pdo_mysql`
- MySQL ou MariaDB
- Un serveur web local (XAMPP, WAMP, MAMP) ou le serveur intégré de PHP
- Une clé d'API TomTom (offre gratuite disponible sur developer.tomtom.com)
- Pour le formulaire de contact : un compte e-mail SMTP (par exemple Gmail avec un mot de passe d'application)

### Étapes

1. Cloner le dépôt dans le dossier du serveur web :

   ```bash
   git clone https://github.com/MintAnt0/<nom-du-depot>.git
   ```

2. Créer la base de données et un utilisateur dédié :

   ```sql
   CREATE DATABASE `smart-taxi-project` CHARACTER SET utf8mb4;
   CREATE USER 'smart_taxi'@'localhost' IDENTIFIED BY '<mot-de-passe-solide>';
   GRANT SELECT, INSERT ON `smart-taxi-project`.* TO 'smart_taxi'@'localhost';
   ```

3. Créer les tables `User`, `Supplement`, `Tarification` et `Reservation` avec les colonnes décrites dans la section [Base de données](#base-de-données).
4. Renseigner la configuration (voir ci-dessous).
5. Lancer le projet, par exemple avec le serveur intégré de PHP :

   ```bash
   php -S localhost:8000
   ```

   puis ouvrir `http://localhost:8000/Home.php`.

## Configuration

Les paramètres sensibles ne doivent jamais être versionnés. Les valeurs à renseigner sont :

| Paramètre | Fichier concerné | Description |
| --- | --- | --- |
| Hôte, nom de la base, utilisateur et mot de passe MySQL | `Home.php`, `Inscription.php`, `Login.php` | Connexion PDO |
| Clé d'API TomTom | `Home.php` | Géocodage, routage et carte |
| Identifiants SMTP et adresse d'expédition | `contact^test.php` | Envoi du formulaire de contact |

Il est recommandé de regrouper ces valeurs dans un fichier `config.php` ou dans des variables d'environnement, exclus du dépôt via `.gitignore`, puis de les charger depuis chaque page.

```php
// config.php (à ne pas versionner)
return [
    'db_host' => '127.0.0.1',
    'db_name' => 'smart-taxi-project',
    'db_user' => 'smart_taxi',
    'db_pass' => getenv('DB_PASS'),
    'tomtom_key' => getenv('TOMTOM_API_KEY'),
];
```

## Parcours utilisateur

1. Découverte des services et des tarifs depuis la page d'accueil.
2. Création d'un compte sur `Inscription.php`.
3. Connexion sur `Login.php`.
4. Saisie du trajet sur `Home.php`, calcul de l'itinéraire et du prix, puis confirmation.
5. En cas de question, envoi d'un message depuis la page Contact.

## Limites connues et pistes d'amélioration

- Les réservations ne sont pas rattachées au compte connecté, et la page de réservation n'exige pas d'être connecté.
- Les informations du client saisies dans le formulaire (nom, téléphone, e-mail) ne sont pas enregistrées dans la table `Reservation`.
- Les suppléments de nuit, de retour à vide et de jours fériés existent en base mais ne sont pas encore calculés.
- Aucune protection CSRF sur les formulaires ; la session n'est pas régénérée après connexion.
- L'affichage détaillé des erreurs PHP est activé pour le débogage et doit être désactivé en production.
- Le calcul du prix est un modèle simplifié qui ne reprend pas la grille officielle de la page Tarifs.
- `server.js` est un prototype Node.js abandonné : il dépend d'un module `db` absent et d'une base différente.
- Pas de tableau de bord chauffeur ni d'historique des courses pour le client.
- Fichiers à harmoniser : pages statiques et PHP en double, noms de fichiers avec espaces et caractères spéciaux.

## Documentation de référence

- ANSSI, mesures cyber préventives prioritaires (2023)
- ANSSI, recommandations pour la mise en œuvre d'un site web : maîtriser les standards de sécurité côté navigateur
- ANSSI, guide 802.1x
- ANSSI, sécurité des systèmes d'objets connectés (IoT)
- Documentation PDO, PHPMailer et TomTom Developer

## Auteur

Antonin FRIMAT (MintAnt0)

- GitHub : [github.com/MintAnt0](https://github.com/MintAnt0)
- LinkedIn : [linkedin.com/in/antonin-frimat-a5371b2a5](https://www.linkedin.com/in/antonin-frimat-a5371b2a5)

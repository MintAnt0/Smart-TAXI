<?php
/**
 * Modèle de configuration du projet Smart Taxi.
 *
 * Copiez ce fichier en `config.php` puis renseignez vos propres valeurs :
 *
 *     cp config.example.php config.php
 *
 * `config.php` est listé dans `.gitignore` : vos identifiants ne seront
 * donc jamais poussés sur GitHub.
 */

return [
    'db' => [
        'host'    => '127.0.0.1',
        'port'    => 3306,
        'name'    => 'smart-taxi-project',
        'user'    => 'VOTRE_UTILISATEUR_MYSQL',
        'pass'    => 'VOTRE_MOT_DE_PASSE_MYSQL',
        'charset' => 'utf8mb4',
    ],

    'tomtom' => [
        // Clé créée sur https://developer.tomtom.com
        // Limitez-la à vos domaines dans la console TomTom.
        'api_key' => 'VOTRE_CLE_API_TOMTOM',
    ],

    'smtp' => [
        // Pour Gmail, utilisez un mot de passe d'application
        // (et non votre mot de passe personnel).
        'host'      => 'smtp.gmail.com',
        'port'      => 587,
        'username'  => 'VOTRE_ADRESSE_GMAIL',
        'password'  => 'VOTRE_MOT_DE_PASSE_APPLICATION',
        'from'      => 'VOTRE_ADRESSE_GMAIL',
        'from_name' => 'Elite Taxi',
    ],
];

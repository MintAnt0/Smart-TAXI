<?php include 'header.php'; ?>
<div class="container">
  <h1>À Propos de <span class="highlight">Elite Taxi</span></h1>
  <div class="underline"></div>
  <p>
    Basée au cœur d’<span class="highlight">Arras</span>, <strong>Elite Taxi</strong> est bien plus qu’un simple service de transport : c’est une expérience. Nous sommes nés d’une volonté claire : <em>réinventer le taxi traditionnel</em> en offrant une prestation de qualité, sécurisée, élégante et toujours ponctuelle.
  </p>
  <p>
    Nos chauffeurs sont rigoureusement sélectionnés pour leur professionnalisme, leur connaissance d’Arras et des environs, mais surtout pour leur sens du service. Chaque course est traitée avec le plus grand soin, qu’il s’agisse d’un simple trajet en centre-ville, d’un transfert vers les gares et aéroports, ou encore d’un service VIP sur mesure.
  </p>
  <p>
    Notre flotte de véhicules est régulièrement entretenue, équipée pour garantir confort et discrétion, dans une ambiance haut de gamme fidèle à l’image de notre marque.
  </p>

  <div class="team-section">
    <h2>Notre Vision</h2>
    <p>
      Être le <span class="highlight">référent du transport de prestige</span> à Arras et dans la région Hauts-de-France. Nous croyons en un service local, humain et accessible sans compromis sur la qualité.
    </p>
  </div>

  <div class="team-section">
    <h2>Pourquoi nous choisir ?</h2>
    <div class="team-member">✔️ Service disponible 24h/24 et 7j/7</div>
    <div class="team-member">✔️ Réservation facile en ligne ou par téléphone</div>
    <div class="team-member">✔️ Conducteurs expérimentés et discrets</div>
    <div class="team-member">✔️ Véhicules propres, récents, et confortables</div>
    <div class="team-member">✔️ Paiement sécurisé (CB, espèces, facture pro)</div>
  </div>

  <a href="index.php" class="back-link">← Retour à l'accueil</a>
</div>
<?php include 'footer.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Elite Taxi - Arras</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Style.css">
  <style>
    body {
      background-color: #121212;
      color: #FFD700;
      font-family: 'Montserrat', sans-serif;
      margin: 0;
      padding: 0;
    }
    .container {
      max-width: 900px;
      margin: 50px auto;
      padding: 30px;
      background-color: #1e1e1e;
      border: 1px solid #FFD700;
      border-radius: 10px;
    }
    h1 {
      font-family: 'Playfair Display', serif;
      font-size: 36px;
      text-align: center;
      margin-bottom: 10px;
    }
    .underline {
      width: 80px;
      height: 4px;
      background: #FFD700;
      margin: 10px auto 30px;
      border-radius: 2px;
    }
    p {
      font-size: 16px;
      line-height: 1.8;
      color: #ddd;
    }
    .highlight {
      color: #FFD700;
      font-weight: bold;
    }
    .team-section {
      margin-top: 40px;
    }
    .team-section h2 {
      text-align: center;
      font-size: 28px;
      margin-bottom: 20px;
    }
    .team-member {
      background: #2c2c2c;
      padding: 15px;
      margin: 10px 0;
      border-left: 4px solid #FFD700;
      border-radius: 6px;
    }
    a.back-link {
      display: block;
      text-align: center;
      color: #FFD700;
      margin-top: 30px;
      text-decoration: none;
    }
    a.back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>

<?php
// elite_taxi.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Configuration (fichier local, non versionné — voir config.example.php)
$config = require __DIR__ . '/config.php';

$host = $config['db']['host'];
$port = $config['db']['port'];
$db = $config['db']['name'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=" . $config['db']['charset'];
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit('Erreur de connexion BDD : ' . $e->getMessage());
}

// 2. Clé TomTom
define('TOMTOM_API_KEY', $config['tomtom']['api_key']);

// 3. Fonctions utilitaires

/**
 * Renvoie [lat, lon] pour une adresse donnée via l'API TomTom.
 */
function geocode(string $address): array
{
    $url = 'https://api.tomtom.com/search/2/geocode/' . urlencode($address) . '.json?key=' . TOMTOM_API_KEY;
    $resp = @file_get_contents($url);
    if ($resp === false) {
        throw new Exception('Erreur de connexion à l\'API TomTom.');
    }
    $data = json_decode($resp, true);
    if (!empty($data['results'][0]['position'])) {
        return [
            $data['results'][0]['position']['lat'],
            $data['results'][0]['position']['lon']
        ];
    }

    throw new Exception('Adresse introuvable : ' . $address);
}

/**
 * Calcule le résumé d'itinéraire via l'API TomTom (distance et durée).
 */
function calculateRouteSummary(array $start, array $end): array
{
    $startStr = implode(',', $start);
    $endStr = implode(',', $end);
    $url = "https://api.tomtom.com/routing/1/calculateRoute/{$startStr}:{$endStr}/json?key=" . TOMTOM_API_KEY;
    $resp = @file_get_contents($url);
    if ($resp === false) {
        throw new Exception('Erreur de connexion à l\'API TomTom.');
    }
    $data = json_decode($resp, true);
    if (!empty($data['routes'][0]['summary'])) {
        return $data['routes'][0]['summary'];
    }

    throw new Exception('Impossible de calculer l\'itinéraire');
}

/**
 * Calcule le prix de base selon la distance (km).
 */
function calcBasePrice(float $distanceKm): float
{
    $base = 4.40;
    if ($distanceKm <= 5) {
        $rate = 2.50;
    }
    elseif ($distanceKm <= 20) {
        $rate = 2.00;
    }
    elseif ($distanceKm <= 50) {
        $rate = 1.75;
    }
    else {
        $rate = 1.50;
    }

    return $base + ($distanceKm * $rate);
}

// 4. Gestion des requêtes AJAX pour le calcul
if (isset($_GET['action']) && $_GET['action'] === 'calculate') {
    header('Content-Type: application/json');

    try {
        $departure = $_GET['departure'] ?? '';
        $arrival = $_GET['arrival'] ?? '';
        $luggage = (int)($_GET['luggage'] ?? 0);
        $fifthPassenger = isset($_GET['fifthPassenger']) ? 1 : 0;

        if (empty($departure) || empty($arrival)) {
            throw new Exception('Adresses de départ et d\'arrivée requises');
        }

        // Géocodage et calcul d'itinéraire
        list($lat1, $lon1) = geocode($departure);
        list($lat2, $lon2) = geocode($arrival);
        $summary = calculateRouteSummary([$lat1, $lon1], [$lat2, $lon2]);
        $distanceKm = round($summary['lengthInMeters'] / 1000, 1);
        $dureeMin = ceil($summary['travelTimeInSeconds'] / 60);

        // Calcul du prix
        $basePrice = calcBasePrice($distanceKm);
        $optionsPrice = ($fifthPassenger * 4) + ($luggage * 2);
        $totalPrice = round($basePrice + $optionsPrice, 2);

        echo json_encode([
            'success' => true,
            'distance' => $distanceKm,
            'duration' => $dureeMin,
            'price' => $totalPrice,
            'departure_coords' => [$lat1, $lon1],
            'arrival_coords' => [$lat2, $lon2]
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// 5. Traitement du POST pour la réservation finale
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Récupération et assainissement manuel
        $first_name = htmlspecialchars(trim($_POST['firstName'] ?? ''), ENT_QUOTES, 'UTF-8');
        $last_name = htmlspecialchars(trim($_POST['lastName'] ?? ''), ENT_QUOTES, 'UTF-8');
        $phone = htmlspecialchars(trim($_POST['phone'] ?? ''), ENT_QUOTES, 'UTF-8');
        $email_raw = trim($_POST['email'] ?? '');
        $email = filter_var($email_raw, FILTER_VALIDATE_EMAIL);
        $departure = htmlspecialchars(trim($_POST['departure'] ?? ''), ENT_QUOTES, 'UTF-8');
        $arrival = htmlspecialchars(trim($_POST['arrival'] ?? ''), ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars(trim($_POST['date'] ?? ''), ENT_QUOTES, 'UTF-8');
        $time = htmlspecialchars(trim($_POST['time'] ?? ''), ENT_QUOTES, 'UTF-8');
        $nbrBagages = (int)filter_var($_POST['luggage'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
        $fifthPassenger = isset($_POST['fifthPassenger']) ? 1 : 0;

        // Vérification des champs obligatoires
        if (
            $first_name === '' ||
            $last_name === '' ||
            $phone === '' ||
            $email === false ||
            $departure === '' ||
            $arrival === '' ||
            $date === '' ||
            $time === ''
        ) {
            throw new Exception('Tous les champs obligatoires doivent être remplis et valides.');
        }

        // Géocodage et calcul d'itinéraire
        list($lat1, $lon1) = geocode($departure);
        list($lat2, $lon2) = geocode($arrival);
        $summary = calculateRouteSummary([$lat1, $lon1], [$lat2, $lon2]);
        $distanceKm = round($summary['lengthInMeters'] / 1000, 1);
        $dureeMin = ceil($summary['travelTimeInSeconds'] / 60);

        // Calcul du prix
        $basePrice = calcBasePrice($distanceKm);
        $optionsPrice = ($fifthPassenger * 4) + ($nbrBagages * 2);
        $totalPrice = round($basePrice + $optionsPrice, 2);

        // Début de la transaction
        $pdo->beginTransaction();

        // 5.1. INSERT dans Supplement
        $stmt = $pdo->prepare(
            'INSERT INTO Supplement (
                SUPPLEMENT_BAGAGE,
                MAJORATION_NUIT,
                MAJORATION_RETOUR_VIDE,
                MAJORATION_FERIE_OU_DIMANCHE
             ) VALUES (
                :bag, 0, 0, 0
             )'
        );
        $stmt->execute([':bag' => $nbrBagages]);
        $suppId = $pdo->lastInsertId();

        // 5.2. INSERT dans Tarification
        $kmRate = $distanceKm > 0
            ? round(($totalPrice - $basePrice - $optionsPrice) / $distanceKm, 2)
            : 0;
        $stmt = $pdo->prepare(
            'INSERT INTO Tarification (
                ID_TYPE_TARIFICATION,
                ID_SUPPLEMENT,
                PRIX_DE_BASE,
                PRIX_PAR_KM,
                PRIX_PAR_H
             ) VALUES (
                :type, :supp, :base, :km, 0
             )'
        );
        $stmt->execute([
            ':type' => 1,      // Code "Standard"
            ':supp' => $suppId,
            ':base' => $basePrice,
            ':km' => $kmRate,
        ]);
        $tarifId = $pdo->lastInsertId();

        // 5.3. INSERT dans Reservation
        $stmt = $pdo->prepare(
            'INSERT INTO Reservation (
                ID_TARIFICATION,
                ADRESSE_DEPART,
                ADRESSE_ARRIVEE,
                DISTANCE_COURSE,
                TEMPS_COURSE,
                DATE,
                PRIX_TOTAL_COURSE,
                NBR_CLIENTS,
                NBR_BAGAGES
             ) VALUES (
                :t, :dpt, :arr, :dist, :dur, :dt, :prix, :clients, :bags
             )'
        );
        $stmt->execute([
            ':t' => $tarifId,
            ':dpt' => $departure,
            ':arr' => $arrival,
            ':dist' => $distanceKm,
            ':dur' => $dureeMin,
            ':dt' => "$date $time",
            ':prix' => $totalPrice,
            ':clients' => $fifthPassenger ? 5 : 4,
            ':bags' => $nbrBagages,
        ]);

        // Validation de la transaction
        $pdo->commit();

        $success_message = "Réservation confirmée ! Distance : {$distanceKm} km, Durée : {$dureeMin} min, Prix : {$totalPrice} €";
    } catch (Exception $e) {
        // Rollback en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Réservation Elite Taxi</title>
  <link rel="stylesheet" href="Style.css">
</head>
<body>

  <div class="gold-border top"></div>
  <div class="gold-border bottom"></div>
  <div class="gold-border left"></div>
  <div class="gold-border right"></div>

    <nav class="navbar">
        <div class="logo">A4 TAXI</div>
        <div class="nav-links">
            <a href="Home.php">Accueil</a>
            <a href="A propos.html">Services</a>
            <a href="Contac.html">Contact</a>
            <a href="Tarifs.html">Tarifs</a>
            <a href="Inscription.php">Connexion</a>
        </div>
    </nav>


  <section class="hero">
    <div class="hero-content">
      <h1>L'Excellence du Transport<br>Premium</h1>
      <p>Découvrez une expérience de transport d'exception où le raffinement se marie à la perfection. Notre flotte de véhicules d'exception et nos chauffeurs d'élite vous garantissent un voyage inoubliable, empreint d'élégance et de distinction.</p>
      <a href="#reservation" class="cta-button">Réserver votre expérience</a>
    </div>
  </section>

  <section class="services" id="services">
    <h2>Services Exclusifs</h2>
    <div class="service-grid">
      <div class="service-card">
        <i class="fas fa-car-side"></i>
        <h3>Transport VIP</h3>
        <p>Une flotte de véhicules prestigieux minutieusement sélectionnés, dotés des dernières innovations technologiques pour vous garantir une expérience de voyage d'exception.</p>
      </div>
      <div class="service-card">
        <i class="fas fa-plane"></i>
        <h3>Service Aéroport</h3>
        <p>Un accueil personnalisé et un service de conciergerie premium pour vos transferts aéroportuaires, dans une atmosphère de luxe et de sérénité absolue.</p>
      </div>
      <div class="service-card">
        <i class="fas fa-glass-cheers"></i>
        <h3>Événements Prestige</h3>
        <p>Une prestation sur mesure pour vos événements les plus exclusifs, avec un service d'exception minutieusement orchestré selon vos exigences les plus raffinées.</p>
      </div>
    </div>
  </section>

  <section class="reservation-section" id="reservation">
    <div class="reservation-container">
      <h2 style="text-align:center; color:#d4af37; font-family:'Playfair Display', serif; margin-bottom:50px; font-size:36px; text-transform:uppercase; letter-spacing:4px;">Réservation Premium</h2>
      
      <?php if (isset($success_message)) : ?>
        <div class="success-message" style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($error_message)) : ?>
        <div class="error-message" style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
          Erreur : <?php echo htmlspecialchars($error_message); ?>
        </div>
      <?php endif; ?>
      
      <form id="reservationForm" class="reservation-form" method="POST" action="">
        <div class="form-group">
          <label for="firstName">Prénom</label>
          <input type="text" id="firstName" name="firstName" required>
        </div>
        <div class="form-group">
          <label for="lastName">Nom</label>
          <input type="text" id="lastName" name="lastName" required>
        </div>
        <div class="form-group">
          <label for="phone">Téléphone</label>
          <input type="tel" id="phone" name="phone" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="departure">Adresse de départ</label>
          <input type="text" id="departure" name="departure" required>
        </div>
        <div class="form-group">
          <label for="arrival">Adresse d'arrivée</label>
          <input type="text" id="arrival" name="arrival" required>
        </div>
        <div class="form-group">
          <label for="date">Date</label>
          <input type="date" id="date" name="date" required>
        </div>
        <div class="form-group">
          <label for="time">Heure</label>
          <input type="time" id="time" name="time" required>
        </div>
        <div class="options-group">
          <div class="option-item">
            <input type="checkbox" id="fifthPassenger" name="fifthPassenger">
            <label for="fifthPassenger">5ème passager (+4€)</label>
          </div>
          <div class="option-item">
            <label for="luggage">Bagages (+2€/bagage)</label>
            <input type="number" id="luggage" name="luggage" min="0" max="10" value="0">
          </div>
        </div>
        <button type="button" class="submit-btn" id="calculateBtn">Calculer le trajet</button>
      </form>

      <div class="map-container">
        <div id="map" style="width: 100%; height: 400px;"></div>
      </div>

      <div class="results-container" id="results" style="display:none;">
        <div class="result-item"><h3>Distance</h3><p id="distance">- km</p></div>
        <div class="result-item"><h3>Durée estimée</h3><p id="duration">- min</p></div>
        <div class="result-item"><h3>Prix total</h3><p id="price">- €</p></div>
        <button type="submit" class="submit-btn" id="confirmBtn" form="reservationForm" style="display:none;">Confirmer la réservation</button>
      </div>
    </div>
  </section>

  <script src="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.19.0/maps/maps-web.min.js"></script>
  <script src="https://api.tomtom.com/maps-sdk-for-web/cdn/6.x/6.19.0/services/services-web.min.js"></script>
  <script>
    // Configuration de la carte TomTom
    const map = tt.map({
      key: '<?php echo TOMTOM_API_KEY; ?>',
      container: 'map',
      center: [2.3522, 48.8566], // Paris par défaut
      zoom: 10
    });

    let currentRoute = null;
    let departureMarker = null;
    let arrivalMarker = null;

    // Gestionnaire pour le calcul du trajet
    document.getElementById('calculateBtn').addEventListener('click', function() {
      const departure = document.getElementById('departure').value;
      const arrival = document.getElementById('arrival').value;
      const luggage = document.getElementById('luggage').value;
      const fifthPassenger = document.getElementById('fifthPassenger').checked;

      if (!departure || !arrival) {
        alert('Veuillez remplir les adresses de départ et d\'arrivée');
        return;
      }

      // Afficher un loader
      this.textContent = 'Calcul en cours...';
      this.disabled = true;

      // Requête AJAX pour calculer le trajet
      const params = new URLSearchParams({
        action: 'calculate',
        departure: departure,
        arrival: arrival,
        luggage: luggage,
        fifthPassenger: fifthPassenger ? '1' : '0'
      });

      fetch('?' + params.toString())
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Afficher les résultats
            document.getElementById('distance').textContent = data.distance + ' km';
            document.getElementById('duration').textContent = data.duration + ' min';
            document.getElementById('price').textContent = data.price + ' €';
            document.getElementById('results').style.display = 'block';
            document.getElementById('confirmBtn').style.display = 'block';

            // Afficher la route sur la carte
            displayRoute(data.departure_coords, data.arrival_coords);
          } else {
            alert('Erreur: ' + data.error);
          }
        })
        .catch(error => {
          console.error('Erreur:', error);
          alert('Erreur lors du calcul du trajet');
        })
        .finally(() => {
          this.textContent = 'Calculer le trajet';
          this.disabled = false;
        });
    });

    function displayRoute(departureCoords, arrivalCoords) {
      // Nettoyer les marqueurs précédents
      if (departureMarker) map.removeLayer(departureMarker);
      if (arrivalMarker) map.removeLayer(arrivalMarker);
      if (currentRoute) map.removeLayer(currentRoute);

      // Ajouter les marqueurs
      departureMarker = new tt.Marker({color: 'green'})
        .setLngLat([departureCoords[1], departureCoords[0]])
        .addTo(map);

      arrivalMarker = new tt.Marker({color: 'red'})
        .setLngLat([arrivalCoords[1], arrivalCoords[0]])
        .addTo(map);

      // Calculer et afficher la route
      tt.services.calculateRoute({
        key: '<?php echo TOMTOM_API_KEY; ?>',
        locations: [
          [departureCoords[1], departureCoords[0]],
          [arrivalCoords[1], arrivalCoords[0]]
        ]
      }).then(function(response) {
        const geojson = response.toGeoJson();
        currentRoute = map.addLayer({
          'id': 'route',
          'type': 'line',
          'source': {
            'type': 'geojson',
            'data': geojson
          },
          'layout': {
            'line-join': 'round',
            'line-cap': 'round'
          },
          'paint': {
            'line-color': '#d4af37',
            'line-width': 5
          }
        });

        // Ajuster la vue pour inclure toute la route
        const bounds = new tt.LngLatBounds();
        bounds.extend([departureCoords[1], departureCoords[0]]);
        bounds.extend([arrivalCoords[1], arrivalCoords[0]]);
        map.fitBounds(bounds, {padding: 50});
      });
    }
  </script>
</body>
</html>

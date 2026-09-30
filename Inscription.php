<?php
// Configuration des erreurs pour débogage
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Vérification de l'environnement PHP
if (version_compare(PHP_VERSION, '7.0.0') < 0) {
    die('PHP 7.0 ou supérieur requis. Version actuelle : ' . PHP_VERSION);
}

// Connexion à la base (fichier local, non versionné — voir config.example.php)
$config = require __DIR__ . '/config.php';

$host = $config['db']['host'];
$port = $config['db']['port'];
$dbname = $config['db']['name'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];
$charset = $config['db']['charset'];

$message = '';
$error = '';

// Test de connexion PDO
try {
    $testConnection = new PDO("mysql:host=$host;charset=$charset", $user, $pass);
    $testConnection = null; // Fermer la connexion de test
} catch (Exception $e) {
    die('❌ Erreur de connexion MySQL : ' . $e->getMessage());
}

try {
    // Connexion à la base de données
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=$charset", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Récupération des données du formulaire
        $prenom = trim($_POST['firstname'] ?? '');
        $nom = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $telephone = trim($_POST['telephone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        // Validation des champs obligatoires
        if (empty($prenom) || empty($nom) || empty($email) || empty($telephone) || empty($password) || empty($confirmPassword)) {
            $error = '❌ Tous les champs sont obligatoires.';
        }
        // Validation de l'email
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = '❌ Adresse email invalide.';
        }
        // Validation du téléphone
        elseif (!preg_match('/^\+?[0-9]{6,}$/', $telephone)) {
            $error = '❌ Numéro de téléphone invalide.';
        }
        // Validation du mot de passe
        elseif (strlen($password) < 12) {
            $error = '❌ Le mot de passe doit contenir au moins 12 caractères.';
        }
        // Vérification de la correspondance des mots de passe
        elseif ($password !== $confirmPassword) {
            $error = '❌ Les mots de passe ne correspondent pas.';
        }
        else {
            // Vérifie si l'email est déjà utilisé
            $check = $pdo->prepare('SELECT COUNT(*) FROM User WHERE EMAIL_USER = :email');
            $check->execute([':email' => $email]);

            if ($check->fetchColumn() > 0) {
                $error = '❌ Cet email est déjà utilisé.';
            } else {
                // Hash du mot de passe pour la sécurité
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insertion en base de données
                $stmt = $pdo->prepare('
                    INSERT INTO User (
                        ID_TYPE_USER,
                        NOM_USER,
                        PRENOM_USER,
                        EMAIL_USER,
                        MOT_DE_PASSE_USER,
                        TELEPHONE_USER,
                        DATE_CREATION_USER
                    ) VALUES (
                        :type, :nom, :prenom, :email, :password, :telephone, NOW()
                    )
                ');

                $stmt->execute([
                    ':type' => 'CLI',
                    ':nom' => $nom,
                    ':prenom' => $prenom,
                    ':email' => $email,
                    ':password' => $hashedPassword,
                    ':telephone' => $telephone
                ]);

                $message = '✅ Votre compte a été créé avec succès ! ID utilisateur : ' . $pdo->lastInsertId();
            }
        }
    }
} catch (PDOException $e) {
    $error = '❌ Erreur de base de données : ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Inscription - Elite Taxi</title>
  <link rel="stylesheet" href="Style.css" />
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap');
    
    body {
      background: #121212;
      color: #FFD700;
      font-family: 'Montserrat', sans-serif;
      margin: 0;
      padding: 0;
    }

    .container {
      max-width: 500px;
      margin: 60px auto;
      padding: 30px;
      border: 2px solid #FFD700;
      border-radius: 12px;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.1);
      background-color: #1E1E1E;
    }

    h1 {
      font-family: 'Playfair Display', serif;
      text-align: center;
      font-size: 32px;
      letter-spacing: 2px;
      margin-bottom: 10px;
    }

    .subtitle {
      text-align: center;
      font-size: 16px;
      color: #DDD;
      margin-bottom: 20px;
    }

    .underline {
      width: 60px;
      height: 3px;
      background: #FFD700;
      margin: 0 auto 30px;
      border-radius: 2px;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 15px;
    }

    @media (max-width: 500px) {
      .form-grid {
        grid-template-columns: 1fr;
      }
    }

    .form-group {
      position: relative;
    }

    .form-group label {
      position: absolute;
      width: 1px;
      height: 1px;
      padding: 0;
      margin: -1px;
      overflow: hidden;
      clip: rect(0,0,0,0);
      border: 0;
    }

    .form-group input {
      width: 100%;
      padding: 12px;
      background: #2c2c2c;
      color: #FFD700;
      border: 1px solid rgba(255,215,0,0.3);
      border-radius: 6px;
      font-size: 14px;
      box-sizing: border-box;
    }

    .form-actions {
      margin-top: 30px;
      text-align: center;
    }

    .btn-primary {
      background: #FFD700;
      color: #000;
      text-transform: uppercase;
      font-weight: bold;
      padding: 14px 30px;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
      letter-spacing: 1px;
      transition: background 0.3s, box-shadow 0.3s;
    }

    .btn-primary:hover {
      background: #e6c200;
      box-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
    }

    .back-link {
      display: block;
      margin-bottom: 20px;
      color: #AAA;
      text-decoration: none;
      font-size: 14px;
    }

    .back-link:hover {
      color: #FFD700;
    }

    .rgpd-note {
      text-align: center;
      font-size: 12px;
      color: #888;
      margin-top: 20px;
    }

    .error {
      color: #FF6B6B;
      text-align: center;
      margin: 15px 0;
      font-size: 14px;
      padding: 10px;
      background: rgba(255, 107, 107, 0.1);
      border-radius: 6px;
    }

    .success {
      color: #4CAF50;
      text-align: center;
      margin: 15px 0;
      font-size: 14px;
      padding: 10px;
      background: rgba(76, 175, 80, 0.1);
      border-radius: 6px;
    }
  </style>
</head>
<body>

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


  <div class="container">
    <a href="Login.html" class="back-link">← Retour</a>
    <h1>REJOIGNEZ L'ÉLITE</h1>
    <p class="subtitle">Découvrez un service de transport d'exception</p>
    <div class="underline"></div>

    <?php if ($error) : ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($message) : ?>
        <div class="success"><?php echo htmlspecialchars($message); ?></div>
        <div style="text-align: center; margin-top: 20px;">
            <a href="Login.html" style="color: #FFD700; text-decoration: none;">→ Se connecter maintenant</a>
        </div>
    <?php else : ?>
    <form method="POST" action="" autocomplete="on" novalidate>
      <div class="form-grid">
        <div class="form-group">
          <label for="firstname">Prénom</label>
          <input type="text" id="firstname" name="firstname" placeholder="Prénom" 
                 value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required />
        </div>
        <div class="form-group">
          <label for="lastname">Nom</label>
          <input type="text" id="lastname" name="lastname" placeholder="Nom" 
                 value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required />
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" placeholder="Adresse email" 
                 value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required />
        </div>
        <div class="form-group">
          <label for="telephone">Téléphone</label>
          <input type="tel" id="telephone" name="telephone" placeholder="Téléphone" 
                 value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>" 
                 required pattern="\+?[0-9]{6,}" />
        </div>
        <div class="form-group">
          <label for="password">Mot de passe</label>
          <input type="password" id="password" name="password" placeholder="Mot de passe" required />
        </div>
        <div class="form-group">
          <label for="confirmPassword">Confirmer le mot de passe</label>
          <input type="password" id="confirmPassword" name="confirmPassword" placeholder="Confirmer le mot de passe" required />
        </div>
      </div>

      <div class="form-actions">
        <button type="submit" class="btn-primary">CRÉER MON COMPTE</button>
      </div>

      <p class="rgpd-note">
        En créant un compte, vous acceptez nos
        <a href="Conditions d'utilisattion.html" style="color:#FFD700;">Conditions d'utilisation</a>
        et notre
        <a href="Confidantialite.html" style="color:#FFD700;">Politique de confidentialité</a>.
      </p>
    </form>

    <?php endif; ?>
  </div>

</body>
</html>

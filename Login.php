<?php
// login.php

session_start();

// 1. Paramètres BDD (fichier local, non versionné — voir config.example.php)
$config = require __DIR__ . '/config.php';

$host = $config['db']['host'];
$port = $config['db']['port'];
$db   = $config['db']['name'];
$user = $config['db']['user'];
$pass = $config['db']['pass'];
$charset = $config['db']['charset'];
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$error_message = '';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    // En production, ne pas divulguer l'erreur exacte
    $error_message = 'Erreur de connexion au serveur';
}

// 2. Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error_message) {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        // 3. Recherche de l'utilisateur dans la table User
        try {
            $stmt = $pdo->prepare('SELECT ID_USER, PRENOM_USER, NOM_USER, MOT_DE_PASSE_USER FROM User WHERE EMAIL_USER = ?');
            $stmt->execute([$email]);
            $user_data = $stmt->fetch();

            if (!$user_data) {
                $error_message = 'Email ou mot de passe incorrect';
            } else {
                // 4. Vérification du mot de passe hashé
                if (!password_verify($password, $user_data['MOT_DE_PASSE_USER'])) {
                    $error_message = 'Email ou mot de passe incorrect';
                } else {
                    // 5. Authentification réussie : création de session
                    $_SESSION['user_id']   = $user_data['ID_USER'];
                    $_SESSION['user_prenom'] = $user_data['PRENOM_USER'];
                    $_SESSION['user_nom'] = $user_data['NOM_USER'];
                    $_SESSION['user_email'] = $email;
                    
                    // 6. Redirection vers la page d'accueil
                    header('Location: Home page.html');
                    exit;
                }
            }
        } catch (PDOException $e) {
            $error_message = 'Erreur lors de la connexion';
        }
    }
}

// Récupération de l'erreur depuis l'URL (si redirection)
if (isset($_GET['error']) && empty($error_message)) {
    $error_message = $_GET['error'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Connexion - Elite Taxi</title>
  <link rel="stylesheet" href="Style.css">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Montserrat:wght@300;400;600&display=swap');
    
    body { 
      background: #121212; 
      color: #FFD700; 
      font-family: 'Montserrat', sans-serif; 
      margin: 0; 
      padding: 0; 
    }
    
    .navbar {
      background: #1E1E1E;
      padding: 15px 0;
      border-bottom: 1px solid #FFD700;
    }
    
    .navbar .logo {
      color: #FFD700;
      font-size: 24px;
      font-weight: bold;
      text-align: center;
    }
    
    .container { 
      max-width: 400px; 
      margin: 80px auto; 
      padding: 30px; 
      border: 2px solid #FFD700; 
      border-radius: 12px; 
      background: #1E1E1E;
      box-shadow: 0 0 20px rgba(255, 215, 0, 0.1);
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
    
    .form-group { 
      margin-bottom: 20px; 
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
    
    .form-group input:focus {
      outline: none;
      border-color: #FFD700;
      box-shadow: 0 0 5px rgba(255, 215, 0, 0.3);
    }
    
    .btn-primary {
      width: 100%; 
      background: #FFD700; 
      color: #000; 
      font-weight: bold;
      padding: 14px; 
      border: none; 
      border-radius: 6px; 
      font-size: 16px;
      cursor: pointer; 
      text-transform: uppercase; 
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
    
    .register-link {
      text-align: center;
      margin-top: 20px;
    }
    
    .register-link a {
      color: #FFD700;
      text-decoration: none;
      font-size: 14px;
    }
    
    .register-link a:hover {
      text-decoration: underline;
    }
    
    .rgpd-note {
      font-size: 12px; 
      text-align: center; 
      color: #888; 
      margin-top: 20px;
    }
    
    .rgpd-note a {
      color: #FFD700;
      text-decoration: none;
    }
    
    .rgpd-note a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="logo">A4 taxi</div>
  </nav>

  <div class="container">
    <a href="Home page.html" class="back-link">← Retour à l'accueil</a>
    <h1>CONNEXION</h1>
    <p class="subtitle">Accédez à votre espace client</p>
    <div class="underline"></div>
    
    <?php if (!empty($error_message)): ?>
      <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['success'])): ?>
      <div class="success">Inscription réussie ! Vous pouvez maintenant vous connecter.</div>
    <?php endif; ?>
    
    <form action="login.php" method="post" novalidate>
      <div class="form-group">
        <input type="email" name="email" placeholder="Adresse email" 
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
               required autocomplete="email">
      </div>
      <div class="form-group">
        <input type="password" name="password" placeholder="Mot de passe" 
               required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-primary">SE CONNECTER</button>
    </form>
    
    <div class="register-link">
      <p>Pas encore de compte ? 
        <a href="Inscription.php">Créer un compte Elite Taxi</a>
      </p>
    </div>
    
    <p class="rgpd-note">
      En vous connectant, vous acceptez nos
      <a href="Conditions d'utilisattion.html">Conditions d'utilisation</a>
      et notre
      <a href="Confidantialite.html">Politique de confidentialité</a>.
    </p>
  </div>
</body>
</html>
<?php
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

// PHPMailer est installé via Composer : voir composer.json
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    exit('PHPMailer est introuvable. Lancez « composer install » dans le dossier du projet.');
}
require __DIR__ . '/vendor/autoload.php';

// Configuration (fichier local, non versionné — voir config.example.php)
$config = require __DIR__ . '/config.php';

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $config['smtp']['host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp']['username'];
    $mail->Password = $config['smtp']['password'];
    $mail->SMTPSecure = 'tls';
    $mail->Port = $config['smtp']['port'];

    // Infos mail
    $mail->setFrom($config['smtp']['from'], $config['smtp']['from_name']);
    $mail->addAddress($_POST['email'],);

    $mail->Subject = '📨 Nouveau message via le formulaire';
    $mail->Body = "Email : {$_POST['email']}\n\nMessage : {$_POST['message']}";

    $mail->send();
    echo '✔️ Message envoyé avec succès.';
} catch (Exception $e) {
    echo "❌ Le message n'a pas pu être envoyé. Erreur : {$mail->ErrorInfo}";
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="Style.css">
  <title>Contactez-nous - Elite Taxi</title>
  <style>
    body { background: #121212; color: #FFD700; font-family: Arial, sans-serif; margin: 0; padding: 20px ; }
    .container { max-width: 500px; margin: 40px auto; padding: 24px; }
    h1 { text-align: center; font-size: 30px; margin-bottom: 30px; }
    label { display: block; font-size: 16px; margin-top: 15px; }
    input, textarea { width: 100%; background: #1e1e1e; color: #FFD700; border: 1px solid rgba(255,215,0,0.33); padding: 12px 14px; border-radius: 10px; font-size: 16px; }
    textarea { height: 120px; resize: vertical; }
    .password-group { position: relative; }
    .password-group input { padding-right: 44px; }
    .toggle-password { position: absolute; top: 50%; right: 14px; transform: translateY(-50%); background: none; border: none; color: #FFD700; cursor: pointer; font-size: 20px; }
    .button { background: #FFD700; color: #121212; border: none; padding: 15px; border-radius: 12px; width: 100%; margin-top: 30px; font-size: 18px; font-weight: bold; cursor: pointer; }
    .confirmation { text-align: center; color: #FFD700; margin-top: 20px; font-size: 16px; font-style: italic; opacity: 0; transition: opacity 0.8s; }
    .rgpd { font-size: 12px; text-align: center; margin-top: 30px; color: rgba(255,215,0,0.67); font-style: italic; }
  </style>
</head>
<body>
        <div class="gold-border top"></div>
    <div class="gold-border bottom"></div>
    <div class="gold-border left"></div>
    <div class="gold-border right"></div>

    <nav class="navbar">
        <div class="logo">ELITE TAXI</div>
        <div class="nav-links">
            <a href="Home page.html">Accueil</a>
            <a href="A propos.html">Services</a>
            <a href="Contac.html">Contact</a>
            <a href="Tarifs.html">Tarifs</a>
            <a href="Se connecter_Inscription.html">Connexion</a>
        </div>
    </nav>


  <div class="container">
    <h1>Contactez-nous</h1>
    <form id="contactForm">
      <label for="nom">Nom complet</label>
      <input type="text" id="nom" name="nom" placeholder="Ex : Jean Dupont" required>

      <label for="email">Adresse e-mail</label>
      <input type="email" id="email" name="email" placeholder="Ex : contact@exemple.com" required>

      <label for="message">Message</label>
      <textarea id="message" name="message" placeholder="Votre message ici..." required></textarea>

      <label for="password">Mot de passe (optionnel)</label>
      <div class="password-group">
        <input type="password" id="password" name="password" placeholder="Mot de passe sécurisé">
        <button type="button" class="toggle-password" id="togglePwd">👁️</button>
      </div>

      <button type="submit" class="button">Envoyer</button>
    </form>

    <div id="confirmation" class="confirmation"></div>

    <p class="rgpd">
      🔐 Vos informations (e-mail, message, mot de passe) sont traitées dans le respect du RGPD et des recommandations de la CNIL. Aucune donnée ne sera stockée sans votre accord préalable.
    </p>
  </div>

  <script>
    const form = document.getElementById('contactForm');
    const confirmationEl = document.getElementById('confirmation');
    const pwdInput = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePwd');

    toggleBtn.addEventListener('click', () => {
      const type = pwdInput.type === 'password' ? 'text' : 'password';
      pwdInput.type = type;
      toggleBtn.textContent = type === 'password' ? '👁️' : '🙈';
    });

    function validateEmail(email) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function validatePassword(pwd) {
      return pwd.length === 0 || (
        pwd.length >= 12 && /[A-Z]/.test(pwd) && /[a-z]/.test(pwd) && /[0-9]/.test(pwd) && /[^A-Za-z0-9]/.test(pwd)
      );
    }

    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const nom = form.nom.value.trim();
      const email = form.email.value.trim();
      const message = form.message.value.trim();
      const pwd = form.password.value;

      if (!nom || !email || !message) {
        showConfirmation('❌ Veuillez remplir tous les champs obligatoires.');
        return;
      }
      if (!validateEmail(email)) {
        showConfirmation("❌ L'adresse e-mail n'est pas valide.");
        return;
      }
      if (!validatePassword(pwd)) {
        showConfirmation('❌ Le mot de passe doit contenir au moins 12 caractères avec majuscule, minuscule, chiffre et caractère spécial.');
        return;
      }

      // Ici appel AJAX/fetch vers votre endpoint PHP si besoin

      form.reset();
      showConfirmation('✔️ Merci, votre message a été envoyé !');
    });

    function showConfirmation(msg) {
      confirmationEl.textContent = msg;
      confirmationEl.style.opacity = 1;
      setTimeout(() => confirmationEl.style.opacity = 0, 3800);
    }
  </script>
</body>
</html>

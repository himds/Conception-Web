<?php
  session_start();
  require_once("param.inc.php");

  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';

  if ($email === '' || $password === '') {
      $_SESSION['erreur'] = "Email ou mot de passe manquant";
      header('Location: connexion.php');
      exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
      $_SESSION['erreur'] = "Problème de connexion à la base de données";
      header('Location: connexion.php');
      exit;
  }

  if ($stmt = $mysqli->prepare("SELECT id, nom, prenom, email, password, role FROM compte WHERE email = ? LIMIT 1")) {
      $stmt->bind_param("s", $email);
      if ($stmt->execute()) {
          $result = $stmt->get_result();
          if ($row = $result->fetch_assoc()) {
              if (password_verify($password, $row['password'])) {
                  $_SESSION['user'] = [
                      'id' => $row['id'],
                      'nom' => $row['nom'],
                      'prenom' => $row['prenom'],
                      'email' => $row['email'],
                      'role' => (int)$row['role']
                  ];
                  $_SESSION['message'] = "Connexion réussie";
                  // Redirection selon le rôle
                  if((int)$row['role'] === 1){
                    header('Location: annonce_nouvelle.php');
                  } else if((int)$row['role'] === 2){
                    header('Location: mes_offres.php');
                  } else {
                    header('Location: index.php');
                  }
                  exit;
              }
          }
      }
  }

  $_SESSION['erreur'] = "Identifiants invalides";
  header('Location: connexion.php');
  exit;
?>
<?php
  session_start();
  if(!isset($_SESSION['user']) || !in_array((int)$_SESSION['user']['role'], [1,2], true)){
    $_SESSION['erreur'] = "Connexion requise";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $auteurId = (int)$_SESSION['user']['id'];
  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';
  if($annonceId <= 0 || $contenu === ''){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: index.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: annonce_detail.php?id='.$annonceId);
    exit;
  }

  // Déterminer type: si auteur est client de l'annonce => reponse, sinon question
  $type = 'question';
  if($stmt = $mysqli->prepare("SELECT client_id FROM annonce WHERE id=?")){
    $stmt->bind_param("i", $annonceId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
      if((int)$row['client_id'] === $auteurId){
        $type = 'reponse';
      }
    }
    $stmt->close();
  }

  if($stmt = $mysqli->prepare("INSERT INTO qa(annonce_id, auteur_id, contenu, type) VALUES (?,?,?,?)")){
    $stmt->bind_param("iiss", $annonceId, $auteurId, $contenu, $type);
    if($stmt->execute()){
      $_SESSION['message'] = "Message envoyé";
    } else {
      $_SESSION['erreur'] = "Impossible d'envoyer le message";
    }
    $stmt->close();
  }

  header('Location: annonce_detail.php?id='.$annonceId);
  exit;
?>



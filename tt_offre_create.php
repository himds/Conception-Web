<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $demenageurId = (int)$_SESSION['user']['id'];
  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  $prix = isset($_POST['prix_eur']) ? (float)$_POST['prix_eur'] : -1;
  $message = isset($_POST['message']) ? trim($_POST['message']) : '';

  if($annonceId <= 0 || $prix < 0) {
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

  // Vérifier annonce publiee et pas soi-même
  if($stmt = $mysqli->prepare("SELECT client_id, statut FROM annonce WHERE id = ?")){
    $stmt->bind_param("i", $annonceId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if(!$row){
      $_SESSION['erreur'] = "Annonce introuvable";
      header('Location: annonces.php');
      exit;
    }
    if((int)$row['client_id'] === $demenageurId){
      $_SESSION['erreur'] = "Vous ne pouvez pas proposer sur votre propre annonce";
      header('Location: annonce_detail.php?id='.$annonceId);
      exit;
    }
    if($row['statut'] !== 'publie'){
      $_SESSION['erreur'] = "Annonce non disponible";
      header('Location: annonce_detail.php?id='.$annonceId);
      exit;
    }
  }

  // Empêcher les doublons (un même déménageur peut mettre à jour son offre?)
  // Ici: si déjà une offre 'propose' existe, on met à jour sinon on insère
  if($stmt = $mysqli->prepare("SELECT id FROM offre WHERE annonce_id=? AND demenageur_id=? AND etat='propose' LIMIT 1")){
    $stmt->bind_param("ii", $annonceId, $demenageurId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($row = $res->fetch_assoc()){
      $offreId = (int)$row['id'];
      $stmt->close();
      if($stmtU = $mysqli->prepare("UPDATE offre SET prix_eur=?, message=? WHERE id=?")){
        $stmtU->bind_param("dsi", $prix, $message, $offreId);
        if($stmtU->execute()){
          $_SESSION['message'] = "Offre mise à jour";
        } else {
          $_SESSION['erreur'] = "Impossible de mettre à jour l'offre";
        }
        $stmtU->close();
      }
    } else {
      $stmt->close();
      if($stmtI = $mysqli->prepare("INSERT INTO offre(annonce_id, demenageur_id, prix_eur, message) VALUES (?,?,?,?)")){
        $stmtI->bind_param("iids", $annonceId, $demenageurId, $prix, $message);
        if($stmtI->execute()){
          $_SESSION['message'] = "Offre envoyée";
        } else {
          $_SESSION['erreur'] = "Impossible d'enregistrer l'offre";
        }
        $stmtI->close();
      }
    }
  }

  header('Location: annonce_detail.php?id='.$annonceId);
  exit;
?>



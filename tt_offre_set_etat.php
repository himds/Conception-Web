<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $clientId = (int)$_SESSION['user']['id'];
  $offreId = isset($_POST['offre_id']) ? (int)$_POST['offre_id'] : 0;
  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  $etat = isset($_POST['etat']) ? $_POST['etat'] : '';

  if($offreId <= 0 || $annonceId <= 0 || !in_array($etat, ['accepte','refuse'], true)){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: annonce_offres.php?id='.$annonceId);
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: annonce_offres.php?id='.$annonceId);
    exit;
  }

  // 检查账户是否被停用
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if($checkActif && $checkActif->num_rows > 0) {
    if($stmtCheck = $mysqli->prepare("SELECT actif FROM compte WHERE id=?")){
      $stmtCheck->bind_param("i", $clientId);
      $stmtCheck->execute();
      $resCheck = $stmtCheck->get_result();
      if($userRow = $resCheck->fetch_assoc()){
        if(isset($userRow['actif']) && $userRow['actif'] == 0){
          $_SESSION['erreur'] = "Votre compte a été désactivé. Vous ne pouvez pas modifier les offres.";
          header('Location: annonce_offres.php?id='.$annonceId);
          exit;
        }
      }
      $stmtCheck->close();
    }
  }

  // Vérifier propriété de l'annonce et état
  if($stmt = $mysqli->prepare("SELECT id, statut FROM annonce WHERE id=? AND client_id=?")){
    $stmt->bind_param("ii", $annonceId, $clientId);
    $stmt->execute();
    $res = $stmt->get_result();
    $annonce = $res->fetch_assoc();
    $stmt->close();
    if(!$annonce){
      $_SESSION['erreur'] = "Accès refusé";
      header('Location: annonces.php');
      exit;
    }
    if($annonce['statut'] !== 'publie'){
      $_SESSION['erreur'] = "Annonce non modifiable";
      header('Location: annonce_offres.php?id='.$annonceId);
      exit;
    }
  }

  // Mettre à jour l'état de l'offre
  if($stmt = $mysqli->prepare("UPDATE offre SET etat=? WHERE id=? AND annonce_id=?")){
    $stmt->bind_param("sii", $etat, $offreId, $annonceId);
    if($stmt->execute()){
      $_SESSION['message'] = "Offre mise à jour";
      // Si acceptée: optionnellement clôturer l'annonce
      if($etat === 'accepte'){
        if($stmt2 = $mysqli->prepare("UPDATE annonce SET statut='cloture' WHERE id=?")){
          $stmt2->bind_param("i", $annonceId);
          $stmt2->execute();
          $stmt2->close();
        }
      }
    } else {
      $_SESSION['erreur'] = "Impossible de mettre à jour l'offre";
    }
    $stmt->close();
  }

  header('Location: annonce_offres.php?id='.$annonceId);
  exit;
?>



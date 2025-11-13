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

  // 检查账户是否被停用 / Vérifier si le compte est désactivé
  // 被停用的账户不能创建报价
  // Les comptes désactivés ne peuvent pas créer d'offres
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if($checkActif && $checkActif->num_rows > 0) {
    if($stmtCheck = $mysqli->prepare("SELECT actif FROM compte WHERE id=?")){
      $stmtCheck->bind_param("i", $demenageurId);
      $stmtCheck->execute();
      $resCheck = $stmtCheck->get_result();
      if($userRow = $resCheck->fetch_assoc()){
        if(isset($userRow['actif']) && $userRow['actif'] == 0){
          $_SESSION['erreur'] = "Votre compte a été désactivé. Vous ne pouvez pas créer d'offre.";
          header('Location: annonce_detail.php?id='.$annonceId);
          exit;
        }
      }
      $stmtCheck->close();
    }
  }

  // Vérifier annonce publiee et pas soi-même
  // 只能对已发布的公告提出报价，且不能在自己的公告上提出报价
  // Ne peut proposer que sur les annonces publiées, et ne peut pas proposer sur ses propres annonces
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
    // 不能在自己的公告上提出报价 / Ne peut pas proposer sur sa propre annonce
    if((int)$row['client_id'] === $demenageurId){
      $_SESSION['erreur'] = "Vous ne pouvez pas proposer sur votre propre annonce";
      header('Location: annonce_detail.php?id='.$annonceId);
      exit;
    }
    // 只能对已发布的公告提出报价 / Ne peut proposer que sur les annonces publiées
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



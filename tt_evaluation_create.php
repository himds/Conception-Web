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
  $nominationId = isset($_POST['nomination_id']) ? (int)$_POST['nomination_id'] : 0;
  $note = isset($_POST['note']) ? (int)$_POST['note'] : 0;
  $commentaire = isset($_POST['commentaire']) ? trim($_POST['commentaire']) : '';

  if(($offreId <= 0 && $nominationId <= 0) || $note < 1 || $note > 5){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: index.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    if($nominationId > 0){
      header('Location: evaluation_create.php?nomination_id='.$nominationId);
    } else {
      header('Location: evaluation_create.php?offre_id='.$offreId);
    }
    exit;
  }

  $demenageurId = null;
  $finalOffreId = $offreId;

  if($nominationId > 0){
    // 通过指名获取信息
    $sql = "SELECT n.demenageur_id, o.id AS offre_id, a.id AS annonce_id 
            FROM nomination n 
            JOIN annonce a ON a.id=n.annonce_id 
            LEFT JOIN offre o ON o.nomination_id=n.id 
            WHERE n.id=? AND a.client_id=? AND n.etat='accepte'";
    if($stmt = $mysqli->prepare($sql)){
      $stmt->bind_param("ii", $nominationId, $clientId);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res->fetch_assoc();
      $stmt->close();
      if(!$row){
        $_SESSION['erreur'] = "Accès refusé";
        $mysqli->close();
        header('Location: annonces.php');
        exit;
      }
      $demenageurId = (int)$row['demenageur_id'];
      if($row['offre_id']){
        $finalOffreId = (int)$row['offre_id'];
      }
    }

    // 检查是否已评价
    if($stmt = $mysqli->prepare("SELECT id FROM evaluation WHERE nomination_id=? LIMIT 1")){
      $stmt->bind_param("i", $nominationId);
      $stmt->execute();
      $res = $stmt->get_result();
      if($res->fetch_assoc()){
        $_SESSION['erreur'] = "Évaluation déjà envoyée";
        $stmt->close();
        $mysqli->close();
        header('Location: annonces.php');
        exit;
      }
      $stmt->close();
    }
  } else {
    // 原有逻辑：通过出价获取信息
    $sql = "SELECT o.demenageur_id, o.nomination_id, a.id AS annonce_id FROM offre o JOIN annonce a ON a.id=o.annonce_id WHERE o.id=? AND a.client_id=? AND a.statut='cloture' AND o.etat='accepte'";
    if($stmt = $mysqli->prepare($sql)){
      $stmt->bind_param("ii", $offreId, $clientId);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res->fetch_assoc();
      $stmt->close();
      if(!$row){
        $_SESSION['erreur'] = "Accès refusé";
        $mysqli->close();
        header('Location: annonces.php');
        exit;
      }
      $demenageurId = (int)$row['demenageur_id'];
      if($row['nomination_id']){
        $nominationId = (int)$row['nomination_id'];
      }
    }

    // 检查是否已评价
    if($stmt = $mysqli->prepare("SELECT id FROM evaluation WHERE offre_id=? LIMIT 1")){
      $stmt->bind_param("i", $offreId);
      $stmt->execute();
      $res = $stmt->get_result();
      if($res->fetch_assoc()){
        $_SESSION['erreur'] = "Évaluation déjà envoyée";
        $stmt->close();
        $mysqli->close();
        header('Location: annonces.php');
        exit;
      }
      $stmt->close();
    }
  }

  // 创建评价
  if($nominationId > 0){
    // 如果有指名ID，使用指名ID
    if($stmt = $mysqli->prepare("INSERT INTO evaluation(offre_id, nomination_id, client_id, demenageur_id, note, commentaire) VALUES (?,?,?,?,?,?)")){
      $offreIdParam = $finalOffreId > 0 ? $finalOffreId : null;
      $stmt->bind_param("iiiiis", $offreIdParam, $nominationId, $clientId, $demenageurId, $note, $commentaire);
      if($stmt->execute()){
        $_SESSION['message'] = "Évaluation enregistrée";
        // 更新公告状态为cloture
        if($stmt2 = $mysqli->prepare("UPDATE annonce a JOIN nomination n ON n.annonce_id=a.id SET a.statut='cloture' WHERE n.id=?")){
          $stmt2->bind_param("i", $nominationId);
          $stmt2->execute();
          $stmt2->close();
        }
      } else {
        $_SESSION['erreur'] = "Impossible d'enregistrer l'évaluation";
      }
      $stmt->close();
    }
  } else {
    // 原有逻辑
    if($stmt = $mysqli->prepare("INSERT INTO evaluation(offre_id, client_id, demenageur_id, note, commentaire) VALUES (?,?,?,?,?)")){
      $stmt->bind_param("iiiis", $offreId, $clientId, $demenageurId, $note, $commentaire);
      if($stmt->execute()){
        $_SESSION['message'] = "Évaluation enregistrée";
      } else {
        $_SESSION['erreur'] = "Impossible d'enregistrer l'évaluation";
      }
      $stmt->close();
    }
  }

  $mysqli->close();
  header('Location: annonces.php');
  exit;
?>



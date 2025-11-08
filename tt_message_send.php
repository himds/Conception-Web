<?php
  session_start();
  if(!isset($_SESSION['user']) || !in_array((int)$_SESSION['user']['role'], [1,2], true)) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $userId = (int)$_SESSION['user']['id'];
  $nominationId = isset($_POST['nomination_id']) ? (int)$_POST['nomination_id'] : 0;
  $contenu = isset($_POST['contenu']) ? trim($_POST['contenu']) : '';

  if($nominationId <= 0 || $contenu === ''){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: index.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: messages.php?nomination_id='.$nominationId);
    exit;
  }

  // 验证用户有权发送消息
  $sql = "SELECT n.id FROM nomination n JOIN annonce a ON a.id=n.annonce_id WHERE n.id=? AND n.etat='accepte' AND (a.client_id=? OR n.demenageur_id=?)";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("iii", $nominationId, $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Accès refusé";
      $stmt->close();
      $mysqli->close();
      header('Location: index.php');
      exit;
    }
    $stmt->close();
  }

  // 发送消息
  if($stmt = $mysqli->prepare("INSERT INTO message(nomination_id, expediteur_id, contenu) VALUES (?,?,?)")){
    $stmt->bind_param("iis", $nominationId, $userId, $contenu);
    if($stmt->execute()){
      $_SESSION['message'] = "Message envoyé";
    } else {
      $_SESSION['erreur'] = "Impossible d'envoyer le message";
    }
    $stmt->close();
  }

  $mysqli->close();
  header('Location: messages.php?nomination_id='.$nominationId);
  exit;
?>




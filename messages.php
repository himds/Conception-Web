<?php
  session_start();
  if(!isset($_SESSION['user']) || !in_array((int)$_SESSION['user']['role'], [1,2], true)) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Messages";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $userId = (int)$_SESSION['user']['id'];
  $nominationId = isset($_GET['nomination_id']) ? (int)$_GET['nomination_id'] : 0;

  if($nominationId <= 0){
    $_SESSION['erreur'] = "Nomination introuvable";
    header('Location: index.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  // 验证用户有权访问这个指名
  $sql = "SELECT n.id, n.etat, a.id AS annonce_id, a.titre, a.client_id, n.demenageur_id, c1.prenom AS client_prenom, c1.nom AS client_nom, c2.prenom AS demenageur_prenom, c2.nom AS demenageur_nom
          FROM nomination n 
          JOIN annonce a ON a.id=n.annonce_id 
          JOIN compte c1 ON c1.id=a.client_id 
          JOIN compte c2 ON c2.id=n.demenageur_id 
          WHERE n.id=? AND (a.client_id=? OR n.demenageur_id=?)";
  
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("iii", $nominationId, $userId, $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $nomination = $res->fetch_assoc();
    $stmt->close();
    
    if(!$nomination){
      $_SESSION['erreur'] = "Accès refusé";
      $mysqli->close();
      header('Location: index.php');
      exit;
    }
  }

  // 获取对方信息
  $otherUser = null;
  if((int)$_SESSION['user']['role'] === 1){
    // 客户：对方是搬家工人
    $otherUser = ['id' => $nomination['demenageur_id'], 'prenom' => $nomination['demenageur_prenom'], 'nom' => $nomination['demenageur_nom']];
  } else {
    // 搬家工人：对方是客户
    $otherUser = ['id' => $nomination['client_id'], 'prenom' => $nomination['client_prenom'], 'nom' => $nomination['client_nom']];
  }

  echo '<div class="index-page-background">';
  echo '<div class="container-fluid my-4">';
  // 返回按钮
  echo '<div class="mb-3">';
  if((int)$_SESSION['user']['role'] === 1){
    echo '<a href="mes_annonces.php" class="btn btn-outline-secondary">← Retour à mes annonces</a>';
  } else {
    echo '<a href="mes_nominations.php" class="btn btn-outline-secondary">← Retour à mes nominations</a>';
  }
  echo '</div>';
  echo '<h1>Messages - '.htmlspecialchars($nomination['titre']).'</h1>';
  echo '<p class="text-muted">Conversation avec '.htmlspecialchars($otherUser['prenom'].' '.$otherUser['nom']).'</p>';

  // 显示消息
  $sql = "SELECT m.id, m.contenu, m.created_at, m.expediteur_id, c.prenom, c.nom 
          FROM message m 
          JOIN compte c ON c.id=m.expediteur_id 
          WHERE m.nomination_id=? 
          ORDER BY m.created_at ASC";
  
  echo '<div class="card mb-3" style="max-height: 500px; overflow-y: auto;">';
  echo '<div class="card-body">';
  
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $nominationId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Aucun message pour le moment.</div>';
    } else {
      while($row = $res->fetch_assoc()){
        $isMine = (int)$row['expediteur_id'] === $userId;
        $alignClass = $isMine ? 'text-end' : 'text-start';
        $bgClass = $isMine ? 'bg-primary text-white' : 'bg-light';
        
        echo '<div class="mb-3 '.$alignClass.'">';
        echo '<div class="d-inline-block p-2 rounded '.$bgClass.'" style="max-width: 70%;">';
        echo '<div><strong>'.htmlspecialchars($row['prenom'].' '.$row['nom']).'</strong></div>';
        echo '<div>'.nl2br(htmlspecialchars($row['contenu'])).'</div>';
        echo '<small>'.htmlspecialchars($row['created_at']).'</small>';
        echo '</div>';
        echo '</div>';
      }
    }
    $stmt->close();
  }
  
  echo '</div>';
  echo '</div>';

  // 发送消息表单
  if($nomination['etat'] === 'accepte'){
    echo '<form method="POST" action="tt_message_send.php">';
    echo '<input type="hidden" name="nomination_id" value="'.(int)$nominationId.'">';
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="contenu">Votre message</label>';
    echo '<textarea class="form-control" id="contenu" name="contenu" rows="3" required></textarea>';
    echo '</div>';
    echo '<button class="btn btn-primary" type="submit">Envoyer</button>';
    echo '</form>';
  } else {
    echo '<div class="alert alert-warning">Vous ne pouvez envoyer des messages qu\'après avoir accepté la nomination.</div>';
  }

  echo '</div>';
  echo '</div>';
  $mysqli->close();
  include('footer.inc.php');
?>



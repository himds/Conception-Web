<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Offres sur mon annonce";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $annonceId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if($annonceId <= 0){
    echo '<div class="index-page-background">';
    echo '<div class="container-fluid my-4">';
    echo '<div class="alert alert-danger">Annonce introuvable.</div>';
    echo '</div>';
    echo '</div>';
    include('footer.inc.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="index-page-background">';
    echo '<div class="container-fluid my-4">';
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    echo '</div>';
    echo '</div>';
    include('footer.inc.php');
    exit;
  }

  // Vérifier que l'annonce appartient au client connecté
  if($stmt = $mysqli->prepare("SELECT id, titre, statut FROM annonce WHERE id=? AND client_id=?")){
    $stmt->bind_param("ii", $annonceId, $_SESSION['user']['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $annonce = $res->fetch_assoc();
    $stmt->close();
    if(!$annonce){
      echo '<div class="index-page-background">';
      echo '<div class="container-fluid my-4">';
      echo '<div class="alert alert-danger">Accès refusé.</div>';
      echo '</div>';
      echo '</div>';
      include('footer.inc.php');
      exit;
    }
  }

  echo '<div class="index-page-background">';
  echo '<div class="container-fluid my-4">';
  // 返回按钮
  echo '<div class="mb-3">';
  echo '<a href="mes_annonces.php" class="btn btn-outline-secondary">← Retour à mes annonces</a>';
  echo '</div>';
  echo '<h1>Offres - '.htmlspecialchars($annonce['titre']).'</h1>';

  // Récupérer les offres (包括指名相关的)
  if($stmt = $mysqli->prepare("SELECT o.id, o.prix_eur, o.message, o.etat, o.created_at, o.nomination_id, c.nom, c.prenom, n.etat AS nomination_etat FROM offre o JOIN compte c ON c.id=o.demenageur_id LEFT JOIN nomination n ON n.id=o.nomination_id WHERE o.annonce_id=? ORDER BY o.created_at DESC")){
    $stmt->bind_param("i", $annonceId);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Aucune offre pour le moment.</div>';
    } else {
      echo '<div class="table-responsive">';
      echo '<table class="table table-striped">';
      echo '<thead><tr><th>Déménageur</th><th>Prix (€)</th><th>Message</th><th>État</th><th>Actions</th></tr></thead>';
      echo '<tbody>';
      while($row = $res->fetch_assoc()){
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['prenom'].' '.$row['nom']).'</td>';
        echo '<td>'.htmlspecialchars((string)$row['prix_eur']).'</td>';
        echo '<td>'.htmlspecialchars($row['message']).'</td>';
        echo '<td>'.htmlspecialchars($row['etat']).'</td>';
        echo '<td>';
        if($annonce['statut'] === 'publie'){
          if($row['etat'] === 'propose'){
            echo '<form class="d-inline" method="POST" action="tt_offre_set_etat.php">';
            echo '<input type="hidden" name="offre_id" value="'.(int)$row['id'].'">';
            echo '<input type="hidden" name="annonce_id" value="'.(int)$annonceId.'">';
            echo '<input type="hidden" name="etat" value="accepte">';
            echo '<button class="btn btn-sm btn-success" type="submit">Accepter</button>';
            echo '</form> ';
            echo '<form class="d-inline" method="POST" action="tt_offre_set_etat.php">';
            echo '<input type="hidden" name="offre_id" value="'.(int)$row['id'].'">';
            echo '<input type="hidden" name="annonce_id" value="'.(int)$annonceId.'">';
            echo '<input type="hidden" name="etat" value="refuse">';
            echo '<button class="btn btn-sm btn-outline-danger" type="submit">Refuser</button>';
            echo '</form>';
          }
        }
        // 如果是指名且已接受，显示消息链接
        if(isset($row['nomination_id']) && $row['nomination_id'] && $row['nomination_etat'] === 'accepte'){
          echo ' <a class="btn btn-sm btn-outline-info" href="messages.php?nomination_id='.(int)$row['nomination_id'].'">Messages</a>';
        }
        // Si annonce cloturée，可对被接受的出价进行评价
        if($annonce['statut'] === 'cloture' && $row['etat'] === 'accepte'){
          // 检查是否已评价
          $hasEvaluation = false;
          if(isset($row['nomination_id']) && $row['nomination_id']){
            if($stmtEval = $mysqli->prepare("SELECT id FROM evaluation WHERE nomination_id=?")){
              $stmtEval->bind_param("i", $row['nomination_id']);
              $stmtEval->execute();
              $resEval = $stmtEval->get_result();
              $hasEvaluation = $resEval->num_rows > 0;
              $stmtEval->close();
            }
          } else {
            if($stmtEval = $mysqli->prepare("SELECT id FROM evaluation WHERE offre_id=?")){
              $stmtEval->bind_param("i", $row['id']);
              $stmtEval->execute();
              $resEval = $stmtEval->get_result();
              $hasEvaluation = $resEval->num_rows > 0;
              $stmtEval->close();
            }
          }
          
          if(!$hasEvaluation){
            if(isset($row['nomination_id']) && $row['nomination_id']){
              echo ' <a class="btn btn-sm btn-outline-primary" href="evaluation_create.php?nomination_id='.(int)$row['nomination_id'].'">Évaluer</a>';
            } else {
              echo ' <a class="btn btn-sm btn-outline-primary" href="evaluation_create.php?offre_id='.(int)$row['id'].'">Évaluer</a>';
            }
          } else {
            echo ' <span class="badge bg-success">Déjà évalué</span>';
          }
        }
        // 如果出价被接受且是指名，显示完成搬家按钮
        if($row['etat'] === 'accepte' && isset($row['nomination_id']) && $row['nomination_id'] && $annonce['statut'] === 'publie'){
          // 检查是否已完成
          $isCompleted = false;
          if($stmtComp = $mysqli->prepare("SELECT id FROM evaluation WHERE nomination_id=?")){
            $stmtComp->bind_param("i", $row['nomination_id']);
            $stmtComp->execute();
            $resComp = $stmtComp->get_result();
            $isCompleted = $resComp->num_rows > 0;
            $stmtComp->close();
          }
          
          if(!$isCompleted){
            echo ' <form class="d-inline" method="POST" action="tt_complete_demenagement.php">';
            echo '<input type="hidden" name="nomination_id" value="'.(int)$row['nomination_id'].'">';
            echo '<input type="hidden" name="offre_id" value="'.(int)$row['id'].'">';
            echo '<input type="hidden" name="annonce_id" value="'.(int)$annonceId.'">';
            echo '<button class="btn btn-sm btn-success" type="submit">Marquer comme terminé</button>';
            echo '</form>';
          }
        }
        echo '</td>';
        echo '</tr>';
      }
      echo '</tbody></table></div>';
    }
    $stmt->close();
  }

  echo '</div>';
  echo '</div>';
  include('footer.inc.php');
?>



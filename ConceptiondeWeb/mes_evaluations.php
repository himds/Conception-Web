<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Mes évaluations";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  // 列出对当前搬家人员的评价
  $sql = "SELECT e.note, e.commentaire, e.created_at, a.titre, c.prenom, c.nom FROM evaluation e JOIN offre o ON o.id=e.offre_id JOIN annonce a ON a.id=o.annonce_id JOIN compte c ON c.id=e.client_id WHERE e.demenageur_id=? ORDER BY e.created_at DESC";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    echo '<h1>Mes évaluations</h1>';
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Aucune évaluation.</div>';
    } else {
      echo '<hr class="section-divider">';
      echo '<div class="p-3 rounded bg-orange-100">';
      echo '<div class="list-group">';
      while($row = $res->fetch_assoc()){
        echo '<div class="list-group-item">';
        echo '<div class="d-flex justify-content-between">';
        echo '<div><strong>'.htmlspecialchars($row['titre']).'</strong> · <span class="text-muted">par '.htmlspecialchars($row['prenom'].' '.$row['nom']).'</span></div>';
        echo '<div><span class="badge badge-orange">'.htmlspecialchars((string)$row['note']).'/5</span></div>';
        echo '</div>';
        if($row['commentaire'] !== null && $row['commentaire'] !== ''){
          echo '<div class="mt-2">'.nl2br(htmlspecialchars($row['commentaire'])).'</div>';
        }
        echo '<div class="mt-2"><small class="text-muted">'.htmlspecialchars($row['created_at']).'</small></div>';
        echo '</div>';
      }
      echo '</div>';
      echo '</div>';
    }
    $stmt->close();
  }

  include('footer.inc.php');
?>



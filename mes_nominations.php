<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Mes nominations";
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

  $demenageurId = (int)$_SESSION['user']['id'];

  echo '<div class="index-page-background">';
  echo '<div class="container-fluid my-4">';
  echo '<h1>Mes nominations</h1>';

  // 获取所有指名
  $sql = "SELECT n.id, n.etat, n.created_at, a.id AS annonce_id, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut, a.nb_demenageurs, c.prenom, c.nom, c.email 
          FROM nomination n 
          JOIN annonce a ON a.id=n.annonce_id 
          JOIN compte c ON c.id=a.client_id 
          WHERE n.demenageur_id=? 
          ORDER BY n.created_at DESC";
  
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $demenageurId);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Aucune nomination pour le moment.</div>';
    } else {
      echo '<div class="table-responsive">';
      echo '<table class="table table-striped">';
      echo '<thead><tr><th>Annonce</th><th>Client</th><th>Itinéraire</th><th>Date</th><th>État</th><th>Actions</th></tr></thead>';
      echo '<tbody>';
      
      while($row = $res->fetch_assoc()){
        echo '<tr>';
        echo '<td><a href="annonce_detail.php?id='.(int)$row['annonce_id'].'">'.htmlspecialchars($row['titre']).'</a></td>';
        echo '<td>'.htmlspecialchars($row['prenom'].' '.$row['nom']).'<br><small>'.htmlspecialchars($row['email']).'</small></td>';
        echo '<td>'.htmlspecialchars($row['ville_depart'].' → '.$row['ville_arrivee']).'</td>';
        echo '<td>'.htmlspecialchars($row['date_debut']).'</td>';
        
        $badgeClass = $row['etat'] === 'accepte' ? 'success' : ($row['etat'] === 'refuse' ? 'danger' : 'warning');
        echo '<td><span class="badge bg-'.$badgeClass.'">'.htmlspecialchars($row['etat']).'</span></td>';
        
        echo '<td>';
        if($row['etat'] === 'en_attente'){
          echo '<form class="d-inline" method="POST" action="tt_nomination_respond.php">';
          echo '<input type="hidden" name="nomination_id" value="'.(int)$row['id'].'">';
          echo '<input type="hidden" name="action" value="accepte">';
          echo '<button class="btn btn-sm btn-success" type="submit">Accepter</button>';
          echo '</form> ';
          echo '<form class="d-inline" method="POST" action="tt_nomination_respond.php">';
          echo '<input type="hidden" name="nomination_id" value="'.(int)$row['id'].'">';
          echo '<input type="hidden" name="action" value="refuse">';
          echo '<button class="btn btn-sm btn-outline-danger" type="submit">Refuser</button>';
          echo '</form>';
        } else if($row['etat'] === 'accepte'){
          // 检查是否已有报价
          $hasOffre = false;
          if($stmtOffre = $mysqli->prepare("SELECT id FROM offre WHERE nomination_id=?")){
            $stmtOffre->bind_param("i", $row['id']);
            $stmtOffre->execute();
            $resOffre = $stmtOffre->get_result();
            $hasOffre = $resOffre->num_rows > 0;
            $stmtOffre->close();
          }
          
          if(!$hasOffre){
            echo '<a class="btn btn-sm btn-primary" href="offre_create.php?nomination_id='.(int)$row['id'].'">Faire une offre</a> ';
          } else {
            echo '<a class="btn btn-sm btn-outline-primary" href="offre_create.php?nomination_id='.(int)$row['id'].'">Modifier l\'offre</a> ';
          }
          echo '<a class="btn btn-sm btn-outline-info" href="messages.php?nomination_id='.(int)$row['id'].'">Messages</a>';
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
  $mysqli->close();
  include('footer.inc.php');
?>



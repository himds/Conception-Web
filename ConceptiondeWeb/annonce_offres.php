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
    echo '<div class="alert alert-danger">Annonce introuvable.</div>';
    include('footer.inc.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
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
      echo '<div class="alert alert-danger">Accès refusé.</div>';
      include('footer.inc.php');
      exit;
    }
  }

  echo '<h1>Offres - '.htmlspecialchars($annonce['titre']).'</h1>';

  // Récupérer les offres
  if($stmt = $mysqli->prepare("SELECT o.id, o.prix_eur, o.message, o.etat, o.created_at, c.nom, c.prenom FROM offre o JOIN compte c ON c.id=o.demenageur_id WHERE o.annonce_id=? ORDER BY o.created_at DESC")){
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
        // Si annonce cloturée，可对被接受的出价进行评价
        if($annonce['statut'] === 'cloture' && $row['etat'] === 'accepte'){
          echo ' <a class="btn btn-sm btn-outline-primary" href="evaluation_create.php?offre_id='.(int)$row['id'].'">Évaluer</a>';
        }
        echo '</td>';
        echo '</tr>';
      }
      echo '</tbody></table></div>';
    }
    $stmt->close();
  }

  include('footer.inc.php');
?>



<?php
  session_start();
  $titre = "Détail annonce";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if($id <= 0){
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

  $annonce = null;
  if($stmt = $mysqli->prepare("SELECT a.*, c.prenom, c.nom FROM annonce a JOIN compte c ON c.id=a.client_id WHERE a.id=?")){
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $annonce = $res->fetch_assoc();
    $stmt->close();
  }

  if(!$annonce){
    echo '<div class="alert alert-danger">Annonce introuvable.</div>';
    include('footer.inc.php');
    exit;
  }

  echo '<div class="mb-3">';
  echo '<h1>'.htmlspecialchars($annonce['titre']).'</h1>';
  echo '<p class="text-muted">Client: '.htmlspecialchars($annonce['prenom'].' '.$annonce['nom']).'</p>';
  echo '<p>'.nl2br(htmlspecialchars($annonce['description'] ?? '')).'</p>';
  echo '<ul class="list-unstyled">';
  echo '<li><strong>Départ:</strong> '.htmlspecialchars($annonce['ville_depart']).' ('.htmlspecialchars($annonce['depart_type']).', étage '.htmlspecialchars((string)$annonce['depart_etage']).', ascenseur '.($annonce['depart_ascenseur']? 'oui':'non').')</li>';
  echo '<li><strong>Arrivée:</strong> '.htmlspecialchars($annonce['ville_arrivee']).' ('.htmlspecialchars($annonce['arrivee_type']).', étage '.htmlspecialchars((string)$annonce['arrivee_etage']).', ascenseur '.($annonce['arrivee_ascenseur']? 'oui':'non').')</li>';
  echo '<li><strong>Date:</strong> '.htmlspecialchars($annonce['date_debut']).'</li>';
  echo '<li><strong>Volume:</strong> '.htmlspecialchars((string)$annonce['volume_m3']).' m³</li>';
  echo '<li><strong>Poids:</strong> '.htmlspecialchars((string)$annonce['poids_kg']).' kg</li>';
  echo '<li><strong>Déménageurs:</strong> '.htmlspecialchars((string)$annonce['nb_demenageurs']).'</li>';
  echo '</ul>';
  echo '</div>';

  // Images
  if($stmt = $mysqli->prepare("SELECT path FROM annonce_image WHERE annonce_id=?")){
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows>0){
      echo '<div class="row">';
      while($img = $res->fetch_assoc()){
        echo '<div class="col-md-3 mb-3"><img class="img-fluid rounded" src="'.htmlspecialchars($img['path']).'" alt="image"></div>';
      }
      echo '</div>';
    }
    $stmt->close();
  }

  include('footer.inc.php');
?>



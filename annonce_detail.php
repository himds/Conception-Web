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

  // Formulaire de proposition (réservé aux déménageurs)
  if(isset($_SESSION['user']) && (int)$_SESSION['user']['role'] === 2) {
    // Ne pas proposer sur sa propre annonce et uniquement si publiée
    if((int)$_SESSION['user']['id'] !== (int)$annonce['client_id'] && $annonce['statut'] === 'publie') {
      echo '<div class="card my-4">';
      echo '<div class="card-body">';
      echo '<h5 class="card-title">Proposer vos services</h5>';
      echo '<form method="POST" action="tt_offre_create.php">';
      echo '<input type="hidden" name="annonce_id" value="'.(int)$annonce['id'].'">';
      echo '<div class="row g-3 align-items-end">';
      echo '<div class="col-md-3">';
      echo '<label class="form-label" for="prix_eur">Prix proposé (€)</label>';
      echo '<input class="form-control" type="number" step="0.01" min="0" id="prix_eur" name="prix_eur" required>';
      echo '</div>';
      echo '<div class="col-md-7">';
      echo '<label class="form-label" for="message">Message</label>';
      echo '<input class="form-control" type="text" id="message" name="message" placeholder="Informations complémentaires (optionnel)">';
      echo '</div>';
      echo '<div class="col-md-2">';
      echo '<button class="btn btn-orange w-100" type="submit">Proposer</button>';
      echo '</div>';
      echo '</div>';
      echo '</form>';
      echo '</div>';
      echo '</div>';
    }
  }

  // Section Questions / Réponses
  echo '<hr class="section-divider">';
  echo '<h2 class="h4 mb-3">Questions et messages</h2>';
  if($stmt = $mysqli->prepare("SELECT q.id, q.contenu, q.type, q.created_at, c.prenom, c.nom FROM qa q JOIN compte c ON c.id=q.auteur_id WHERE q.annonce_id=? ORDER BY q.created_at ASC")){
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Aucun message pour le moment.</div>';
    } else {
      echo '<div class="p-3 rounded bg-orange-100">';
      echo '<ul class="list-group mb-3">';
      while($m = $res->fetch_assoc()){
        $badge = $m['type']==='question' ? 'bg-warning text-dark' : 'bg-success';
        echo '<li class="list-group-item">';
        echo '<div class="d-flex justify-content-between">';
        echo '<div><span class="badge '.$badge.' me-2">'.htmlspecialchars($m['type']).'</span><strong>'.htmlspecialchars($m['prenom'].' '.$m['nom']).'</strong></div>';
        echo '<small class="text-muted">'.htmlspecialchars($m['created_at']).'</small>';
        echo '</div>';
        echo '<div class="mt-2">'.nl2br(htmlspecialchars($m['contenu'])).'</div>';
        echo '</li>';
      }
      echo '</ul>';
      echo '</div>';
    }
    $stmt->close();
  }

  // Formulaire de message (client et déménageur)
  if(isset($_SESSION['user']) && in_array((int)$_SESSION['user']['role'], [1,2], true)){
    echo '<div class="card my-3">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">Envoyer un message</h5>';
    echo '<form method="POST" action="tt_qa_post.php">';
    echo '<input type="hidden" name="annonce_id" value="'.(int)$annonce['id'].'">';
    echo '<div class="mb-3">';
    echo '<label class="form-label" for="contenu">Votre message</label>';
    echo '<textarea class="form-control" id="contenu" name="contenu" rows="3" required></textarea>';
    echo '</div>';
    echo '<button class="btn btn-outline-orange" type="submit">Envoyer</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
  }

  include('footer.inc.php');
?>



<?php
  session_start();
  $titre = "Accueil";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php')
?>
  <h1 class="mb-4">Plateforme de déménagement</h1>
  <p class="lead">Trouvez des déménageurs ou proposez vos services.</p>
  <?php if(!isset($_SESSION['user'])) { ?>
    <p>
      <a class="btn btn-orange btn-sm" href="inscription.php">Créer un compte</a>
      <a class="btn btn-outline-orange btn-sm" href="connexion.php">Se connecter</a>
    </p>
  <?php } ?>

  <hr class="my-4">

  <h2 class="h4 mb-3">Annonces récentes</h2>
  <?php
    require_once('param.inc.php');
    $mysqli = @new mysqli($host, $login, $passwd, $dbname);
    if(!$mysqli->connect_error) {
      $sql = "SELECT a.id, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut FROM annonce a WHERE a.statut='publie' ORDER BY a.created_at DESC LIMIT 6";
      if ($res = $mysqli->query($sql)) {
        if ($res->num_rows === 0) {
          echo '<div class="alert alert-info">Aucune annonce pour le moment.</div>';
        } else {
          echo '<div class="row">';
          while($row = $res->fetch_assoc()) {
            echo '<div class="col-md-4 mb-3">';
            echo '<div class="card h-100">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">'.htmlspecialchars($row['titre']).'</h5>';
            echo '<p class="card-text mb-1"><strong>Départ:</strong> '.htmlspecialchars($row['ville_depart']).'</p>';
            echo '<p class="card-text mb-1"><strong>Arrivée:</strong> '.htmlspecialchars($row['ville_arrivee']).'</p>';
            echo '<p class="card-text"><strong>Date:</strong> '.htmlspecialchars($row['date_debut']).'</p>';
            echo '<a class="btn btn-outline-primary btn-sm" href="annonce_detail.php?id='.$row['id'].'">Détails</a>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
          }
          echo '</div>';
        }
        $res->free();
      }
    }
  ?>
<?php
  include('footer.inc.php');
?>
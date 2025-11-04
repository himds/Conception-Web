<?php
session_start();
$titre = "Accueil";
include('header.inc.php');
include('menu.inc.php');
include('message.inc.php');
?>

<div class="container my-4">

  <div class="row g-4">

    <!-- 🧊 Block 1 -->
    <div class="col-12 col-md-6">
      <div class="p-3 bg-light border rounded h-100">
        <h1 class="mb-4">Plateforme de déménagement</h1>
        <p class="lead">Trouvez des déménageurs ou proposez vos services.</p>
      </div>
    </div>

    <!-- 🧊 Block 2 -->
    <div class="col-12 col-md-6">
      <div class="p-3 bg-light border rounded h-100">
        <h2 class="h4 mb-3">Annonces récentes</h2>
        <p>Déposez une annonce ou trouvez un déménageur facilement.</p>
        <a href="annonces.php" class="btn btn-primary btn-sm">Voir les annonces</a>
      </div>
    </div>

    <!-- 🧊 Block 3 (Full width bottom) -->
    <div class="col-12">
      <div class="p-3 bg-light border rounded">

        <h2 class="h4 mb-3"> ......... </h2>

        <?php
          require_once('param.inc.php');
          $mysqli = @new mysqli($host, $login, $passwd, $dbname);

          if(!$mysqli->connect_error) {
            $sql = "SELECT a.id, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut 
                    FROM annonce a 
                    WHERE a.statut='publie' 
                    ORDER BY a.created_at DESC LIMIT 6";

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
                  echo '</div></div></div>';
                }

                echo '</div>';
              }
              $res->free();
            }
          }
        ?>

      </div>
    </div>

  </div>
</div>

<?php include('footer.inc.php'); ?>

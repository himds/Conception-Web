<?php
  session_start();
  $titre = "Annonces";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $mysqli = @new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    echo '<div class="alert alert-danger">Problème de BDD.</div>';
    include('footer.inc.php');
    exit;
  }

  $sql = "SELECT id, titre, ville_depart, ville_arrivee, date_debut FROM annonce WHERE statut='publie' ORDER BY created_at DESC";
  $res = $mysqli->query($sql);
?>
  <h1>Liste des annonces</h1>
  <?php if($res && $res->num_rows>0) { ?>
    <div class="table-responsive">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Titre</th>
            <th>Départ</th>
            <th>Arrivée</th>
            <th>Date</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $res->fetch_assoc()) { ?>
          <tr>
            <td><?php echo htmlspecialchars($row['titre']); ?></td>
            <td><?php echo htmlspecialchars($row['ville_depart']); ?></td>
            <td><?php echo htmlspecialchars($row['ville_arrivee']); ?></td>
            <td><?php echo htmlspecialchars($row['date_debut']); ?></td>
            <td><a class="btn btn-sm btn-outline-orange" href="annonce_detail.php?id=<?php echo $row['id']; ?>">Détails</a></td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } else { ?>
    <div class="alert alert-info">Aucune annonce.</div>
  <?php } ?>
<?php include('footer.inc.php'); ?>



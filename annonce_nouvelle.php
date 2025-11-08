<?php
  session_start();
  if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: connexion.php');
    exit;
  }
  $titre = "Nouvelle annonce";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
?>
<div class="index-page-background">
<div class="container-fluid my-4">
    <h1>Créer une annonce de déménagement</h1>
    <form method="POST" action="tt_annonce_create.php" enctype="multipart/form-data">
    <div class="row my-3">
      <div class="col-md-6">
        <label class="form-label" for="titre">Titre</label>
        <input class="form-control" type="text" id="titre" name="titre" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="date_debut">Date et heure de début</label>
        <input class="form-control" type="datetime-local" id="date_debut" name="date_debut" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="description">Description rapide</label>
      <textarea class="form-control" id="description" name="description" rows="3"></textarea>
    </div>

    <div class="row my-3">
      <div class="col-md-6">
        <label class="form-label" for="ville_depart">Ville de départ</label>
        <input class="form-control" type="text" id="ville_depart" name="ville_depart" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="ville_arrivee">Ville d'arrivée</label>
        <input class="form-control" type="text" id="ville_arrivee" name="ville_arrivee" required>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-md-6">
        <label class="form-label">Départ</label>
        <div class="row g-2">
          <div class="col-6">
            <select class="form-select" name="depart_type" required>
              <option value="maison">Maison</option>
              <option value="appartement">Appartement</option>
            </select>
          </div>
          <div class="col-3">
            <input class="form-control" type="number" name="depart_etage" placeholder="Étage">
          </div>
          <div class="col-3 d-flex align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="depart_ascenseur" name="depart_ascenseur">
              <label class="form-check-label" for="depart_ascenseur">Ascenseur</label>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <label class="form-label">Arrivée</label>
        <div class="row g-2">
          <div class="col-6">
            <select class="form-select" name="arrivee_type" required>
              <option value="maison">Maison</option>
              <option value="appartement">Appartement</option>
            </select>
          </div>
          <div class="col-3">
            <input class="form-control" type="number" name="arrivee_etage" placeholder="Étage">
          </div>
          <div class="col-3 d-flex align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="arrivee_ascenseur" name="arrivee_ascenseur">
              <label class="form-check-label" for="arrivee_ascenseur">Ascenseur</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-md-4">
        <label class="form-label" for="volume_m3">Volume total (m³)</label>
        <input class="form-control" type="number" step="0.01" min="0" id="volume_m3" name="volume_m3">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="poids_kg">Poids total (kg)</label>
        <input class="form-control" type="number" min="0" id="poids_kg" name="poids_kg">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="nb_demenageurs">Nombre de déménageurs requis</label>
        <input class="form-control" type="number" min="1" id="nb_demenageurs" name="nb_demenageurs" required>
        <small class="form-text text-muted">Sélectionnez les déménageurs ci-dessous selon ce nombre</small>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label">Sélectionner les déménageurs (selon le nombre requis)</label>
      <div class="border rounded p-3" style="max-height: 300px; overflow-y: auto;">
        <?php
        require_once('param.inc.php');
        $mysqli = new mysqli($host, $login, $passwd, $dbname);
        if(!$mysqli->connect_error){
          if($stmt = $mysqli->prepare("SELECT id, nom, prenom, email FROM compte WHERE role=2 ORDER BY nom, prenom")){
            $stmt->execute();
            $res = $stmt->get_result();
            if($res->num_rows === 0){
              echo '<div class="alert alert-info">Aucun déménageur disponible.</div>';
            } else {
              while($row = $res->fetch_assoc()){
                echo '<div class="form-check mb-2">';
                echo '<input class="form-check-input" type="checkbox" name="demenageurs[]" value="'.(int)$row['id'].'" id="demenageur_'.(int)$row['id'].'">';
                echo '<label class="form-check-label" for="demenageur_'.(int)$row['id'].'">';
                echo htmlspecialchars($row['prenom'].' '.$row['nom']).' ('.htmlspecialchars($row['email']).')';
                echo '</label>';
                echo '</div>';
              }
            }
            $stmt->close();
          }
          $mysqli->close();
        }
        ?>
      </div>
      <small class="form-text text-muted">Cochez les déménageurs que vous souhaitez inviter pour ce déménagement</small>
    </div>

    <div class="mb-3">
      <label class="form-label" for="images">Images</label>
      <input class="form-control" type="file" id="images" name="images[]" multiple>
    </div>

    <div class="my-3">
      <button class="btn btn-primary" type="submit">Publier l'annonce</button>
    </div>
    </form>
</div>
</div>
<?php include('footer.inc.php'); ?>



<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 3) {
    header('Location: connexion.php');
    exit;
  }
  
  $titre = "Modifier annonce";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if($id <= 0){
    $_SESSION['erreur'] = "ID d'annonce invalide";
    header('Location: admin.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: admin.php');
    exit;
  }

  // 查询公告（管理员可以查看所有状态）
  $annonce = null;
  if($stmt = $mysqli->prepare("SELECT * FROM annonce WHERE id=?")){
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $annonce = $res->fetch_assoc();
    $stmt->close();
  }

  if(!$annonce){
    $_SESSION['erreur'] = "Annonce introuvable";
    header('Location: admin.php');
    exit;
  }

  // 格式化日期时间
  $date_debut_formatted = date('Y-m-d\TH:i', strtotime($annonce['date_debut']));
?>
<div class="index-page-background">
<div class="container-fluid my-4">
    <h1>Modifier l'annonce</h1>
    <p class="text-muted">Annonce ID: <?= htmlspecialchars($annonce['id']) ?> | Statut: <span class="badge bg-<?= $annonce['statut']==='publie'?'success':($annonce['statut']==='cloture'?'secondary':'warning') ?>"><?= htmlspecialchars($annonce['statut']) ?></span></p>
    
    <form method="POST" action="tt_admin_annonce_update.php">
    <input type="hidden" name="annonce_id" value="<?= (int)$annonce['id'] ?>">
    
    <div class="row my-3">
      <div class="col-md-6">
        <label class="form-label" for="titre">Titre</label>
        <input class="form-control" type="text" id="titre" name="titre" value="<?= htmlspecialchars($annonce['titre']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="date_debut">Date et heure de début</label>
        <input class="form-control" type="datetime-local" id="date_debut" name="date_debut" value="<?= htmlspecialchars($date_debut_formatted) ?>" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="description">Description rapide</label>
      <textarea class="form-control" id="description" name="description" rows="3"><?= htmlspecialchars($annonce['description'] ?? '') ?></textarea>
    </div>

    <div class="row my-3">
      <div class="col-md-6">
        <label class="form-label" for="ville_depart">Ville de départ</label>
        <input class="form-control" type="text" id="ville_depart" name="ville_depart" value="<?= htmlspecialchars($annonce['ville_depart']) ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label" for="ville_arrivee">Ville d'arrivée</label>
        <input class="form-control" type="text" id="ville_arrivee" name="ville_arrivee" value="<?= htmlspecialchars($annonce['ville_arrivee']) ?>" required>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-md-6">
        <label class="form-label">Départ</label>
        <div class="row g-2">
          <div class="col-6">
            <select class="form-select" name="depart_type" required>
              <option value="maison" <?= $annonce['depart_type']==='maison'?'selected':'' ?>>Maison</option>
              <option value="appartement" <?= $annonce['depart_type']==='appartement'?'selected':'' ?>>Appartement</option>
            </select>
          </div>
          <div class="col-3">
            <input class="form-control" type="number" name="depart_etage" placeholder="Étage" value="<?= $annonce['depart_etage'] ? htmlspecialchars($annonce['depart_etage']) : '' ?>">
          </div>
          <div class="col-3 d-flex align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="depart_ascenseur" name="depart_ascenseur" <?= $annonce['depart_ascenseur']==1?'checked':'' ?>>
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
              <option value="maison" <?= $annonce['arrivee_type']==='maison'?'selected':'' ?>>Maison</option>
              <option value="appartement" <?= $annonce['arrivee_type']==='appartement'?'selected':'' ?>>Appartement</option>
            </select>
          </div>
          <div class="col-3">
            <input class="form-control" type="number" name="arrivee_etage" placeholder="Étage" value="<?= $annonce['arrivee_etage'] ? htmlspecialchars($annonce['arrivee_etage']) : '' ?>">
          </div>
          <div class="col-3 d-flex align-items-center">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" value="1" id="arrivee_ascenseur" name="arrivee_ascenseur" <?= $annonce['arrivee_ascenseur']==1?'checked':'' ?>>
              <label class="form-check-label" for="arrivee_ascenseur">Ascenseur</label>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row my-3">
      <div class="col-md-4">
        <label class="form-label" for="volume_m3">Volume total (m³)</label>
        <input class="form-control" type="number" step="0.01" min="0" id="volume_m3" name="volume_m3" value="<?= $annonce['volume_m3'] ? htmlspecialchars($annonce['volume_m3']) : '' ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="poids_kg">Poids total (kg)</label>
        <input class="form-control" type="number" min="0" id="poids_kg" name="poids_kg" value="<?= $annonce['poids_kg'] ? htmlspecialchars($annonce['poids_kg']) : '' ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label" for="nb_demenageurs">Nombre de déménageurs requis</label>
        <input class="form-control" type="number" min="1" id="nb_demenageurs" name="nb_demenageurs" value="<?= htmlspecialchars($annonce['nb_demenageurs'] ?? '') ?>" required>
      </div>
    </div>

    <div class="mb-3">
      <label class="form-label" for="statut">Statut</label>
      <select class="form-select" id="statut" name="statut" required>
        <option value="brouillon" <?= $annonce['statut']==='brouillon'?'selected':'' ?>>Brouillon</option>
        <option value="publie" <?= $annonce['statut']==='publie'?'selected':'' ?>>Publié</option>
        <option value="cloture" <?= $annonce['statut']==='cloture'?'selected':'' ?>>Clôturé</option>
      </select>
    </div>

    <div class="my-3">
      <button class="btn btn-primary" type="submit">Enregistrer les modifications</button>
      <a href="admin.php" class="btn btn-outline-secondary">Annuler</a>
    </div>
    </form>
</div>
</div>
<?php
  $mysqli->close();
  include('footer.inc.php');
?>


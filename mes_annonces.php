<?php
  session_start();
  // 只有客户角色（role=1）才能访问此页面
  // Seul le rôle client (role=1) peut accéder à cette page
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 1) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Mes annonces";
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

  $sql = "SELECT id, titre, ville_depart, ville_arrivee, date_debut, statut, created_at FROM annonce WHERE client_id=? ORDER BY created_at DESC";
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $_SESSION['user']['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    ?>
<div class="index-page-background">
<div class="container-fluid my-4">
    <h1>Mes annonces</h1>
    <?php
    if($res->num_rows === 0){
      echo '<div class="alert alert-info">Vous n\'avez pas encore créé d\'annonce.</div>';
      echo '<a href="annonce_nouvelle.php" class="btn btn-orange">Créer une annonce</a>';
    } else {
      echo '<div class="table-responsive">';
      echo '<table class="table table-striped">';
      echo '<thead><tr><th>Titre</th><th>Départ</th><th>Arrivée</th><th>Date</th><th>Statut</th><th>Déménageur accepté</th><th>Actions</th></tr></thead>';
      echo '<tbody>';
      while($row = $res->fetch_assoc()){
        echo '<tr>';
        echo '<td>'.htmlspecialchars($row['titre']).'</td>';
        echo '<td>'.htmlspecialchars($row['ville_depart']).'</td>';
        echo '<td>'.htmlspecialchars($row['ville_arrivee']).'</td>';
        echo '<td>'.htmlspecialchars($row['date_debut']).'</td>';
        echo '<td><span class="badge bg-'.($row['statut']==='publie'?'success':($row['statut']==='cloture'?'secondary':'warning')).'">'.htmlspecialchars($row['statut']).'</span></td>';
        
        // 查询该公告的提名状态和接受的搬家工人
        // Requête du statut de nomination et du déménageur accepté pour cette annonce
        $nominationStatus = 'public';
        $acceptedDemenageur = null;
        $hasAcceptedNomination = false;
        
        // 查询是否有已接受的提名
        // Requête pour vérifier s'il y a une nomination acceptée
        // 注意：如果搬家工人拒绝提名，提名会被删除，所以只有接受的提名会存在
        // Note : si le déménageur refuse la nomination, elle sera supprimée, donc seules les nominations acceptées existent
        $nominationSql = "SELECT n.id, n.etat, n.demenageur_id, c.nom, c.prenom 
                         FROM nomination n 
                         JOIN compte c ON c.id = n.demenageur_id 
                         WHERE n.annonce_id = ? AND n.etat = 'accepte' 
                         LIMIT 1";
        if($nominationStmt = $mysqli->prepare($nominationSql)){
          $nominationStmt->bind_param("i", $row['id']);
          $nominationStmt->execute();
          $nominationRes = $nominationStmt->get_result();
          if($nominationRow = $nominationRes->fetch_assoc()){
            $nominationStatus = 'accepte';
            $hasAcceptedNomination = true;
            $acceptedDemenageur = $nominationRow;
          }
          $nominationStmt->close();
        }
        
        echo '<td>';
        // 显示提名状态 / Afficher le statut de nomination
        // 如果已接受，显示"Accepté"和搬家工人姓名；否则显示"Public"
        // Si acceptée, afficher "Accepté" et le nom du déménageur ; sinon afficher "Public"
        if($nominationStatus === 'accepte' && $acceptedDemenageur){
          echo '<span class="badge bg-success">Accepté</span><br>';
          echo '<small>'.htmlspecialchars($acceptedDemenageur['prenom'].' '.$acceptedDemenageur['nom']).'</small>';
        } else {
          echo '<span class="badge bg-info">Public</span>';
        }
        echo '</td>';
        
        echo '<td>';
        echo '<a class="btn btn-sm btn-outline-primary" href="annonce_detail.php?id='.(int)$row['id'].'">Détails</a> ';
        echo '<a class="btn btn-sm btn-outline-orange" href="annonce_offres.php?id='.(int)$row['id'].'">Voir les offres</a> ';
        // 取消/删除公告按钮 / Bouton pour annuler/supprimer l'annonce
        // 只能取消状态为 publie 或 brouillon 的公告
        // Ne peut annuler que les annonces avec le statut 'publie' ou 'brouillon'
        if($row['statut'] === 'publie' || $row['statut'] === 'brouillon'){
          echo '<button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('.(int)$row['id'].', \''.htmlspecialchars(addslashes($row['titre'])).'\')">Annuler</button>';
        }
        echo '</td>';
        echo '</tr>';
      }
      echo '</tbody></table></div>';
    }
    ?>
</div>
</div>
    <?php
    $stmt->close();
  }
?>

<script>
function confirmDelete(annonceId, titre) {
  if(confirm('Êtes-vous sûr de vouloir annuler l\'annonce "' + titre + '" ? Cette action est irréversible.')) {
    var form = document.createElement('form');
    form.method = 'POST';
    form.action = 'tt_annonce_delete.php';
    
    var input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'annonce_id';
    input.value = annonceId;
    form.appendChild(input);
    
    document.body.appendChild(form);
    form.submit();
  }
}
</script>

<?php
  include('footer.inc.php');
?>


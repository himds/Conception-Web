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

  // 未登录用户可以查看公告列表，但不能操作
  $isLoggedIn = isset($_SESSION['user']);
  
  // 查询公告（只显示已发布的，并且客户账户没有被停用的）
  // 如果 actif 字段不存在，则忽略该条件
  $sql = "SELECT a.id, a.titre, a.ville_depart, a.ville_arrivee, a.date_debut 
          FROM annonce a 
          JOIN compte c ON c.id = a.client_id 
          WHERE a.statut='publie'";
  
  // 检查 actif 字段是否存在
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if($checkActif && $checkActif->num_rows > 0) {
    $sql .= " AND (c.actif IS NULL OR c.actif = 1)";
  }
  
  $sql .= " ORDER BY a.created_at DESC";
  $res = $mysqli->query($sql);
?>
<div class="index-page-background">
<div class="container-fluid my-4">
  <h1>Liste des annonces</h1>
  <?php if(!$isLoggedIn) { ?>
    <div class="alert alert-info">Vous pouvez consulter les annonces publiques. <a href="connexion.php">Connectez-vous</a> pour interagir avec les annonceurs ou proposer vos services.</div>
  <?php } ?>
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
            <td>
              <a class="btn btn-sm btn-outline-orange" href="annonce_detail.php?id=<?php echo $row['id']; ?>">Détails</a>
            </td>
          </tr>
        <?php } ?>
        </tbody>
      </table>
    </div>
  <?php } else { ?>
    <div class="alert alert-info">Aucune annonce.</div>
  <?php } ?>
</div>
</div>
<?php include('footer.inc.php'); ?>



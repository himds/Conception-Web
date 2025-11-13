<?php
  session_start();
  // 管理员权限检查 / Vérification des permissions administrateur
  // 只有管理员角色（role=3）才能访问此页面
  // Seul le rôle administrateur (role=3) peut accéder à cette page
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 3) {
    header('Location: connexion.php');
    exit;
  }

  $titre = "Administration";
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

  // 统计信息
  $stats = [];
  
  // 检查 actif 字段是否存在，如果不存在则创建
  // Vérifier si le champ actif existe, le créer s'il n'existe pas
  // actif字段用于标记账户是否被停用（1=激活，0=停用）
  // Le champ actif sert à marquer si un compte est désactivé (1=actif, 0=désactivé)
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if(!$checkActif || $checkActif->num_rows === 0) {
    // 字段不存在，创建它 / Le champ n'existe pas, le créer
    $mysqli->query("ALTER TABLE compte ADD COLUMN actif TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1=actif, 0=désactivé'");
    $mysqli->query("ALTER TABLE compte ADD INDEX idx_actif (actif)");
  }

  // 统计用户
  $userStatsSql = "SELECT COUNT(*) as total, 
                          SUM(CASE WHEN role=1 THEN 1 ELSE 0 END) as clients, 
                          SUM(CASE WHEN role=2 THEN 1 ELSE 0 END) as demenageurs, 
                          SUM(CASE WHEN role=3 THEN 1 ELSE 0 END) as admins,
                          SUM(CASE WHEN (actif IS NULL OR actif = 1) THEN 1 ELSE 0 END) as actifs,
                          SUM(CASE WHEN actif = 0 THEN 1 ELSE 0 END) as desactives
                   FROM compte";
  if($stmt = $mysqli->prepare($userStatsSql)){
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['users'] = $res->fetch_assoc();
    $stmt->close();
  }

  // 统计公告
  if($stmt = $mysqli->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN statut='publie' THEN 1 ELSE 0 END) as publie, SUM(CASE WHEN statut='cloture' THEN 1 ELSE 0 END) as cloture, SUM(CASE WHEN statut='brouillon' THEN 1 ELSE 0 END) as brouillon FROM annonce")){
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['annonces'] = $res->fetch_assoc();
    $stmt->close();
  }

  // 统计评价
  if($stmt = $mysqli->prepare("SELECT COUNT(*) as total, AVG(note) as moyenne FROM evaluation")){
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['evaluations'] = $res->fetch_assoc();
    $stmt->close();
  }

  // 统计出价
  if($stmt = $mysqli->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN etat='propose' THEN 1 ELSE 0 END) as propose, SUM(CASE WHEN etat='accepte' THEN 1 ELSE 0 END) as accepte, SUM(CASE WHEN etat='refuse' THEN 1 ELSE 0 END) as refuse FROM offre")){
    $stmt->execute();
    $res = $stmt->get_result();
    $stats['offres'] = $res->fetch_assoc();
    $stmt->close();
  }
?>

<div class="index-page-background">
<div class="container-fluid my-4">
  <h1 class="mb-4">Tableau de bord administrateur</h1>

  <!-- 统计卡片 -->
  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="card bg-primary text-white">
        <div class="card-body">
          <h5 class="card-title">Utilisateurs</h5>
          <h3><?= htmlspecialchars($stats['users']['total'] ?? 0) ?></h3>
          <small>Clients: <?= htmlspecialchars($stats['users']['clients'] ?? 0) ?> | Déménageurs: <?= htmlspecialchars($stats['users']['demenageurs'] ?? 0) ?> | Admins: <?= htmlspecialchars($stats['users']['admins'] ?? 0) ?></small><br>
          <small>Actifs: <?= htmlspecialchars($stats['users']['actifs'] ?? 0) ?> | Désactivés: <?= htmlspecialchars($stats['users']['desactives'] ?? 0) ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-success text-white">
        <div class="card-body">
          <h5 class="card-title">Annonces</h5>
          <h3><?= htmlspecialchars($stats['annonces']['total'] ?? 0) ?></h3>
          <small>Publiées: <?= htmlspecialchars($stats['annonces']['publie'] ?? 0) ?> | Clôturées: <?= htmlspecialchars($stats['annonces']['cloture'] ?? 0) ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-info text-white">
        <div class="card-body">
          <h5 class="card-title">Offres</h5>
          <h3><?= htmlspecialchars($stats['offres']['total'] ?? 0) ?></h3>
          <small>Proposées: <?= htmlspecialchars($stats['offres']['propose'] ?? 0) ?> | Acceptées: <?= htmlspecialchars($stats['offres']['accepte'] ?? 0) ?></small>
        </div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="card bg-warning text-white">
        <div class="card-body">
          <h5 class="card-title">Évaluations</h5>
          <h3><?= htmlspecialchars($stats['evaluations']['total'] ?? 0) ?></h3>
          <small>Note moyenne: <?= $stats['evaluations']['moyenne'] ? number_format((float)$stats['evaluations']['moyenne'], 2) : 'N/A' ?>/5</small>
        </div>
      </div>
    </div>
  </div>

  <!-- 用户列表 -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Gestion des utilisateurs</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Nom</th>
              <th>Prénom</th>
              <th>Email</th>
              <th>Rôle / État</th>
              <th>Date d'inscription</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // 查询用户，包括 actif 状态（如果字段存在）
            $userSql = "SELECT id, nom, prenom, email, role, created_at";
            $checkActifField = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
            if($checkActifField && $checkActifField->num_rows > 0) {
              $userSql .= ", actif";
            }
            $userSql .= " FROM compte ORDER BY created_at DESC";
            
            if($stmt = $mysqli->prepare($userSql)){
              $stmt->execute();
              $res = $stmt->get_result();
              while($row = $res->fetch_assoc()){
                $roleNames = [0 => 'Non activé', 1 => 'Client', 2 => 'Déménageur', 3 => 'Administrateur'];
                $isActif = !isset($row['actif']) || $row['actif'] == 1;
                echo '<tr>';
                echo '<td>'.htmlspecialchars($row['id']).'</td>';
                echo '<td>'.htmlspecialchars($row['nom']).'</td>';
                echo '<td>'.htmlspecialchars($row['prenom']).'</td>';
                echo '<td>'.htmlspecialchars($row['email']).'</td>';
                echo '<td>';
                echo '<span class="badge bg-'.($row['role']==3?'danger':($row['role']==2?'info':($row['role']==1?'success':'secondary'))).'">'.htmlspecialchars($roleNames[$row['role']] ?? 'Inconnu').'</span>';
                // 显示账户状态 / Afficher le statut du compte
                if(isset($row['actif'])){
                  echo ' <span class="badge bg-'.($row['actif']==1?'success':'danger').'">'.($row['actif']==1?'Actif':'Désactivé').'</span>';
                }
                echo '</td>';
                echo '<td>'.htmlspecialchars($row['created_at']).'</td>';
                echo '<td>';
                // 不能停用管理员账户和自己 / Ne peut pas désactiver les comptes administrateur et son propre compte
                // 安全措施：防止管理员停用其他管理员或自己
                // Mesure de sécurité : empêcher l'administrateur de désactiver d'autres administrateurs ou lui-même
                if((int)$row['role'] !== 3 && (int)$row['id'] !== (int)$_SESSION['user']['id']){
                  if(isset($row['actif'])){
                    if($row['actif'] == 1){
                      // 账户激活，显示停用按钮 / Compte actif, afficher le bouton désactiver
                      echo '<form class="d-inline" method="POST" action="tt_admin_user_disable.php">';
                      echo '<input type="hidden" name="user_id" value="'.(int)$row['id'].'">';
                      echo '<input type="hidden" name="action" value="desactiver">';
                      echo '<button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm(\'Êtes-vous sûr de vouloir désactiver ce compte ?\')">Désactiver</button>';
                      echo '</form>';
                    } else {
                      // 账户已停用，显示激活按钮 / Compte désactivé, afficher le bouton activer
                      echo '<form class="d-inline" method="POST" action="tt_admin_user_disable.php">';
                      echo '<input type="hidden" name="user_id" value="'.(int)$row['id'].'">';
                      echo '<input type="hidden" name="action" value="activer">';
                      echo '<button class="btn btn-sm btn-outline-success" type="submit">Activer</button>';
                      echo '</form>';
                    }
                  } else {
                    // 如果字段不存在，显示创建按钮的提示
                    // Si le champ n'existe pas, afficher un message
                    echo '<span class="text-muted">N/A</span>';
                  }
                } else {
                  // 管理员账户或自己的账户，不显示操作按钮
                  // Compte administrateur ou son propre compte, ne pas afficher de bouton d'action
                  echo '<span class="text-muted">-</span>';
                }
                echo '</td>';
                echo '</tr>';
              }
              $stmt->close();
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- 公告列表 -->
  <div class="card mb-4">
    <div class="card-header">
      <h5 class="mb-0">Gestion des annonces</h5>
    </div>
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped">
          <thead>
            <tr>
              <th>ID</th>
              <th>Titre</th>
              <th>Client</th>
              <th>Départ → Arrivée</th>
              <th>Statut</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
            // 显示所有公告，不限数量 / Afficher toutes les annonces, sans limite
            // 管理员可以查看和管理所有公告
            // L'administrateur peut voir et gérer toutes les annonces
            if($stmt = $mysqli->prepare("SELECT a.id, a.titre, a.ville_depart, a.ville_arrivee, a.statut, a.created_at, c.prenom, c.nom FROM annonce a JOIN compte c ON c.id=a.client_id ORDER BY a.created_at DESC")){
              $stmt->execute();
              $res = $stmt->get_result();
              while($row = $res->fetch_assoc()){
                echo '<tr>';
                echo '<td>'.htmlspecialchars($row['id']).'</td>';
                echo '<td>'.htmlspecialchars($row['titre']).'</td>';
                echo '<td>'.htmlspecialchars($row['prenom'].' '.$row['nom']).'</td>';
                echo '<td>'.htmlspecialchars($row['ville_depart'].' → '.$row['ville_arrivee']).'</td>';
                echo '<td><span class="badge bg-'.($row['statut']==='publie'?'success':($row['statut']==='cloture'?'secondary':'warning')).'">'.htmlspecialchars($row['statut']).'</span></td>';
                echo '<td>'.htmlspecialchars($row['created_at']).'</td>';
                echo '<td>';
                echo '<a class="btn btn-sm btn-outline-primary" href="annonce_detail.php?id='.(int)$row['id'].'">Voir</a> ';
                // 修改状态 / Modifier le statut
                // 只能关闭已发布的公告
                // Ne peut fermer que les annonces publiées
                if($row['statut'] === 'publie'){
                  echo '<form class="d-inline" method="POST" action="tt_admin_annonce_manage.php">';
                  echo '<input type="hidden" name="annonce_id" value="'.(int)$row['id'].'">';
                  echo '<input type="hidden" name="action" value="cloture">';
                  echo '<button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm(\'Êtes-vous sûr de vouloir clôturer cette annonce ?\')">Clôturer</button>';
                  echo '</form> ';
                }
                // 删除公告 / Supprimer l'annonce
                // 管理员可以删除任何公告（不可逆操作）
                // L'administrateur peut supprimer n'importe quelle annonce (action irréversible)
                echo '<form class="d-inline" method="POST" action="tt_admin_annonce_manage.php">';
                echo '<input type="hidden" name="annonce_id" value="'.(int)$row['id'].'">';
                echo '<input type="hidden" name="action" value="supprimer">';
                echo '<button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer cette annonce ? Cette action est irréversible.\')">Supprimer</button>';
                echo '</form>';
                echo '</td>';
                echo '</tr>';
              }
              $stmt->close();
            }
            ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
</div>

<?php
  $mysqli->close();
  include('footer.inc.php');
?>




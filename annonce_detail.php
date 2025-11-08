<?php
  session_start();
  // 未登录用户也可以查看详情，但不能操作
  
  $titre = "Détail annonce";
  include('header.inc.php');
  include('menu.inc.php');
  include('message.inc.php');
  require_once('param.inc.php');
  
  $isLoggedIn = isset($_SESSION['user']);

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

  // 查询公告
  // 如果是公告的创建者，可以查看所有状态的公告
  // 否则，只能查看已发布的、客户账户未被停用的公告
  $annonce = null;
  $sql = "SELECT a.*, c.prenom, c.nom FROM annonce a JOIN compte c ON c.id=a.client_id WHERE a.id=?";
  
  // 如果不是公告创建者，添加状态和账户检查
  if(!$isLoggedIn || !isset($_SESSION['user']['id'])) {
    $sql .= " AND a.statut='publie'";
    // 检查 actif 字段是否存在
    $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
    if($checkActif && $checkActif->num_rows > 0) {
      $sql .= " AND (c.actif IS NULL OR c.actif = 1)";
    }
  } else {
    // 已登录用户：如果是公告创建者，可以查看所有状态；否则只能查看已发布的
    // 我们需要先检查是否是创建者
    $userId = (int)$_SESSION['user']['id'];
    $sqlCheckOwner = "SELECT client_id FROM annonce WHERE id=?";
    $isOwner = false;
    if($stmtCheck = $mysqli->prepare($sqlCheckOwner)){
      $stmtCheck->bind_param("i", $id);
      $stmtCheck->execute();
      $resCheck = $stmtCheck->get_result();
      if($rowCheck = $resCheck->fetch_assoc()){
        $isOwner = (int)$rowCheck['client_id'] === $userId;
      }
      $stmtCheck->close();
    }
    
    if(!$isOwner) {
      $sql .= " AND a.statut='publie'";
      // 检查 actif 字段是否存在
      $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
      if($checkActif && $checkActif->num_rows > 0) {
        $sql .= " AND (c.actif IS NULL OR c.actif = 1)";
      }
    }
  }
  
  if($stmt = $mysqli->prepare($sql)){
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $annonce = $res->fetch_assoc();
    $stmt->close();
  }

  if(!$annonce){
    echo '<div class="index-page-background">';
    echo '<div class="container-fluid my-4">';
    echo '<div class="alert alert-danger">Annonce introuvable ou non disponible.</div>';
    echo '<a href="annonces.php" class="btn btn-outline-secondary">← Retour aux annonces</a>';
    echo '</div>';
    echo '</div>';
    include('footer.inc.php');
    exit;
  }

  echo '<div class="index-page-background">';
  echo '<div class="container-fluid my-4">';
  // 返回按钮
  echo '<div class="mb-3">';
  if($isLoggedIn && (int)$_SESSION['user']['role'] === 1){
    echo '<a href="mes_annonces.php" class="btn btn-outline-secondary">← Retour à mes annonces</a>';
  } else {
    echo '<a href="annonces.php" class="btn btn-outline-secondary">← Retour aux annonces</a>';
  }
  echo '</div>';
  
  // 提示未登录用户
  if(!$isLoggedIn) {
    echo '<div class="alert alert-info mb-3">';
    echo 'Vous consultez cette annonce en tant qu\'invité. <a href="connexion.php">Connectez-vous</a> pour interagir avec l\'annonceur ou proposer vos services.';
    echo '</div>';
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

  // Formulaire de message (只有登录的用户才能发送消息，但不能是停用的账户)
  if($isLoggedIn && in_array((int)$_SESSION['user']['role'], [1,2], true)){
    // 检查账户是否被停用
    $userActif = true;
    $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
    if($checkActif && $checkActif->num_rows > 0) {
      if($stmtCheck = $mysqli->prepare("SELECT actif FROM compte WHERE id=?")){
        $stmtCheck->bind_param("i", $_SESSION['user']['id']);
        $stmtCheck->execute();
        $resCheck = $stmtCheck->get_result();
        if($userRow = $resCheck->fetch_assoc()){
          $userActif = !isset($userRow['actif']) || $userRow['actif'] == 1;
        }
        $stmtCheck->close();
      }
    }
    
    if($userActif){
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
  } else if(!$isLoggedIn) {
    // 未登录用户提示
    echo '<div class="card my-3">';
    echo '<div class="card-body">';
    echo '<h5 class="card-title">Envoyer un message</h5>';
    echo '<div class="alert alert-warning">';
    echo 'Vous devez être <a href="connexion.php">connecté</a> pour envoyer un message.';
    echo '</div>';
    echo '</div>';
    echo '</div>';
  }

  echo '</div>';
  echo '</div>';
  include('footer.inc.php');
?>



<?php
  session_start();
  if(!isset($_SESSION['user']) || $_SESSION['user']['role'] != 1) {
    header('Location: connexion.php');
    exit;
  }
  require_once('param.inc.php');

  $clientId = (int)$_SESSION['user']['id'];

  $titre = trim($_POST['titre'] ?? '');
  $description = trim($_POST['description'] ?? '');
  $date_debut = $_POST['date_debut'] ?? '';
  $ville_depart = trim($_POST['ville_depart'] ?? '');
  $ville_arrivee = trim($_POST['ville_arrivee'] ?? '');
  $depart_type = $_POST['depart_type'] ?? 'maison';
  $depart_etage = $_POST['depart_etage'] !== '' ? (int)$_POST['depart_etage'] : NULL;
  $depart_ascenseur = isset($_POST['depart_ascenseur']) ? 1 : 0;
  $arrivee_type = $_POST['arrivee_type'] ?? 'maison';
  $arrivee_etage = $_POST['arrivee_etage'] !== '' ? (int)$_POST['arrivee_etage'] : NULL;
  $arrivee_ascenseur = isset($_POST['arrivee_ascenseur']) ? 1 : 0;
  $volume_m3 = $_POST['volume_m3'] !== '' ? (float)$_POST['volume_m3'] : NULL;
  $poids_kg = $_POST['poids_kg'] !== '' ? (int)$_POST['poids_kg'] : NULL;
  $nb_demenageurs = $_POST['nb_demenageurs'] !== '' ? (int)$_POST['nb_demenageurs'] : NULL;

  if($titre === '' || $date_debut === '' || $ville_depart === '' || $ville_arrivee === '') {
    $_SESSION['erreur'] = "Champs obligatoires manquants";
    header('Location: annonce_nouvelle.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: annonce_nouvelle.php');
    exit;
  }

  // 检查账户是否被停用
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  if($checkActif && $checkActif->num_rows > 0) {
    if($stmtCheck = $mysqli->prepare("SELECT actif FROM compte WHERE id=?")){
      $stmtCheck->bind_param("i", $clientId);
      $stmtCheck->execute();
      $resCheck = $stmtCheck->get_result();
      if($userRow = $resCheck->fetch_assoc()){
        if(isset($userRow['actif']) && $userRow['actif'] == 0){
          $_SESSION['erreur'] = "Votre compte a été désactivé. Vous ne pouvez pas créer d'annonce.";
          header('Location: mes_annonces.php');
          exit;
        }
      }
      $stmtCheck->close();
    }
  }

  $sql = "INSERT INTO annonce (client_id, titre, description, date_debut, ville_depart, ville_arrivee, depart_type, depart_etage, depart_ascenseur, arrivee_type, arrivee_etage, arrivee_ascenseur, volume_m3, poids_kg, nb_demenageurs) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
  if($stmt = $mysqli->prepare($sql)){
    // MySQLi bind_param 处理 NULL 值：对于可能为 NULL 的参数，使用变量引用
    // 所有参数都需要通过引用传递
    $depart_etage_ref = $depart_etage;
    $arrivee_etage_ref = $arrivee_etage;
    $volume_m3_ref = $volume_m3;
    $poids_kg_ref = $poids_kg;
    $nb_demenageurs_ref = $nb_demenageurs;
    
    // 使用 call_user_func_array 来动态绑定参数
    $types = "issssss";
    $params = [&$clientId, &$titre, &$description, &$date_debut, &$ville_depart, &$ville_arrivee, &$depart_type];
    
    // depart_etage: 如果是 NULL，使用 's' 类型，否则使用 'i'
    if($depart_etage === NULL) {
      $types .= "s";
      $depart_etage_null = null;
      $params[] = &$depart_etage_null;
    } else {
      $types .= "i";
      $params[] = &$depart_etage_ref;
    }
    
    $types .= "is";
    $params[] = &$depart_ascenseur;
    $params[] = &$arrivee_type;
    
    // arrivee_etage: 如果是 NULL，使用 's' 类型，否则使用 'i'
    if($arrivee_etage === NULL) {
      $types .= "s";
      $arrivee_etage_null = null;
      $params[] = &$arrivee_etage_null;
    } else {
      $types .= "i";
      $params[] = &$arrivee_etage_ref;
    }
    
    $types .= "i";
    $params[] = &$arrivee_ascenseur;
    
    // volume_m3: 如果是 NULL，使用 's' 类型，否则使用 'd'
    if($volume_m3 === NULL) {
      $types .= "s";
      $volume_m3_null = null;
      $params[] = &$volume_m3_null;
    } else {
      $types .= "d";
      $params[] = &$volume_m3_ref;
    }
    
    // poids_kg: 如果是 NULL，使用 's' 类型，否则使用 'i'
    if($poids_kg === NULL) {
      $types .= "s";
      $poids_kg_null = null;
      $params[] = &$poids_kg_null;
    } else {
      $types .= "i";
      $params[] = &$poids_kg_ref;
    }
    
    // nb_demenageurs: 如果是 NULL，使用 's' 类型，否则使用 'i'
    if($nb_demenageurs === NULL) {
      $types .= "s";
      $nb_demenageurs_null = null;
      $params[] = &$nb_demenageurs_null;
    } else {
      $types .= "i";
      $params[] = &$nb_demenageurs_ref;
    }
    
    // 调用 bind_param（第一个参数是类型字符串，然后是所有参数）
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    
    if($stmt->execute()){
      $annonceId = $stmt->insert_id;

      // 上传图片（可选）
      if(isset($_FILES['images']) && is_array($_FILES['images']['name'])){
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . 'uploads';
        if(!is_dir($uploadDir)){
          @mkdir($uploadDir, 0777, true);
        }
        for($i=0; $i<count($_FILES['images']['name']); $i++){
          if($_FILES['images']['error'][$i] === UPLOAD_ERR_OK){
            $tmp = $_FILES['images']['tmp_name'][$i];
            $name = basename($_FILES['images']['name'][$i]);
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            $safeName = 'annonce_'.$annonceId.'_'.time().'_'.$i.'.'.strtolower($ext);
            $dest = $uploadDir . DIRECTORY_SEPARATOR . $safeName;
            if(@move_uploaded_file($tmp, $dest)){
              $relPath = 'uploads/'.$safeName;
              if($stmtImg = $mysqli->prepare("INSERT INTO annonce_image(annonce_id, path) VALUES (?,?)")){
                $stmtImg->bind_param("is", $annonceId, $relPath);
                $stmtImg->execute();
                $stmtImg->close();
              }
            }
          }
        }
      }

      // 创建指名（如果选择了搬家工人）
      if(isset($_POST['demenageurs']) && is_array($_POST['demenageurs']) && count($_POST['demenageurs']) > 0){
        $demenageurs = array_map('intval', $_POST['demenageurs']);
        $demenageurs = array_filter($demenageurs, function($id) { return $id > 0; });
        
        if(count($demenageurs) > 0){
          if($stmtNom = $mysqli->prepare("INSERT INTO nomination(annonce_id, demenageur_id, etat) VALUES (?,?, 'en_attente')")){
            foreach($demenageurs as $demenageurId){
              $stmtNom->bind_param("ii", $annonceId, $demenageurId);
              $stmtNom->execute();
            }
            $stmtNom->close();
          }
        }
      }

      $_SESSION['message'] = "Annonce publiée" . (isset($_POST['demenageurs']) && count($_POST['demenageurs']) > 0 ? " et déménageurs invités" : "");
      $stmt->close();
      header('Location: mes_annonces.php');
      exit;
    } else {
      // 获取详细的错误信息
      $error_msg = "Impossible d'enregistrer l'annonce";
      if($mysqli->error) {
        $error_msg .= " : " . $mysqli->error;
      }
      if($stmt->error) {
        $error_msg .= " (Statement: " . $stmt->error . ")";
      }
      $_SESSION['erreur'] = $error_msg;
      $stmt->close();
      header('Location: annonce_nouvelle.php');
      exit;
    }
  } else {
    $_SESSION['erreur'] = "Erreur de préparation : " . ($mysqli->error ?? "Erreur inconnue");
    header('Location: annonce_nouvelle.php');
    exit;
  }
?>



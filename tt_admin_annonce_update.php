<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 3) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $annonceId = isset($_POST['annonce_id']) ? (int)$_POST['annonce_id'] : 0;
  
  if($annonceId <= 0){
    $_SESSION['erreur'] = "ID d'annonce invalide";
    header('Location: admin.php');
    exit;
  }

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
  $statut = $_POST['statut'] ?? 'publie';

  if($titre === '' || $date_debut === '' || $ville_depart === '' || $ville_arrivee === '' || !in_array($statut, ['brouillon', 'publie', 'cloture'], true)) {
    $_SESSION['erreur'] = "Champs obligatoires manquants ou invalides";
    header('Location: admin_annonce_edit.php?id='.$annonceId);
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: admin_annonce_edit.php?id='.$annonceId);
    exit;
  }

  // 验证公告存在
  if($stmt = $mysqli->prepare("SELECT id FROM annonce WHERE id=?")){
    $stmt->bind_param("i", $annonceId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Annonce introuvable";
      $stmt->close();
      header('Location: admin.php');
      exit;
    }
    $stmt->close();
  }

  // 更新公告
  $sql = "UPDATE annonce SET titre=?, description=?, date_debut=?, ville_depart=?, ville_arrivee=?, 
          depart_type=?, depart_etage=?, depart_ascenseur=?, arrivee_type=?, arrivee_etage=?, 
          arrivee_ascenseur=?, volume_m3=?, poids_kg=?, nb_demenageurs=?, statut=? 
          WHERE id=?";
  
  if($stmt = $mysqli->prepare($sql)){
    // 处理 NULL 值
    $depart_etage_ref = $depart_etage;
    $arrivee_etage_ref = $arrivee_etage;
    $volume_m3_ref = $volume_m3;
    $poids_kg_ref = $poids_kg;
    $nb_demenageurs_ref = $nb_demenageurs;
    
    $types = "ssssss";
    $params = [&$titre, &$description, &$date_debut, &$ville_depart, &$ville_arrivee, &$depart_type];
    
    // depart_etage
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
    
    // arrivee_etage
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
    
    // volume_m3
    if($volume_m3 === NULL) {
      $types .= "s";
      $volume_m3_null = null;
      $params[] = &$volume_m3_null;
    } else {
      $types .= "d";
      $params[] = &$volume_m3_ref;
    }
    
    // poids_kg
    if($poids_kg === NULL) {
      $types .= "s";
      $poids_kg_null = null;
      $params[] = &$poids_kg_null;
    } else {
      $types .= "i";
      $params[] = &$poids_kg_ref;
    }
    
    // nb_demenageurs
    if($nb_demenageurs === NULL) {
      $types .= "s";
      $nb_demenageurs_null = null;
      $params[] = &$nb_demenageurs_null;
    } else {
      $types .= "i";
      $params[] = &$nb_demenageurs_ref;
    }
    
    $types .= "si";
    $params[] = &$statut;
    $params[] = &$annonceId;
    
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    
    if($stmt->execute()){
      $_SESSION['message'] = "Annonce modifiée avec succès";
      header('Location: admin.php');
    } else {
      $_SESSION['erreur'] = "Impossible de modifier l'annonce";
      header('Location: admin_annonce_edit.php?id='.$annonceId);
    }
    $stmt->close();
  } else {
    $_SESSION['erreur'] = "Erreur de préparation de la requête";
    header('Location: admin_annonce_edit.php?id='.$annonceId);
  }

  $mysqli->close();
  exit;
?>


<?php
  session_start();
  require_once("param.inc.php");

  $email = isset($_POST['email']) ? trim($_POST['email']) : '';
  $password = isset($_POST['password']) ? $_POST['password'] : '';
  $selected_role = isset($_POST['role']) ? (int)$_POST['role'] : 0;

  if ($email === '' || $password === '' || $selected_role === 0) {
      $_SESSION['erreur'] = "Tous les champs sont requis";
      header('Location: connexion.php');
      exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if ($mysqli->connect_error) {
      $_SESSION['erreur'] = "Problème de connexion à la base de données";
      header('Location: connexion.php');
      exit;
  }

  // 检查 actif 字段是否存在 / Vérifier si le champ actif existe
  // 这个字段用于账户状态管理（激活/停用）
  // Ce champ sert à la gestion du statut des comptes (actif/désactivé)
  $checkActif = $mysqli->query("SHOW COLUMNS FROM compte LIKE 'actif'");
  $hasActifField = $checkActif && $checkActif->num_rows > 0;
  
  // 构建查询语句 / Construire la requête SQL
  // 如果actif字段存在，则包含在查询中
  // Si le champ actif existe, l'inclure dans la requête
  $sql = "SELECT id, nom, prenom, email, password, role";
  if($hasActifField) {
    $sql .= ", actif";
  }
  $sql .= " FROM compte WHERE email = ? AND role = ? LIMIT 1";
  
  if ($stmt = $mysqli->prepare($sql)) {
      $stmt->bind_param("si", $email, $selected_role);
      if ($stmt->execute()) {
          $result = $stmt->get_result();
          if ($row = $result->fetch_assoc()) {
              // 检查账户是否被停用 / Vérifier si le compte est désactivé
              // 如果账户被停用（actif=0），拒绝登录
              // Si le compte est désactivé (actif=0), refuser la connexion
              if($hasActifField && isset($row['actif']) && $row['actif'] == 0){
                $_SESSION['erreur'] = "Votre compte a été désactivé. Veuillez contacter l'administrateur.";
                header('Location: connexion.php');
                exit;
              }
              
              if (password_verify($password, $row['password'])) {
                  $_SESSION['user'] = [
                      'id' => $row['id'],
                      'nom' => $row['nom'],
                      'prenom' => $row['prenom'],
                      'email' => $row['email'],
                      'role' => (int)$row['role']
                  ];
                  $_SESSION['message'] = "Connexion réussie";
                  // Redirection selon le rôle / Redirection selon le rôle
                  // role=1: 客户 / Client -> 创建公告页面 / Page de création d'annonce
                  // role=2: 搬家工人 / Déménageur -> 我的报价页面 / Page mes offres
                  // role=3: 管理员 / Administrateur -> 管理页面 / Page d'administration
                  if((int)$row['role'] === 1){
                    header('Location: annonce_nouvelle.php');
                  } else if((int)$row['role'] === 2){
                    header('Location: mes_offres.php');
                  } else if((int)$row['role'] === 3){
                    header('Location: admin.php'); // 管理员重定向到管理页面 / Redirection administrateur vers la page d'administration
                  } else {
                    header('Location: index.php');
                  }
                  exit;
              }
          }
      }
      $stmt->close();
  }

  $_SESSION['erreur'] = "Identifiants invalides ou type de compte incorrect";
  header('Location: connexion.php');
  exit;
?>
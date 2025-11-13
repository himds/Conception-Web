<?php
  session_start();
  if(!isset($_SESSION['user']) || (int)$_SESSION['user']['role'] !== 2) {
    $_SESSION['erreur'] = "Accès refusé";
    header('Location: connexion.php');
    exit;
  }

  require_once('param.inc.php');

  $demenageurId = (int)$_SESSION['user']['id'];
  $nominationId = isset($_POST['nomination_id']) ? (int)$_POST['nomination_id'] : 0;
  $action = isset($_POST['action']) ? $_POST['action'] : '';

  if($nominationId <= 0 || !in_array($action, ['accepte', 'refuse'], true)){
    $_SESSION['erreur'] = "Paramètres invalides";
    header('Location: mes_nominations.php');
    exit;
  }

  $mysqli = new mysqli($host, $login, $passwd, $dbname);
  if($mysqli->connect_error){
    $_SESSION['erreur'] = "Problème de BDD";
    header('Location: mes_nominations.php');
    exit;
  }

  // 验证指名属于当前搬家工人
  if($stmt = $mysqli->prepare("SELECT id FROM nomination WHERE id=? AND demenageur_id=? AND etat='en_attente'")){
    $stmt->bind_param("ii", $nominationId, $demenageurId);
    $stmt->execute();
    $res = $stmt->get_result();
    if(!$res->fetch_assoc()){
      $_SESSION['erreur'] = "Nomination introuvable ou déjà traitée";
      $stmt->close();
      header('Location: mes_nominations.php');
      exit;
    }
    $stmt->close();
  }

  // 获取公告ID（在删除之前）
  $annonceId = 0;
  if($stmtAnnonce = $mysqli->prepare("SELECT annonce_id FROM nomination WHERE id=?")){
    $stmtAnnonce->bind_param("i", $nominationId);
    $stmtAnnonce->execute();
    $resAnnonce = $stmtAnnonce->get_result();
    if($rowAnnonce = $resAnnonce->fetch_assoc()){
      $annonceId = (int)$rowAnnonce['annonce_id'];
    }
    $stmtAnnonce->close();
  }

  // 更新指名状态
  if($action === 'refuse'){
    // 拒绝：更新状态为refuse（不删除，以便追踪所有响应）
    if($stmt = $mysqli->prepare("UPDATE nomination SET etat='refuse' WHERE id=? AND demenageur_id=?")){
      $stmt->bind_param("ii", $nominationId, $demenageurId);
      if($stmt->execute()){
        $_SESSION['message'] = "Nomination refusée";
        
        // 检查是否所有nomination都已响应
        if($annonceId > 0){
          // 获取公告的nb_demenageurs
          $nbDemenageurs = null;
          if($stmtNb = $mysqli->prepare("SELECT nb_demenageurs FROM annonce WHERE id=?")){
            $stmtNb->bind_param("i", $annonceId);
            $stmtNb->execute();
            $resNb = $stmtNb->get_result();
            if($rowNb = $resNb->fetch_assoc()){
              $nbDemenageurs = $rowNb['nb_demenageurs'];
            }
            $stmtNb->close();
          }
          
          // 如果有指定数量，检查所有nomination是否都已响应
          if($nbDemenageurs && $nbDemenageurs > 0){
            // 查询该公告的所有nomination
            if($stmtCheck = $mysqli->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN etat IN ('accepte', 'refuse') THEN 1 ELSE 0 END) as responded FROM nomination WHERE annonce_id=?")){
              $stmtCheck->bind_param("i", $annonceId);
              $stmtCheck->execute();
              $resCheck = $stmtCheck->get_result();
              if($rowCheck = $resCheck->fetch_assoc()){
                $totalCount = (int)$rowCheck['total'];
                $respondedCount = (int)$rowCheck['responded'];
                
                // 如果所有nomination都已响应（接受或拒绝），关闭请求
                if($totalCount > 0 && $totalCount == $respondedCount){
                  // 所有nomination都已响应，关闭请求
                  if($stmtClose = $mysqli->prepare("UPDATE annonce SET statut='cloture' WHERE id=?")){
                    $stmtClose->bind_param("i", $annonceId);
                    $stmtClose->execute();
                    $stmtClose->close();
                  }
                }
              }
              $stmtCheck->close();
            }
          }
        }
      } else {
        $_SESSION['erreur'] = "Impossible de refuser la nomination";
      }
      $stmt->close();
    }
  } else {
    // 接受：更新状态
    if($stmt = $mysqli->prepare("UPDATE nomination SET etat='accepte' WHERE id=? AND demenageur_id=?")){
      $stmt->bind_param("ii", $nominationId, $demenageurId);
      if($stmt->execute()){
        $_SESSION['message'] = "Nomination acceptée ! Vous pouvez maintenant faire une offre.";
        
        // 检查是否所有nomination都已响应
        if($annonceId > 0){
          // 获取公告的nb_demenageurs
          $nbDemenageurs = null;
          if($stmtNb = $mysqli->prepare("SELECT nb_demenageurs FROM annonce WHERE id=?")){
            $stmtNb->bind_param("i", $annonceId);
            $stmtNb->execute();
            $resNb = $stmtNb->get_result();
            if($rowNb = $resNb->fetch_assoc()){
              $nbDemenageurs = $rowNb['nb_demenageurs'];
            }
            $stmtNb->close();
          }
          
          // 如果有指定数量，检查所有nomination是否都已响应
          if($nbDemenageurs && $nbDemenageurs > 0){
            // 查询该公告的所有nomination
            if($stmtCheck = $mysqli->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN etat IN ('accepte', 'refuse') THEN 1 ELSE 0 END) as responded FROM nomination WHERE annonce_id=?")){
              $stmtCheck->bind_param("i", $annonceId);
              $stmtCheck->execute();
              $resCheck = $stmtCheck->get_result();
              if($rowCheck = $resCheck->fetch_assoc()){
                $totalCount = (int)$rowCheck['total'];
                $respondedCount = (int)$rowCheck['responded'];
                
                // 如果所有nomination都已响应（接受或拒绝），关闭请求
                if($totalCount > 0 && $totalCount == $respondedCount){
                  // 所有nomination都已响应，关闭请求
                  if($stmtClose = $mysqli->prepare("UPDATE annonce SET statut='cloture' WHERE id=?")){
                    $stmtClose->bind_param("i", $annonceId);
                    $stmtClose->execute();
                    $stmtClose->close();
                  }
                }
              }
              $stmtCheck->close();
            }
          }
        }
      } else {
        $_SESSION['erreur'] = "Impossible d'accepter la nomination";
      }
      $stmt->close();
    }
  }

  $mysqli->close();
  header('Location: mes_nominations.php');
  exit;
?>




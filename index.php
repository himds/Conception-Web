<?php
session_start();
$titre = "Accueil";
include('header.inc.php');
include('menu.inc.php');
include('message.inc.php');
?>

<!-- Couche d'animation d'entrée - affichée uniquement lors de la première visite -->
<div class="intro-overlay" id="introOverlay" style="display: none;">
  <div class="intro-content">
    <h1 class="intro-title">DéménageFacile</h1>
  </div>
</div>

<div class="index-page-background" id="mainContent">
<div class="container-fluid my-4">

  <div class="row g-4">

    <!-- 🧊 Block 1 -->
    <div class="col-12 col-md-6">
      <div class="p-3 bg-light border rounded h-100">
        <h1 class="mb-3">DéménageFacile – Votre partenaire de confiance pour un déménagement sans stress !</h1>
        <div class="scrollable-content">
          <p>Besoin de changer de maison ou de bureau ? Notre équipe de professionnels est là pour vous accompagner à chaque étape : emballage, transport, déchargement et installation.</p>
          <p>Avec DéménageFacile, vous bénéficiez d'un service rapide, sûr et personnalisé, adapté à vos besoins et à votre budget.</p>
          <p>Que vous soyez un particulier ou une entreprise, nous vous garantissons un déménagement simple, efficace et sans tracas.</p>
          <p><strong>📦 Nos services :</strong></p>
          <ul>
            <li>Déménagement local et national</li>
            <li>Emballage et protection de vos biens</li>
            <li>Transport sécurisé</li>
            <li>Montage et démontage de meubles</li>
            <li>Service de stockage temporaire</li>
          </ul>
          <p><strong>🚚 Pourquoi nous choisir ?</strong></p>
          <ul>
            <li>✔ Équipe professionnelle et expérimentée</li>
            <li>✔ Respect des délais</li>
            <li>✔ Tarifs transparents</li>
            <li>✔ Assurance incluse</li>
          </ul>
          <p>Contactez-nous dès aujourd'hui et laissez-nous faire le travail pendant que vous profitez de votre nouveau départ !</p>
        </div>
      </div>
    </div>

    <!-- 🧊 Block 2 -->
    <div class="col-12 col-md-6">
      <div class="p-3 bg-light border rounded h-100">
        <h2 class="h4 mb-3">Recherche par villes</h2>
        <form id="searchForm" class="mb-3">
          <div class="mb-2">
            <label class="form-label small" for="villeDepart">Ville de départ</label>
            <input type="text" class="form-control" id="villeDepart" placeholder="Ville de départ">
          </div>
          <div class="mb-2">
            <label class="form-label small" for="villeArrivee">Ville d'arrivée</label>
            <input type="text" class="form-control" id="villeArrivee" placeholder="Ville d'arrivée">
          </div>
          <div class="d-grid gap-2">
            <button class="btn btn-orange" type="submit">Rechercher</button>
            <button class="btn btn-outline-secondary btn-sm" type="button" id="resetSearch">Afficher tout</button>
          </div>
        </form>
        <div id="searchResults" class="search-results-scroll"></div>
      </div>
    </div>

    <!-- 🧊 Block 3 (Video and Carousel side by side) -->
    <div class="col-12">
      <div class="p-3 bg-light border rounded">
        <div class="row g-4">
          <!-- Section vidéo - gauche -->
          <div class="col-12 col-md-6">
            <div class="video-title-container">
              <h2 class="h4 mb-3">Video</h2>
            </div>
            <div class="video-container">
              <video width="100%" controls>
                <source src="video/20251106_1250_01k9bqf0hxftf9t260qj35kn05.mp4" type="video/mp4">
                Votre navigateur ne supporte pas la vidéo.
              </video>
            </div>
          </div>

          <!-- Section carrousel - droite -->
          <div class="col-12 col-md-6">
            <div class="carousel-title-container">
              <h2 class="h4 mb-3">Véhicules</h2>
            </div>
            <div class="carousel-container mb-4">
              <button class="carousel-btn carousel-btn-prev" onclick="changeSlide(-1)">
                <span class="carousel-arrow">◀</span>
              </button>
              <div class="carousel-wrapper">
                <div class="carousel-slide active">
                  <img src="images/car.png" alt="Voiture" class="carousel-image">
                </div>
                <div class="carousel-slide">
                  <img src="images/truck.png" alt="Camion" class="carousel-image">
                </div>
                <div class="carousel-slide">
                  <img src="images/E-bike.png" alt="Vélo électrique" class="carousel-image">
                </div>
              </div>
              <button class="carousel-btn carousel-btn-next" onclick="changeSlide(1)">
                <span class="carousel-arrow">▶</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
</div>

<script>
// Animation d'entrée - affichée uniquement lors de la première visite
document.addEventListener('DOMContentLoaded', function() {
  const overlay = document.getElementById('introOverlay');
  const hasSeenIntro = localStorage.getItem('hasSeenIntro');
  
  if (!hasSeenIntro && overlay) {
    // Première visite, afficher l'animation
    overlay.style.display = 'flex';
    
    // Marquer que l'animation a été vue
    localStorage.setItem('hasSeenIntro', 'true');
    
    // Supprimer complètement l'élément overlay après 3 secondes
    setTimeout(() => {
      overlay.style.display = 'none';
    }, 3000);
  } else {
    // Animation déjà vue, masquer directement et afficher le contenu principal
    if (overlay) {
      overlay.style.display = 'none';
    }
    // Afficher immédiatement le contenu principal (sans attendre l'animation)
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
      mainContent.style.opacity = '1';
    }
  }
});

// Fonctionnalité du carrousel
(function() {
  let currentSlide = 0;
  let slides = [];
  let totalSlides = 0;
  
  function initCarousel() {
    slides = document.querySelectorAll('.carousel-slide');
    totalSlides = slides.length;
    if (totalSlides > 0) {
      showSlide(0);
    }
  }
  
  function showSlide(index) {
    if (slides.length === 0) return;
    
    // Supprimer toutes les classes active
    slides.forEach(slide => slide.classList.remove('active'));
    
    // S'assurer que l'index est dans la plage valide (boucle)
    if (index < 0) {
      currentSlide = totalSlides - 1;
    } else if (index >= totalSlides) {
      currentSlide = 0;
    } else {
      currentSlide = index;
    }
    
    // Ajouter la classe active à la diapositive actuelle
    if (slides[currentSlide]) {
      slides[currentSlide].classList.add('active');
    }
  }
  
  // Exposer la fonction à la portée globale
  window.changeSlide = function(direction) {
    showSlide(currentSlide + direction);
  };
  
  // Initialisation
  document.addEventListener('DOMContentLoaded', function() {
    initCarousel();
  });
  
  // Si le DOM est déjà chargé, initialiser immédiatement
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCarousel);
  } else {
    initCarousel();
  }
})();

// Fonctionnalité de recherche par ville
function loadAnnonces(ville_depart = '', ville_arrivee = '') {
  const searchResults = document.getElementById('searchResults');
  
  searchResults.innerHTML = '<div class="text-center"><span class="spinner-border spinner-border-sm" role="status"></span> Chargement...</div>';
  
  let url = 'tt_search_annonces.php';
  const params = new URLSearchParams();
  if(ville_depart) params.append('ville_depart', ville_depart);
  if(ville_arrivee) params.append('ville_arrivee', ville_arrivee);
  if(params.toString()) url += '?' + params.toString();
  
  fetch(url)
    .then(response => response.json())
    .then(data => {
      if(data.success) {
        if(data.annonces && data.annonces.length > 0) {
          let html = '<div class="alert alert-success">' + data.count + ' annonce(s) trouvée(s)</div>';
          html += '<div class="list-group">';
          data.annonces.forEach(function(annonce) {
            html += '<div class="list-group-item">';
            html += '<h6 class="mb-1">' + annonce.titre;
            if(annonce.statut === 'cloture') {
              html += ' <span class="badge bg-secondary">Clôturée</span>';
            }
            html += '</h6>';
            html += '<p class="mb-1 text-muted small">' + annonce.ville_depart + ' → ' + annonce.ville_arrivee + '</p>';
            html += '<p class="mb-1 text-muted small">Date: ' + annonce.date_debut + '</p>';
            html += '<a href="annonce_detail.php?id=' + annonce.id + '" class="btn btn-sm btn-outline-orange mt-2">Voir détails</a>';
            html += '</div>';
          });
          html += '</div>';
          searchResults.innerHTML = html;
        } else {
          searchResults.innerHTML = '<div class="alert alert-info">Aucune annonce trouvée.</div>';
        }
      } else {
        searchResults.innerHTML = '<div class="alert alert-danger">Erreur: ' + (data.message || 'Erreur inconnue') + '</div>';
      }
    })
    .catch(error => {
      searchResults.innerHTML = '<div class="alert alert-danger">Erreur de connexion.</div>';
      console.error('Error:', error);
    });
}

document.addEventListener('DOMContentLoaded', function() {
  const searchForm = document.getElementById('searchForm');
  const resetBtn = document.getElementById('resetSearch');
  const searchResults = document.getElementById('searchResults');
  
  // Afficher toutes les annonces par défaut au chargement de la page
  loadAnnonces();
  
  if(searchForm) {
    searchForm.addEventListener('submit', function(e) {
      e.preventDefault();
      const ville_depart = document.getElementById('villeDepart').value.trim();
      const ville_arrivee = document.getElementById('villeArrivee').value.trim();
      
      // Si une ville de départ est saisie, la ville d'arrivée doit également être saisie
      if(ville_depart && !ville_arrivee) {
        searchResults.innerHTML = '<div class="alert alert-warning">Si vous entrez une ville de départ, vous devez également entrer une ville d\'arrivée.</div>';
        return;
      }
      
      loadAnnonces(ville_depart, ville_arrivee);
    });
  }
  
  if(resetBtn) {
    resetBtn.addEventListener('click', function() {
      document.getElementById('villeDepart').value = '';
      document.getElementById('villeArrivee').value = '';
      loadAnnonces();
    });
  }
});
</script>

<?php include('footer.inc.php'); ?>


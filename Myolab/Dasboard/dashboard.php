<?php
session_start();

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: ../iamodinson.php');
    exit();
}

// Session hijacking koruması
if ($_SESSION['login_time'] < (time() - (8 * 60 * 60))) { // 8 saat
    session_destroy();
    header('Location: ../iamodinson.php?expired=1');
    exit();
}

// Kullanıcı bilgileri
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MyoLab Dashboard</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">


</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-flask"></i> MyoLab</h2>
            </div>
            
            <!-- Katalog Görüntüle Butonu -->
            <div class="sidebar-catalog-section">
                <a href="../myokatalog/index.html" target="_blank" class="sidebar-catalog-btn">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Kataloğu Görüntüle</span>
                </a>
            </div>
            
            <div id="tree-container">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>Ağaç yapısı yükleniyor...</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="header">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <h1>Dashboard</h1>
                        <p>Kategori ve laboratuvar yönetim sistemi</p>
                    </div>
                    <div class="header-buttons">
                        <button onclick="toggleCategoryForm()" class="btn btn-primary" id="category-toggle-btn">
                            <i class="fas fa-folder"></i> Kategori
                        </button>
                        <button onclick="toggleLaboratoryForm()" class="btn btn-info" id="laboratory-toggle-btn">
                            <i class="fas fa-flask"></i>  Laboratuvar 
                        </button>
                        <button onclick="toggleDeviceList()" class="btn btn-warning" id="device-toggle-btn">
                            <i class="fas fa-microchip"></i> Cihaz
                        </button>
                        <button onclick="logout()" class="btn" style="background: #e74c3c;">
                            <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                        </button>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stats">
                <div class="stat-card">
                    <h3 id="categories-count">-</h3>
                    <p>Toplam Kategori</p>
                </div>
                <div class="stat-card">
                    <h3 id="laboratories-count">-</h3>
                    <p>Toplam Laboratuvar</p>
                </div>
            </div>

            <!-- Forms -->
            <div class="forms-container">
                <!-- Overlay Background -->
                <div class="overlay" id="overlay" style="display: none;"></div>

                <!-- Category Management Form -->
                <div class="form-card toggleable-form" id="category-form-card" style="display: none;">
                    <div class="form-header">
                        <h3><i class="fas fa-folder"></i> Kategori Yönetimi</h3>
                        <button type="button" class="close-form-btn" onclick="toggleCategoryForm()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Tab Buttons -->
                    <div class="tab-buttons">
                        <button type="button" class="tab-btn active" onclick="switchCategoryTab('add')" id="add-tab-btn">
                            <i class="fas fa-plus"></i> Kategori Ekle
                        </button>
                        <button type="button" class="tab-btn" onclick="switchCategoryTab('delete')" id="delete-tab-btn">
                            <i class="fas fa-trash"></i> Kategori Sil
                        </button>
                    </div>
                    
                    <!-- Add Category Tab -->
                    <div id="add-category-tab" class="tab-content active">
                        <form id="category-form" class="modern-form">
                            <div class="form-group">
                                <label for="category-name">
                                    <i class="fas fa-folder"></i> Kategori Adı
                                </label>
                                <input type="text" id="category-name" name="name" required class="modern-input" placeholder="Kategori adını girin...">
                            </div>
                            
                            <div class="form-actions modern-actions">
                                <button type="submit" class="btn btn-success modern-btn">
                                    <i class="fas fa-plus"></i> Kategori Ekle
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Delete Category Tab -->
                    <div id="delete-category-tab" class="tab-content">
                        <form id="category-delete-form" class="modern-form">
                            <div class="form-group">
                                <label for="delete-category-select">
                                    <i class="fas fa-folder-minus"></i> Silinecek Kategori
                                </label>
                                <select id="delete-category-select" class="modern-select" required>
                                    <option value="">Kategori seçin...</option>
                                </select>
                            </div>
                            
                            <div class="form-actions modern-actions">
                                <button type="button" onclick="deleteCategory()" class="btn btn-danger modern-btn">
                                    <i class="fas fa-trash"></i> Kategori Sil
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Laboratory Management Form -->
                <div class="form-card toggleable-form" id="laboratory-form-card" style="display: none;">
                    <div class="form-header">
                        <h3><i class="fas fa-flask"></i> Laboratuvar Yönetimi</h3>
                        <button type="button" class="close-form-btn" onclick="toggleLaboratoryForm()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <!-- Tab Buttons -->
                    <div class="tab-buttons">
                        <button type="button" class="tab-btn active" onclick="switchLaboratoryTab('add')" id="lab-add-tab-btn">
                            <i class="fas fa-plus"></i> Laboratuvar Ekle
                        </button>
                        <button type="button" class="tab-btn" onclick="switchLaboratoryTab('delete-lab')" id="lab-delete-tab-btn">
                            <i class="fas fa-trash"></i> Laboratuvar Sil
                        </button>
                    </div>
                    
                    <!-- Add Laboratory Tab -->
                    <div id="add-laboratory-tab" class="tab-content active">
                        <form id="laboratory-form" class="modern-form">
                            <div class="form-group">
                                <label for="laboratory-category">
                                    <i class="fas fa-folder"></i> Kategori
                                </label>
                                <select id="laboratory-category" name="category_id" required class="modern-select">
                                    <option value="">Kategori seçin...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="laboratory-name">
                                    <i class="fas fa-flask"></i> Laboratuvar Adı
                                </label>
                                <input type="text" id="laboratory-name" name="name" required class="modern-input" placeholder="Laboratuvar adını girin...">
                            </div>
                            
                            <div class="form-actions modern-actions">
                                <button type="submit" class="btn btn-success modern-btn">
                                    <i class="fas fa-plus"></i> Laboratuvar Ekle
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Delete Laboratory Tab -->
                    <div id="delete-laboratory-tab" class="tab-content">
                        <form id="laboratory-delete-form" class="modern-form">
                            <div class="form-group">
                                <label for="delete-laboratory-select">
                                    <i class="fas fa-trash"></i> Silinecek Laboratuvar
                                </label>
                                <select id="delete-laboratory-select" class="modern-select" required>
                                    <option value="">Laboratuvar seçin...</option>
                                </select>
                            </div>
                            
                            <div class="form-actions modern-actions">
                                <button type="button" onclick="deleteLaboratory()" class="btn btn-danger modern-btn">
                                    <i class="fas fa-trash"></i> Laboratuvar Sil
                                </button>
                            </div>
                        </form>
                    </div>
                    

                </div>

                <!-- Add Device Form -->
                <div class="form-card toggleable-form" id="device-form-card" style="display: none;">
                    <div class="form-header">
                        <h3><i class="fas fa-microchip"></i> Yeni Cihaz Ekle</h3>
                        <button type="button" class="close-form-btn" onclick="toggleDeviceForm()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <p class="card-subtitle">Laboratuvar cihazlarını ve ekipmanlarını sisteme ekleyin</p>
                    
                    <form id="device-form" class="modern-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="device-lab">
                                    <i class="fas fa-flask"></i> Laboratuvar
                                </label>
                                <select id="device-lab" name="lab_id" required class="modern-select">
                                    <option value="">Laboratuvar seçin...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="device-name">
                                    <i class="fas fa-microchip"></i> Cihaz Adı
                                </label>
                                <input type="text" id="device-name" name="device_name" required class="modern-input" placeholder="Cihaz adını girin...">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="device-model">
                                    <i class="fas fa-tag"></i> Model
                                </label>
                                <input type="text" id="device-model" name="device_model" class="modern-input" placeholder="Model bilgisi (opsiyonel)">
                            </div>
                            
                            <div class="form-group">
                                <label for="device-count">
                                    <i class="fas fa-layer-group"></i> Sayısı
                                </label>
                                <input type="number" id="device-count" name="device_count" value="1" min="1" class="modern-input">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="device-purpose">
                                <i class="fas fa-info-circle"></i> Kullanım Amacı
                            </label>
                            <textarea id="device-purpose" name="purpose" rows="3" placeholder="Cihazın kullanım amacını açıklayın..." class="modern-textarea"></textarea>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="form-group">
                            <label for="device-image">
                                <i class="fas fa-image"></i> Cihaz Resmi
                            </label>
                            <div class="image-upload-container modern-upload">
                                <input type="file" id="device-image" name="image" accept="image/*" style="display: none;">
                                <div class="image-upload-area" id="image-upload-area">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Resim yüklemek için tıklayın veya sürükleyin</p>
                                        <small>JPG, PNG, GIF, WebP (Max: 5MB)</small>
                                    </div>
                                </div>
                                <div class="image-preview" id="image-preview" style="display: none;">
                                    <img id="preview-img" src="" alt="Önizleme">
                                    <button type="button" id="remove-image" class="btn btn-sm btn-danger modern-btn">
                                        <i class="fas fa-times"></i> Kaldır
                                    </button>
                                </div>
                                <div class="upload-progress" id="upload-progress" style="display: none;">
                                    <div class="upload-progress-bar">
                                        <div class="upload-progress-fill"></div>
                                    </div>
                                    <small>Yükleniyor...</small>
                                </div>
                                <input type="hidden" id="uploaded-image-url" name="uploaded_image_url">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="device-order">
                                <i class="fas fa-sort-numeric-up"></i> Sıra
                            </label>
                            <input type="number" id="device-order" name="order_num" value="0" min="0" class="modern-input" placeholder="Otomatik olarak ayarlanır">
                            <small class="form-help">Laboratuvar seçildiğinde otomatik olarak mevcut cihaz sayısına göre ayarlanır</small>
                        </div>
                        
                        <div class="form-actions modern-actions">
                            <button type="submit" class="btn btn-success modern-btn">
                                <i class="fas fa-plus"></i> Cihaz Ekle
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Device List Popup -->
                <div class="form-card toggleable-form" id="device-list-card" style="display: none;">
                    <div class="form-header">
                        <h3><i class="fas fa-microchip"></i> Cihaz Listesi</h3>
                        <div class="form-header-actions">
                            <button onclick="toggleDeviceForm()" class="btn btn-success modern-btn btn-sm">
                                <i class="fas fa-plus"></i> Yeni Cihaz Ekle
                            </button>
                            <button type="button" class="close-form-btn" onclick="toggleDeviceList()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                    <p class="card-subtitle">Laboratuvarlardaki cihazları görüntüleyin</p>
                    
                    <div class="device-list-container">
                        <div class="device-list-header">
                            <h4>Laboratuvar Seçin</h4>
                            <select id="device-list-lab-select" class="modern-select" onchange="loadDeviceList()">
                                <option value="">Laboratuvar seçin...</option>
                            </select>
                        </div>
                        
                        <div id="device-list-content" class="device-list-content">
                            <div class="device-list-placeholder">
                                <i class="fas fa-microchip"></i>
                                <p>Laboratuvar seçin ve cihazları görüntüleyin</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Laboratory Content Management -->
                <div class="form-card content-management-card">
                    <div class="card-header">
                        <h3><i class="fas fa-edit"></i> Laboratuvar İçerik Yönetimi</h3>
                        <p class="card-subtitle">Laboratuvar başlığı, açıklaması ve ana resmini yönetin</p>
                    </div>
                    
                    <form id="content-form" class="modern-form">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="content-lab">
                                    <i class="fas fa-flask"></i> Laboratuvar
                                </label>
                                <select id="content-lab" name="lab_id" required class="modern-select">
                                    <option value="">Laboratuvar seçin...</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="content-type">
                                    <i class="fas fa-tag"></i> İçerik Tipi
                                </label>
                                <select id="content-type" name="content_type" required onchange="toggleContentInput()" class="modern-select">
                                    <option value="">İçerik tipi seçin...</option>
                                    <option value="main_image">🖼️ Ana Resim</option>
                                    <option value="lab_title">📝 Laboratuvar Başlığı</option>
                                    <option value="about_text">📄 Katalog Hakkında Metni (Kısa)</option>
                                    <option value="detail_about_text">📖 Detay Sayfası Hakkında Metni (Uzun)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Text/URL Input (for title and about text) -->
                                                    <div class="form-group" id="text-input-group">
                                <label for="content-value">
                                    <i class="fas fa-align-left"></i> Laboratuvar Hakkında Bilgi Metni
                                </label>
                            <textarea id="content-value" name="content_value" rows="4" placeholder="Laboratuvar hakkında detaylı bilgi metnini girin..." class="modern-textarea"></textarea>
                        </div>
                        
                        <!-- Image Upload Input (for main image) -->
                        <div class="form-group" id="image-input-group" style="display: none;">
                            <label for="content-image">
                                <i class="fas fa-image"></i> Laboratuvar Ana Resmi
                            </label>
                            <div class="image-upload-container modern-upload">
                                <input type="file" id="content-image" name="image" accept="image/*" style="display: none;">
                                <div class="image-upload-area" id="content-image-upload-area">
                                    <div class="upload-placeholder">
                                        <i class="fas fa-cloud-upload-alt"></i>
                                        <p>Laboratuvar ana resmi yüklemek için tıklayın veya sürükleyin</p>
                                        <small>JPG, PNG, GIF, WebP (Max: 5MB)</small>
                                    </div>
                                </div>
                                <div class="image-preview" id="content-image-preview" style="display: none;">
                                    <img id="content-preview-img" src="" alt="Önizleme">
                                    <button type="button" id="remove-content-image" class="btn btn-sm btn-danger modern-btn">
                                        <i class="fas fa-times"></i> Kaldır
                                    </button>
                                </div>
                                <div class="upload-progress" id="content-upload-progress" style="display: none;">
                                    <div class="upload-progress-bar">
                                        <div class="upload-progress-fill"></div>
                                    </div>
                                    <small>Yükleniyor...</small>
                                </div>
                                <input type="hidden" id="uploaded-content-image-url" name="uploaded_image_url">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="content-alt">
                                <i class="fas fa-comment-alt"></i> Alt Text (Resimler için)
                            </label>
                            <input type="text" id="content-alt" name="alt_text" placeholder="Resim açıklaması (opsiyonel)" class="modern-input">
                        </div>
                        
                        <div class="form-actions modern-actions">
                            <button type="submit" class="btn btn-success modern-btn">
                                <i class="fas fa-save"></i> Kaydet
                            </button>
                            <button type="button" id="load-content-btn" class="btn btn-info modern-btn">
                                <i class="fas fa-download"></i> Yükle
                            </button>
                            <button type="button" id="delete-content-btn" class="btn btn-danger modern-btn">
                            &nbsp;&nbsp;    <i class="fas fa-trash"></i> Sil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Laboratory Modal -->
    <div id="laboratoryModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modal-title">Laboratuvar</h3>
                <span class="close">&times;</span>
            </div>
            <div class="modal-body">
                <div id="laboratory-info" style="padding: 20px; text-align: center;">
                    <p>Laboratuvar bilgileri burada görüntülenecek</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let treeData = [];
        let categories = [];

        // Show notification function
        function showNotification(message, type = 'info') {
            // Remove existing notifications
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notification => notification.remove());
            
            // Create notification element
            const notification = document.createElement('div');
            notification.className = `notification notification-${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
                    <span>${message}</span>
                    <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(notification);
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.remove();
                }
            }, 5000);
        }

        // Show confirmation dialog
        function showConfirmation(message, onConfirm, onCancel = null) {
            // Remove existing confirmations
            const existingConfirmations = document.querySelectorAll('.confirmation-dialog');
            existingConfirmations.forEach(confirmation => confirmation.remove());
            
            // Create confirmation element
            const confirmation = document.createElement('div');
            confirmation.className = 'confirmation-dialog';
            confirmation.style.opacity = '0';
            confirmation.style.display = 'flex';
            
            confirmation.innerHTML = `
                <div class="confirmation-content" style="opacity: 0; transform: scale(0.8) translate(-50%, -50%) translateZ(0);">
                    <div class="confirmation-header">
                        <i class="fas fa-question-circle"></i>
                        <span>Onay Gerekli</span>
                    </div>
                    <div class="confirmation-message">
                        ${message}
                    </div>
                    <div class="confirmation-actions">
                        <button class="btn btn-danger modern-btn" onclick="confirmAction()">
                            <i class="fas fa-check"></i> Onayla
                        </button>
                        <button class="btn btn-secondary modern-btn" onclick="cancelAction()">
                            <i class="fas fa-times"></i> İptal
                        </button>
                    </div>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(confirmation);
            
            // Force reflow and start animations
            confirmation.offsetHeight;
            
            // Start animations with slight delay
            requestAnimationFrame(() => {
                confirmation.style.animation = 'fadeIn 0.3s ease-out forwards';
                const content = confirmation.querySelector('.confirmation-content');
                content.style.animation = 'popIn 0.3s ease-out forwards';
            });
            
            // Store callbacks globally
            window.confirmCallback = onConfirm;
            window.cancelCallback = onCancel;
        }

        // Confirm action
        function confirmAction() {
            const confirmation = document.querySelector('.confirmation-dialog');
            if (confirmation) {
                confirmation.remove();
            }
            
            if (window.confirmCallback) {
                window.confirmCallback();
                window.confirmCallback = null;
            }
        }

        // Cancel action
        function cancelAction() {
            const confirmation = document.querySelector('.confirmation-dialog');
            if (confirmation) {
                confirmation.remove();
            }
            
            if (window.cancelCallback) {
                window.cancelCallback();
                window.cancelCallback = null;
            }
        }

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadTreeData();
            loadCategories();
            loadLaboratories();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
            // Overlay click to close forms
            document.getElementById('overlay').addEventListener('click', function() {
                const categoryCard = document.getElementById('category-form-card');
                const laboratoryCard = document.getElementById('laboratory-form-card');
                const deviceListCard = document.getElementById('device-list-card');
                const deviceFormCard = document.getElementById('device-form-card');
                
                if (categoryCard.style.display === 'block') {
                    toggleCategoryForm();
                } else if (laboratoryCard.style.display === 'block') {
                    toggleLaboratoryForm();
                } else if (deviceListCard.style.display === 'block') {
                    toggleDeviceList();
                } else if (deviceFormCard.style.display === 'block') {
                    toggleDeviceForm();
                }
            });

            // Category form
            document.getElementById('category-form').addEventListener('submit', function(e) {
                e.preventDefault();
                addCategory();
            });

            // Laboratory form
            document.getElementById('laboratory-form').addEventListener('submit', function(e) {
                e.preventDefault();
                addLaboratory();
            });

            // Device form
            document.getElementById('device-form').addEventListener('submit', function(e) {
                e.preventDefault();
                addDevice();
            });

            // Device laboratory selection change
            document.getElementById('device-lab').addEventListener('change', function() {
                updateDeviceOrder();
            });



            // Image upload functionality
            setupImageUpload();

            // Content form
            document.getElementById('content-form').addEventListener('submit', function(e) {
                e.preventDefault();
                saveContent(e);
            });
            
            // Content image upload functionality
            setupContentImageUpload();
            
            // Initialize content form state
            toggleContentInput();
            
            // Load content button
            document.getElementById('load-content-btn').addEventListener('click', function() {
                loadLabContent();
            });

            // Delete content button
            document.getElementById('delete-content-btn').addEventListener('click', function() {
                deleteContent();
            });

            // Modal close
            document.querySelector('.close').addEventListener('click', function() {
                closeModal();
            });

            // Close modal when clicking outside
            window.addEventListener('click', function(e) {
                if (e.target === document.getElementById('laboratoryModal')) {
                    closeModal();
                }
            });

            // ESC key to close forms
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const categoryCard = document.getElementById('category-form-card');
                    const laboratoryCard = document.getElementById('laboratory-form-card');
                    const deviceListCard = document.getElementById('device-list-card');
                    const deviceFormCard = document.getElementById('device-form-card');
                    
                    if (categoryCard.style.display === 'block') {
                        toggleCategoryForm();
                    } else if (laboratoryCard.style.display === 'block') {
                        toggleLaboratoryForm();
                    } else if (deviceListCard.style.display === 'block') {
                        toggleDeviceList();
                    } else if (deviceFormCard.style.display === 'block') {
                        toggleDeviceForm();
                    }
                }
            });
        }

        // Load tree data
        async function loadTreeData() {
            try {
                const response = await fetch('api_tree.php');
                const data = await response.json();
                
                if (data.success) {
                    treeData = data.data;
                    renderTree();
                    updateStats();
                } else {
                    showNotification('Ağaç yapısı yüklenirken hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Ağaç yapısı yüklenirken hata: ' + error.message, 'error');
            }
        }

        // Load categories for dropdown
        async function loadCategories() {
            try {
                const response = await fetch('api_categories.php');
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                
                if (data.success) {
                    categories = data.data;
                    populateCategoryDropdown();
                } else {
                    showNotification('Kategoriler yüklenirken hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Kategoriler yüklenirken hata: ' + error.message, 'error');
            }
        }

        // Populate category dropdown
        function populateCategoryDropdown() {
            const select = document.getElementById('laboratory-category');
            const deleteSelect = document.getElementById('delete-category-select');
            
            if (!select || !deleteSelect) {
                showNotification('Dropdown elementleri bulunamadı!', 'error');
                return;
            }
            
            select.innerHTML = '<option value="">Kategori seçin...</option>';
            deleteSelect.innerHTML = '<option value="">Kategori seçin...</option>';
            
            if (categories && categories.length > 0) {
                categories.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                    
                    const deleteOption = document.createElement('option');
                    deleteOption.value = category.id;
                    deleteOption.textContent = category.name;
                    deleteSelect.appendChild(deleteOption);
                });
            } else {
                showNotification('Kategori verisi bulunamadı', 'warning');
            }
        }

        // Load laboratories for device form dropdown
        async function loadLaboratories() {
            try {
                const response = await fetch('api_laboratories.php');
                const data = await response.json();
                
                if (data.success) {
                    populateLaboratoryDropdown(data.data);
                } else {
                    showNotification('Laboratuvarlar yüklenirken hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Laboratuvarlar yüklenirken hata: ' + error.message, 'error');
            }
        }

        // Populate laboratory dropdown for device form
        function populateLaboratoryDropdown(laboratories) {
            const deviceSelect = document.getElementById('device-lab');
            const contentSelect = document.getElementById('content-lab');
            const deleteSelect = document.getElementById('delete-laboratory-select');
            
            // Device form dropdown
            deviceSelect.innerHTML = '<option value="">Laboratuvar seçin...</option>';
            
            // Content form dropdown  
            contentSelect.innerHTML = '<option value="">Laboratuvar seçin...</option>';
            
            // Delete form dropdown
            deleteSelect.innerHTML = '<option value="">Laboratuvar seçin...</option>';
            
            laboratories.forEach(lab => {
                // Device form option
                const deviceOption = document.createElement('option');
                deviceOption.value = lab.id;
                deviceOption.textContent = lab.name + ' (' + (lab.category_name || 'Kategori yok') + ')';
                deviceSelect.appendChild(deviceOption);
                
                // Content form option
                const contentOption = document.createElement('option');
                contentOption.value = lab.id;
                contentOption.textContent = lab.name + ' (' + (lab.category_name || 'Kategori yok') + ')';
                contentSelect.appendChild(contentOption);
                
                // Delete form option
                const deleteOption = document.createElement('option');
                deleteOption.value = lab.id;
                deleteOption.textContent = lab.name + ' (' + (lab.category_name || 'Kategori yok') + ')';
                deleteSelect.appendChild(deleteOption);
            });
        }

        // Get device count for a specific laboratory
        async function getDeviceCount(labId) {
            try {
                const response = await fetch(`api_devices.php?lab_id=${labId}`);
                const data = await response.json();
                
                if (data.success) {
                    return data.devices ? data.devices.length : 0;
                } else {
                    return 0;
                }
            } catch (error) {
                return 0;
            }
        }

        // Update device order automatically when laboratory is selected
        async function updateDeviceOrder() {
            const labSelect = document.getElementById('device-lab');
            const orderInput = document.getElementById('device-order');
            
            if (labSelect.value) {
                const deviceCount = await getDeviceCount(labSelect.value);
                orderInput.value = deviceCount;
            } else {
                orderInput.value = 0;
            }
        }

        // Render tree structure
        function renderTree() {
            const container = document.getElementById('tree-container');
            container.innerHTML = '';

            treeData.forEach(category => {
                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'tree-item category';
                categoryDiv.innerHTML = `
                    <i class="fas fa-chevron-right toggle-icon" data-category-id="${category.id}"></i>
                    <i class="fas fa-folder-open"></i>
                    <span>${category.name}</span>
                `;

                const laboratoriesContainer = document.createElement('div');
                laboratoriesContainer.className = 'laboratories-container';
                laboratoriesContainer.id = `labs-${category.id}`;

                if (category.laboratories && category.laboratories.length > 0) {
                    category.laboratories.forEach(lab => {
                        const labDiv = document.createElement('div');
                        labDiv.className = 'tree-item laboratory';
                        labDiv.innerHTML = `
                            <i class="fas fa-flask"></i>
                            <span>${lab.name}</span>
                        `;
                        labDiv.addEventListener('click', () => openLaboratory(lab));
                        laboratoriesContainer.appendChild(labDiv);
                    });
                }

                container.appendChild(categoryDiv);
                container.appendChild(laboratoriesContainer);

                // Add click event for category toggle (icon)
                const toggleIcon = categoryDiv.querySelector('.toggle-icon');
                toggleIcon.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleCategory(category.id);
                });

                // Add click event for category name (whole category row)
                categoryDiv.addEventListener('click', (e) => {
                    // Don't trigger if clicking on toggle icon
                    if (e.target.classList.contains('toggle-icon')) {
                        return;
                    }
                    toggleCategory(category.id);
                });
            });
        }

        // Toggle category expansion
        function toggleCategory(categoryId) {
            const container = document.getElementById(`labs-${categoryId}`);
            const toggleIcon = document.querySelector(`[data-category-id="${categoryId}"]`);
            const folderIcon = toggleIcon.nextElementSibling;
            
            if (container.classList.contains('expanded')) {
                container.classList.remove('expanded');
                toggleIcon.classList.remove('expanded');
                toggleIcon.className = 'fas fa-chevron-right toggle-icon';
                folderIcon.className = 'fas fa-folder';
            } else {
                container.classList.add('expanded');
                toggleIcon.classList.add('expanded');
                toggleIcon.className = 'fas fa-chevron-down toggle-icon';
                folderIcon.className = 'fas fa-folder-open';
            }
        }

        // Open laboratory in modal
        function openLaboratory(laboratory) {
            // Yeni detay sayfasında aç
            window.open('../lab_detail.php?id=' + laboratory.id, '_blank');
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('laboratoryModal');
            
            modal.style.display = 'none';
        }

        // Add category
        async function addCategory() {
            const formData = {
                name: document.getElementById('category-name').value
            };

            try {
                const response = await fetch('api_categories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                
                if (data.success) {
                    showNotification('Kategori başarıyla eklendi!', 'success');
                    document.getElementById('category-form').reset();
                    loadTreeData();
                    loadCategories();
                    // Auto close popup
                    toggleCategoryForm();
                } else {
                    showNotification('Hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Kategori eklenirken hata: ' + error.message, 'error');
            }
        }

        // Add laboratory
        async function addLaboratory() {
            const formData = {
                name: document.getElementById('laboratory-name').value,
                category_id: document.getElementById('laboratory-category').value
            };

            try {
                const response = await fetch('api_laboratories.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                
                if (data.success) {
                    let message = data.message;
                    if (data.data && data.data.upload_folder) {
                        message += ' - Kategori: ' + data.data.upload_folder;
                    }
                    showNotification(message, 'success');
                    document.getElementById('laboratory-form').reset();
                    loadTreeData();
                    loadLaboratories();
                    updateStats();
                    // Auto close popup
                    toggleLaboratoryForm();
                } else {
                    showNotification('Hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Laboratuvar eklenirken hata: ' + error.message, 'error');
            }
        }

        // Add device
        async function addDevice() {
            const formData = {
                lab_id: document.getElementById('device-lab').value,
                device_name: document.getElementById('device-name').value,
                device_model: document.getElementById('device-model').value,
                device_count: parseInt(document.getElementById('device-count').value),
                purpose: document.getElementById('device-purpose').value,
                image_url: document.getElementById('uploaded-image-url').value || null,
                order_num: parseInt(document.getElementById('device-order').value)
            };

            try {
                const response = await fetch('api_devices.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(formData)
                });

                const data = await response.json();
                
                if (data.success) {
                    showNotification('Cihaz başarıyla eklendi!', 'success');
                    document.getElementById('device-form').reset();
                    // Update order after successful addition
                    updateDeviceOrder();
                } else {
                    showNotification('Hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('Cihaz eklenirken hata: ' + error.message, 'error');
            }
        }

        // Setup image upload functionality
        function setupImageUpload() {
            const uploadArea = document.getElementById('image-upload-area');
            const fileInput = document.getElementById('device-image');
            const imagePreview = document.getElementById('image-preview');
            const previewImg = document.getElementById('preview-img');
            const removeBtn = document.getElementById('remove-image');
            const uploadedUrlInput = document.getElementById('uploaded-image-url');

            // Click to upload
            uploadArea.addEventListener('click', () => fileInput.click());

            // File selection
            fileInput.addEventListener('change', handleFileSelect);

            // Drag and drop
            uploadArea.addEventListener('dragover', handleDragOver);
            uploadArea.addEventListener('dragleave', handleDragLeave);
            uploadArea.addEventListener('drop', handleDrop);

            // Remove image
            removeBtn.addEventListener('click', removeImage);

            function handleFileSelect(e) {
                const file = e.target.files[0];
                if (file) {
                    processFile(file);
                }
            }

            function handleDragOver(e) {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            }

            function handleDragLeave(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
            }

            function handleDrop(e) {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    processFile(files[0]);
                }
            }

            function processFile(file) {
                // File validation
                if (!file.type.startsWith('image/')) {
                    showNotification('Lütfen sadece resim dosyası seçin!', 'warning');
                    return;
                }

                if (file.size > 5 * 1024 * 1024) {
                    showNotification('Dosya boyutu 5MB\'dan büyük olamaz!', 'warning');
                    return;
                }

                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewImg.src = e.target.result;
                    imagePreview.style.display = 'block';
                    uploadArea.style.display = 'none';
                };
                reader.readAsDataURL(file);

                // Upload file
                uploadImage(file);
            }

            function uploadImage(file) {
                const labId = document.getElementById('device-lab').value;
                if (!labId) {
                    showNotification('Önce laboratuvar seçin!', 'warning');
                    return;
                }

                const formData = new FormData();
                formData.append('image', file);
                formData.append('lab_id', labId);

                // Show progress
                showUploadProgress();

                fetch('api_upload_image.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    hideUploadProgress();
                    
                    if (data.success) {
                        uploadedUrlInput.value = data.data.url;
                        showNotification('Resim başarıyla yüklendi!', 'success');
                    } else {
                        showNotification('Resim yüklenirken hata: ' + data.message, 'error');
                        removeImage();
                    }
                })
                .catch(error => {
                    hideUploadProgress();
                    showNotification('Resim yüklenirken hata: ' + error.message, 'error');
                    removeImage();
                });
            }

            function removeImage() {
                fileInput.value = '';
                uploadedUrlInput.value = '';
                imagePreview.style.display = 'none';
                uploadArea.style.display = 'block';
            }

            function showUploadProgress() {
                const progress = document.getElementById('upload-progress');
                progress.style.display = 'block';
                
                // Progress animation
                setTimeout(() => {
                    const fill = progress.querySelector('.upload-progress-fill');
                    fill.style.width = '100%';
                }, 100);
            }

            function hideUploadProgress() {
                const progress = document.getElementById('upload-progress');
                progress.style.display = 'none';
                
                // Reset progress bar
                const fill = progress.querySelector('.upload-progress-fill');
                fill.style.width = '0%';
            }
        }

        // Toggle between text and image input based on content type
        function toggleContentInput() {
            const contentType = document.getElementById('content-type').value;
            const textGroup = document.getElementById('text-input-group');
            const imageGroup = document.getElementById('image-input-group');
            const textArea = document.getElementById('content-value');
            const textLabel = textGroup.querySelector('label');
            
            if (contentType === 'main_image') {
                textGroup.style.display = 'none';
                imageGroup.style.display = 'block';
                // Remove required attribute when hidden
                textArea.removeAttribute('required');
            } else {
                textGroup.style.display = 'block';
                imageGroup.style.display = 'none';
                // Add required attribute when visible
                textArea.setAttribute('required', 'required');
                
                // Update label based on content type
                if (contentType === 'lab_title') {
                    textLabel.innerHTML = '<i class="fas fa-heading"></i> Laboratuvar Başlığı';
                    textArea.placeholder = 'Laboratuvar başlığını girin...';
                } else if (contentType === 'about_text') {
                    textLabel.innerHTML = '<i class="fas fa-align-left"></i> Katalog Hakkında Metni (Kısa)';
                    textArea.placeholder = 'Katalog sayfasında görünecek kısa açıklama metnini girin...';
                } else if (contentType === 'detail_about_text') {
                    textLabel.innerHTML = '<i class="fas fa-book-open"></i> Detay Sayfası Hakkında Metni (Uzun)';
                    textArea.placeholder = 'Detay sayfasında görünecek uzun ve detaylı açıklama metnini girin...';
                }
            }
        }

        // Setup content image upload functionality
        function setupContentImageUpload() {
            const uploadArea = document.getElementById('content-image-upload-area');
            const fileInput = document.getElementById('content-image');
            const preview = document.getElementById('content-image-preview');
            const previewImg = document.getElementById('content-preview-img');
            const removeBtn = document.getElementById('remove-content-image');
            const progress = document.getElementById('content-upload-progress');
            const hiddenInput = document.getElementById('uploaded-content-image-url');

            // Click to select file
            uploadArea.addEventListener('click', () => fileInput.click());

            // File selection
            fileInput.addEventListener('change', (e) => {
                if (e.target.files.length > 0) {
                    processContentFile(e.target.files[0]);
                }
            });

            // Drag and drop
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', () => {
                uploadArea.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.classList.remove('dragover');
                if (e.dataTransfer.files.length > 0) {
                    processContentFile(e.dataTransfer.files[0]);
                }
            });

            // Remove image
            removeBtn.addEventListener('click', () => {
                hiddenInput.value = '';
                preview.style.display = 'none';
                uploadArea.style.display = 'block';
                fileInput.value = '';
            });
        }

        // Process content image file
        function processContentFile(file) {
            // File type validation
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                showNotification('Sadece resim dosyaları kabul edilir (JPG, PNG, GIF, WebP)', 'warning');
                return;
            }

            // File size validation (5MB)
            if (file.size > 5 * 1024 * 1024) {
                showNotification('Dosya boyutu 5MB\'dan büyük olamaz', 'warning');
                return;
            }

            // Show preview
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('content-preview-img').src = e.target.result;
                document.getElementById('content-image-preview').style.display = 'block';
                document.getElementById('content-image-upload-area').style.display = 'none';
            };
            reader.readAsDataURL(file);

            // Upload file
            uploadContentImage(file);
        }

        // Upload content image
        async function uploadContentImage(file) {
            const labId = document.getElementById('content-lab').value;
            if (!labId) {
                showNotification('Önce laboratuvar seçin!', 'warning');
                return;
            }

            const formData = new FormData();
            formData.append('image', file);
            formData.append('lab_id', labId);

            showContentUploadProgress();

            try {
                const response = await fetch('api_upload_image.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('uploaded-content-image-url').value = data.data.url;
                    hideContentUploadProgress();
                    showNotification('Resim başarıyla yüklendi!', 'success');
                } else {
                    hideContentUploadProgress();
                    showNotification('Resim yüklenirken hata: ' + data.message, 'error');
                }
            } catch (error) {
                hideContentUploadProgress();
                showNotification('Resim yüklenirken hata: ' + error.message, 'error');
            }
        }

        // Show content upload progress
        function showContentUploadProgress() {
            document.getElementById('content-upload-progress').style.display = 'block';
        }

        // Hide content upload progress
        function hideContentUploadProgress() {
            document.getElementById('content-upload-progress').style.display = 'none';
        }

        // Save laboratory content
        async function saveContent(e) {
            e.preventDefault(); // Prevent default form submission
            
            const labId = document.getElementById('content-lab').value;
            const contentType = document.getElementById('content-type').value;
            let contentValue = '';
            const altText = document.getElementById('content-alt').value;
            
            if (!labId || !contentType) {
                showNotification('Laboratuvar ve içerik tipi seçin!', 'warning');
                return;
            }

            // Get content value based on type
            if (contentType === 'main_image') {
                contentValue = document.getElementById('uploaded-content-image-url').value;
                if (!contentValue) {
                    showNotification('Lütfen bir resim yükleyin!', 'warning');
                    return;
                }
            } else {
                contentValue = document.getElementById('content-value').value;
                if (!contentValue.trim()) {
                    showNotification('İçerik metnini girin!', 'warning');
                    return;
                }
            }

            try {
                const response = await fetch('api_lab_contents.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        lab_id: labId,
                        content_type: contentType,
                        content_value: contentValue,
                        alt_text: altText
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    showNotification('İçerik başarıyla kaydedildi!', 'success');
                    document.getElementById('content-form').reset();
                    // Reset image preview
                    document.getElementById('content-image-preview').style.display = 'none';
                    document.getElementById('content-image-upload-area').style.display = 'block';
                    document.getElementById('uploaded-content-image-url').value = '';
                    // Reset content type to trigger toggle
                    document.getElementById('content-type').value = '';
                    toggleContentInput();
                } else {
                    showNotification('Hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('İçerik kaydedilirken hata: ' + error.message, 'error');
            }
        }

        // Load laboratory content
        async function loadLabContent() {
            const labId = document.getElementById('content-lab').value;
            if (!labId) {
                showNotification('Önce bir laboratuvar seçin!', 'warning');
                return;
            }

            try {
                const response = await fetch(`api_lab_contents.php?lab_id=${labId}`);
                const data = await response.json();
                
                if (data.success) {
                    const contents = data.data;
                    
                    // Form alanlarını doldur
                    if (contents.main_image) {
                        document.getElementById('content-type').value = 'main_image';
                        document.getElementById('uploaded-content-image-url').value = contents.main_image.content_value;
                        document.getElementById('content-alt').value = contents.main_image.alt_text || '';
                        toggleContentInput();
                        
                        // Resim önizlemesini göster
                        document.getElementById('content-preview-img').src = contents.main_image.content_value;
                        document.getElementById('content-image-preview').style.display = 'block';
                        document.getElementById('content-image-upload-area').style.display = 'none';
                    } else if (contents.lab_title) {
                        document.getElementById('content-type').value = 'lab_title';
                        document.getElementById('content-value').value = contents.lab_title.content_value;
                        document.getElementById('content-alt').value = contents.lab_title.alt_text || '';
                        toggleContentInput();
                    } else if (contents.about_text) {
                        document.getElementById('content-type').value = 'about_text';
                        document.getElementById('content-value').value = contents.about_text.content_value;
                        document.getElementById('content-alt').value = contents.about_text.alt_text || '';
                        toggleContentInput();
                    } else if (contents.detail_about_text) {
                        document.getElementById('content-type').value = 'detail_about_text';
                        document.getElementById('content-value').value = contents.detail_about_text.content_value;
                        document.getElementById('content-alt').value = contents.detail_about_text.alt_text || '';
                        toggleContentInput();
                    } else {
                        showNotification('Bu laboratuvar için henüz içerik eklenmemiş.', 'info');
                    }
                } else {
                    showNotification('Hata: ' + data.message, 'error');
                }
            } catch (error) {
                showNotification('İçerikler yüklenirken hata: ' + error.message, 'error');
            }
        }

        // Delete laboratory content
        async function deleteContent() {
            const labId = document.getElementById('content-lab').value;
            const contentType = document.getElementById('content-type').value;
            
            if (!labId || !contentType) {
                showNotification('Laboratuvar ve içerik tipi seçmelisiniz!', 'warning');
                return;
            }

            showConfirmation(
                'Bu içeriği silmek istediğinizden emin misiniz?',
                async () => {
                    try {
                        const response = await fetch('api_lab_contents.php', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                lab_id: labId,
                                content_type: contentType
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            showNotification('İçerik başarıyla silindi!', 'success');
                            document.getElementById('content-form').reset();
                            // Reset image preview
                            document.getElementById('content-image-preview').style.display = 'none';
                            document.getElementById('content-image-upload-area').style.display = 'block';
                            document.getElementById('uploaded-content-image-url').value = '';
                        } else {
                            showNotification('Hata: ' + data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('İçerik silinirken hata: ' + error.message, 'error');
                    }
                }
            );
        }

        // Update statistics
        function updateStats() {
            const categoriesCount = treeData.length;
            const laboratoriesCount = treeData.reduce((total, category) => {
                return total + (category.laboratories ? category.laboratories.length : 0);
            }, 0);

            document.getElementById('categories-count').textContent = categoriesCount;
            document.getElementById('laboratories-count').textContent = laboratoriesCount;
        }

        // Toggle category form
        function toggleCategoryForm() {
            const categoryCard = document.getElementById('category-form-card');
            const laboratoryCard = document.getElementById('laboratory-form-card');
            const deviceListCard = document.getElementById('device-list-card');
            const categoryBtn = document.getElementById('category-toggle-btn');
            const overlay = document.getElementById('overlay');
            
            if (categoryCard.style.display === 'none') {
                // Show category form, hide other forms
                overlay.style.display = 'block';
                categoryCard.style.display = 'block';
                laboratoryCard.style.display = 'none';
                deviceListCard.style.display = 'none';
                const deviceFormCard = document.getElementById('device-form-card');
                if (deviceFormCard) deviceFormCard.style.display = 'none';
                categoryBtn.classList.add('active');
                document.getElementById('laboratory-toggle-btn').classList.remove('active');
                document.getElementById('device-toggle-btn').classList.remove('active');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
                
                // Reset to add tab when opening
                switchCategoryTab('add');
            } else {
                // Hide category form
                categoryCard.style.display = 'none';
                overlay.style.display = 'none';
                categoryBtn.classList.remove('active');
                document.body.style.overflow = 'auto'; // Restore background scroll
            }
        }

        // Switch category tab
        function switchCategoryTab(tab) {
            const addTab = document.getElementById('add-category-tab');
            const deleteTab = document.getElementById('delete-category-tab');
            const addTabBtn = document.getElementById('add-tab-btn');
            const deleteTabBtn = document.getElementById('delete-tab-btn');
            
            if (tab === 'add') {
                addTab.classList.add('active');
                deleteTab.classList.remove('active');
                addTabBtn.classList.add('active');
                deleteTabBtn.classList.remove('active');
            } else if (tab === 'delete') {
                addTab.classList.remove('active');
                deleteTab.classList.add('active');
                addTabBtn.classList.remove('active');
                deleteTabBtn.classList.add('active');
            }
        }

        // Switch laboratory tab
        function switchLaboratoryTab(tab) {
            const addTab = document.getElementById('add-laboratory-tab');
            const deleteLabTab = document.getElementById('delete-laboratory-tab');
            const addTabBtn = document.getElementById('lab-add-tab-btn');
            const deleteLabTabBtn = document.getElementById('lab-delete-tab-btn');
            
            // Remove active from all tabs and buttons
            addTab.classList.remove('active');
            deleteLabTab.classList.remove('active');
            addTabBtn.classList.remove('active');
            deleteLabTabBtn.classList.remove('active');
            
            if (tab === 'add') {
                addTab.classList.add('active');
                addTabBtn.classList.add('active');
            } else if (tab === 'delete-lab') {
                deleteLabTab.classList.add('active');
                deleteLabTabBtn.classList.add('active');
            }
        }

        // Toggle laboratory form
        function toggleLaboratoryForm() {
            const laboratoryCard = document.getElementById('laboratory-form-card');
            const categoryCard = document.getElementById('category-form-card');
            const categoryBtn = document.getElementById('category-toggle-btn');
            const laboratoryBtn = document.getElementById('laboratory-toggle-btn');
            const overlay = document.getElementById('overlay');
            
            if (laboratoryCard.style.display === 'none') {
                // Show laboratory form, hide other forms
                overlay.style.display = 'block';
                laboratoryCard.style.display = 'block';
                categoryCard.style.display = 'none';
                deviceListCard.style.display = 'none';
                const deviceFormCard = document.getElementById('device-form-card');
                if (deviceFormCard) deviceFormCard.style.display = 'none';
                laboratoryBtn.classList.add('active');
                categoryBtn.classList.remove('active');
                document.getElementById('device-toggle-btn').classList.remove('active');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
                
                // Reset to add tab when opening
                switchLaboratoryTab('add');
            } else {
                // Hide laboratory form
                laboratoryCard.style.display = 'none';
                overlay.style.display = 'none';
                laboratoryBtn.classList.remove('active');
                document.body.style.overflow = 'auto'; // Restore background scroll
            }
        }

        // Toggle device list
        function toggleDeviceList() {
            const deviceListCard = document.getElementById('device-list-card');
            const categoryCard = document.getElementById('category-form-card');
            const laboratoryCard = document.getElementById('laboratory-form-card');
            const categoryBtn = document.getElementById('category-toggle-btn');
            const laboratoryBtn = document.getElementById('laboratory-toggle-btn');
            const deviceBtn = document.getElementById('device-toggle-btn');
            const overlay = document.getElementById('overlay');
            
            if (deviceListCard.style.display === 'none') {
                // Show device list, hide other forms
                overlay.style.display = 'block';
                deviceListCard.style.display = 'block';
                categoryCard.style.display = 'none';
                laboratoryCard.style.display = 'none';
                const deviceFormCard = document.getElementById('device-form-card');
                if (deviceFormCard) deviceFormCard.style.display = 'none';
                deviceBtn.classList.add('active');
                categoryBtn.classList.remove('active');
                laboratoryBtn.classList.remove('active');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
                
                // Populate laboratory dropdown
                populateDeviceListLabDropdown();
            } else {
                // Hide device list
                deviceListCard.style.display = 'none';
                overlay.style.display = 'none';
                deviceBtn.classList.remove('active');
                document.body.style.overflow = 'auto'; // Restore background scroll
            }
        }

        // Toggle device form (for adding new devices)
        function toggleDeviceForm() {
            const deviceFormCard = document.getElementById('device-form-card');
            const categoryCard = document.getElementById('category-form-card');
            const laboratoryCard = document.getElementById('laboratory-form-card');
            const deviceListCard = document.getElementById('device-list-card');
            const categoryBtn = document.getElementById('category-toggle-btn');
            const laboratoryBtn = document.getElementById('laboratory-toggle-btn');
            const deviceBtn = document.getElementById('device-toggle-btn');
            const overlay = document.getElementById('overlay');
            
            if (deviceFormCard.style.display === 'none') {
                // Show device form, hide other forms
                overlay.style.display = 'block';
                deviceFormCard.style.display = 'block';
                categoryCard.style.display = 'none';
                laboratoryCard.style.display = 'none';
                deviceListCard.style.display = 'none';
                deviceBtn.classList.add('active');
                categoryBtn.classList.remove('active');
                laboratoryBtn.classList.remove('active');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
                
                // Update device order when opening
                setTimeout(() => {
                    updateDeviceOrder();
                }, 100);
            } else {
                // Hide device form
                deviceFormCard.style.display = 'none';
                overlay.style.display = 'none';
                deviceBtn.classList.remove('active');
                document.body.style.overflow = 'auto'; // Restore background scroll
            }
        }

        // Populate laboratory dropdown for device list
        function populateDeviceListLabDropdown() {
            const labSelect = document.getElementById('device-list-lab-select');
            labSelect.innerHTML = '<option value="">Laboratuvar seçin...</option>';
            
            fetch('../Dasboard/api_laboratories.php?action=get_all')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        data.laboratories.forEach(lab => {
                            const option = document.createElement('option');
                            option.value = lab.id;
                            option.textContent = lab.name;
                            labSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Laboratuvarlar yüklenirken hata:', error);
                    showNotification('Laboratuvarlar yüklenirken hata oluştu', 'error');
                });
        }

        // Load device list for selected laboratory
        function loadDeviceList() {
            const labId = document.getElementById('device-list-lab-select').value;
            const contentDiv = document.getElementById('device-list-content');
            
            if (!labId) {
                contentDiv.innerHTML = `
                    <div class="device-list-placeholder">
                        <i class="fas fa-microchip"></i>
                        <p>Laboratuvar seçin ve cihazları görüntüleyin</p>
                    </div>
                `;
                return;
            }
            
            // Show loading
            contentDiv.innerHTML = `
                <div class="device-list-loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Cihazlar yükleniyor...</p>
                </div>
            `;
            
            // Debug için console.log ekle
            console.log('Loading devices for lab:', labId);
            
            fetch(`../Dasboard/api_devices.php?action=get_by_lab&lab_id=${labId}`)
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Received data:', data);
                    if (data.success) {
                        if (data.devices && data.devices.length > 0) {
                            displayDeviceList(data.devices);
                        } else {
                            contentDiv.innerHTML = `
                                <div class="device-list-empty">
                                    <i class="fas fa-microchip"></i>
                                    <p>Bu laboratuvarda henüz cihaz bulunmuyor</p>
                                    <small>Yeni cihaz eklemek için "Yeni Cihaz Ekle" formunu kullanın</small>
                                </div>
                            `;
                        }
                    } else {
                        contentDiv.innerHTML = `
                            <div class="device-list-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <p>${data.message || 'Cihazlar yüklenirken hata oluştu'}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Cihazlar yüklenirken hata:', error);
                    contentDiv.innerHTML = `
                        <div class="device-list-error">
                            <i class="fas fa-exclamation-triangle"></i>
                            <p>Cihazlar yüklenirken hata oluştu: ${error.message}</p>
                        </div>
                    `;
                });
        }

        // Delete device
        async function deleteDevice(deviceId) {
            showConfirmation(
                'Bu cihazı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!',
                async () => {
                    try {
                        const response = await fetch('api_devices.php', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: deviceId
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            showNotification('Cihaz başarıyla silindi!', 'success');
                            // Reload device list
                            const labId = document.getElementById('device-list-lab-select').value;
                            if (labId) {
                                loadDeviceList();
                            }
                        } else {
                            showNotification('Hata: ' + data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('Cihaz silinirken hata: ' + error.message, 'error');
                    }
                }
            );
        }

        // Display device list
        function displayDeviceList(devices) {
            const contentDiv = document.getElementById('device-list-content');
            
            let html = '<div class="device-list-grid">';
            devices.forEach(device => {
                html += '<div class="device-item">';
                html += '<div class="device-item-header">';
                html += '<button onclick="deleteDevice(' + device.id + ')" class="device-delete-btn" title="Cihazı Sil">';
                html += '<i class="fas fa-trash"></i>';
                html += '</button>';
                html += '</div>';
                html += '<div class="device-image">';
                
                if (device.image_url) {
                    html += '<img src="../' + device.image_url + '" alt="' + device.device_name + '">';
                } else {
                    html += '<div class="device-no-image"><i class="fas fa-microchip"></i></div>';
                }
                
                html += '</div>';
                html += '<div class="device-info">';
                html += '<h5>' + device.device_name + '</h5>';
                
                if (device.device_model) {
                    html += '<p class="device-model">Model: ' + device.device_model + '</p>';
                }
                
                html += '<p class="device-count">Sayı: ' + device.device_count + '</p>';
                
                if (device.purpose) {
                    html += '<p class="device-purpose">' + device.purpose + '</p>';
                }
                
                html += '<p class="device-order">Sıra: ' + device.order_num + '</p>';
                html += '</div>';
                html += '</div>';
            });
            html += '</div>';
            
            contentDiv.innerHTML = html;
        }

        // Delete category
        async function deleteCategory() {
            const categoryId = document.getElementById('delete-category-select').value;
            
            if (!categoryId) {
                showNotification('Lütfen silinecek kategoriyi seçin!', 'warning');
                return;
            }
            
            showConfirmation(
                'Bu kategoriyi silmek istediğinizden emin misiniz? Bu işlem geri alınamaz ve tüm laboratuvarlar da silinecektir!',
                async () => {
                    try {
                        const response = await fetch('api_categories.php', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: categoryId
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            showNotification('Kategori başarıyla silindi!', 'success');
                            document.getElementById('delete-category-select').value = '';
                            loadTreeData();
                            loadCategories();
                            updateStats();
                            // Form kapanmaz, sadece dropdown temizlenir
                        } else {
                            showNotification('Hata: ' + data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('Kategori silinirken hata: ' + error.message, 'error');
                    }
                }
            );
        }



        // Delete laboratory
        async function deleteLaboratory() {
            const laboratoryId = document.getElementById('delete-laboratory-select').value;
            
            if (!laboratoryId) {
                showNotification('Lütfen silinecek laboratuvarı seçin!', 'warning');
                return;
            }
            
            showConfirmation(
                'Bu laboratuvarı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!',
                async () => {
                    try {
                        const response = await fetch('api_laboratories.php', {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                id: laboratoryId
                            })
                        });

                        const data = await response.json();
                        
                        if (data.success) {
                            showNotification('Laboratuvar başarıyla silindi!', 'success');
                            document.getElementById('delete-laboratory-select').value = '';
                            loadTreeData();
                            loadLaboratories();
                            updateStats();
                        } else {
                            showNotification('Hata: ' + data.message, 'error');
                        }
                    } catch (error) {
                        showNotification('Laboratuvar silinirken hata: ' + error.message, 'error');
                    }
                }
            );
        }

        // Logout function
        function logout() {
            showConfirmation(
                'Çıkış yapmak istediğinizden emin misiniz?',
                () => {
                    fetch('logout_ajax.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '../iamodinson.php?logout=1';
                        } else {
                            showNotification('Çıkış yapılırken hata oluştu: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        showNotification('Çıkış yapılırken hata: ' + error.message, 'error');
                        window.location.href = '../iamodinson.php?logout=1';
                    });
                }
            );
        }
    </script>
</body>
</html>
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
    <!-- NOT: Laboratuvarlar artık tıklanabilir değil, sadece eklenip eklenmediği gösteriliyor -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
    <script src="js/dasboard.js"></script>

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
                <a href="../index.html" target="_blank" class="sidebar-catalog-btn">
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
                                <input type="text" id="laboratory-name" name="name" required class="modern-input" placeholder="Laboratuvar adını girin... (Boşluk kullanmayın, alt çizgi _ kullanın)">
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
                                        <small>JPG, PNG, GIF, WebP (Max: 5MB) - Önerilen boyut: 500x500 px</small>
                                    </div>
                                </div>
                                <div class="image-preview" id="image-preview" style="display: none;">
                                    <img id="preview-img" src="" alt="Önizleme">
                                    <div class="image-size-info" id="image-size-info" style="display: none;">
                                        <i class="fas fa-info-circle"></i>
                                        <span id="image-size-text">Boyut: 0x0 px</span>
                                    </div>
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
                            <input type="number" id="device-order" name="order_num" value="1" min="1" class="modern-input" placeholder="Otomatik olarak ayarlanır" readonly>
                            <small class="form-help">Laboratuvar seçildiğinde otomatik olarak mevcut cihaz sayısına göre ayarlanır</small>
                        </div>
                        
                        <div class="form-actions modern-actions">
                            <button type="submit" class="btn btn-success modern-btn" id="device-submit-btn">
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
                            <div class="device-list-controls">
                                <select id="device-list-lab-select" class="modern-select" onchange="loadDeviceList()">
                                    <option value="">Laboratuvar seçin...</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Toplu İşlem Kontrolleri -->
                        <div id="bulk-actions-container" class="bulk-actions-container" style="display: none;">
                            <div class="bulk-actions-header">
                                <div class="bulk-selection-info">
                                    <span id="selected-count">0</span> cihaz seçildi
                                </div>
                                <div class="bulk-actions-buttons">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="selectAllDevices()">
                                        <i class="fas fa-check-square"></i> Tümünü Seç
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllDevices()">
                                        <i class="fas fa-square"></i> Seçimi Kaldır
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteSelectedDevices()">
                                        <i class="fas fa-trash"></i> Seçilenleri Sil
                                    </button>
                                </div>
                            </div>
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
                        <p class="card-subtitle">Laboratuvar başlığı, katalog bilgisi, detay sayfası bilgisi ve ana resmini yönetin</p>
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
                                    <option value="lab_title">📝 Laboratuvar Başlığı (Katalog ve detay sayfasında görünür)</option>
                                    <option value="main_image">🖼️ Ana Resim (Laboratuvar ana görseli)</option>
                                    <option value="catalog_info">📋 Katalog Bilgisi (Katalog sayfasında "Hakkında" bölümü)</option>
                                    <option value="detail_page_info">📄 Detay Sayfası Bilgisi (Laboratuvar detay sayfasında "Hakkında" bölümü)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Text/URL Input (for title and about text) -->
                        <div class="form-group" id="text-input-group">
                            <label for="content-value">
                                <i class="fas fa-align-left"></i> İçerik Metni
                            </label>
                            <textarea id="content-value" name="content_value" rows="4" placeholder="Seçilen içerik tipine göre uygun metni girin..." class="modern-textarea"></textarea>
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

    <!-- Device Edit Modal -->
    <div id="deviceEditModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="device-modal-title">
                    <i class="fas fa-edit"></i> Cihaz Düzenle
                </h3>
                <span class="close" onclick="closeDeviceModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="device-edit-form" class="modern-form">
                    <input type="hidden" id="edit-device-id" name="device_id">
                    <input type="hidden" id="edit-device-order" name="order_num">
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-device-name">
                                <i class="fas fa-microchip"></i> Cihaz Adı/Modeli
                            </label>
                            <input type="text" id="edit-device-name" name="device_name" required class="modern-input" placeholder="Cihaz adını girin...">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-device-model">
                                <i class="fas fa-tag"></i> Model (Opsiyonel)
                            </label>
                            <input type="text" id="edit-device-model" name="device_model" class="modern-input" placeholder="Cihaz modelini girin...">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="edit-device-count">
                                <i class="fas fa-hashtag"></i> Sayısı
                            </label>
                            <input type="number" id="edit-device-count" name="device_count" required min="1" class="modern-input" placeholder="Cihaz sayısını girin...">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-device-lab">
                                <i class="fas fa-flask"></i> Laboratuvar
                            </label>
                            <select id="edit-device-lab" name="lab_id" required class="modern-select">
                                <option value="">Laboratuvar seçin...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-device-purpose">
                            <i class="fas fa-bullseye"></i> Kullanım Amacı
                        </label>
                        <textarea id="edit-device-purpose" name="purpose" rows="3" class="modern-textarea" placeholder="Cihazın kullanım amacını açıklayın..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit-device-image">
                            <i class="fas fa-image"></i> Cihaz Resmi
                        </label>
                        <div class="image-upload-container modern-upload">
                            <input type="file" id="edit-device-image" name="image" accept="image/*" style="display: none;">
                            <div class="image-upload-area" id="edit-device-image-upload-area">
                                <div class="upload-placeholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <p>Cihaz resmi yüklemek için tıklayın veya sürükleyin</p>
                                    <small>JPG, PNG, GIF, WebP (Max: 5MB) - Önerilen boyut: 500x500 px</small>
                                </div>
                            </div>
                            <div class="image-preview" id="edit-device-image-preview" style="display: none;">
                                <img id="edit-device-preview-img" src="" alt="Önizleme">
                                <div class="image-size-info" id="edit-device-image-size-info" style="display: none;">
                                    <i class="fas fa-info-circle"></i>
                                    <span id="edit-device-image-size-text">Boyut: 0x0 px</span>
                                </div>
                                <button type="button" id="remove-edit-device-image" class="btn btn-sm btn-danger modern-btn">
                                    <i class="fas fa-times"></i> Kaldır
                                </button>
                            </div>
                            <div class="upload-progress" id="edit-device-upload-progress" style="display: none;">
                                <div class="upload-progress-bar">
                                    <div class="upload-progress-fill"></div>
                                </div>
                                <small>Yükleniyor...</small>
                            </div>
                            <input type="hidden" id="edit-uploaded-device-image-url" name="uploaded_image_url">
                        </div>
                    </div>
                    
                    <div class="form-actions modern-actions">
                        <button type="submit" class="btn btn-success modern-btn">
                            <i class="fas fa-save"></i> Güncelle
                        </button>
                        <button type="button" class="btn btn-secondary modern-btn" onclick="closeDeviceModal()">
                            <i class="fas fa-times"></i> İptal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
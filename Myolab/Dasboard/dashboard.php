<?php
session_start();

// Kullanıcı giriş yapmamışsa login sayfasına yönlendir
if (!isset($_SESSION['user_id']) || !isset($_SESSION['username'])) {
    header('Location: iamodinson.php');
    exit();
}

// Session hijacking koruması
if ($_SESSION['login_time'] < (time() - (8 * 60 * 60))) { // 8 saat
    session_destroy();
    header('Location: iamodinson.php?expired=1');
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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f6fa;
            color: #2c3e50;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styles */
        .sidebar {
            width: 300px;
            background: #2c3e50;
            color: white;
            padding: 20px 0;
            overflow-y: auto;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
        }

        .sidebar-header {
            padding: 0 20px 20px;
            border-bottom: 1px solid #34495e;
            margin-bottom: 20px;
        }

        .sidebar-header h2 {
            color: #3498db;
            font-size: 24px;
        }

        .tree-item {
            padding: 8px 20px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tree-item:hover {
            background: #34495e;
        }

        .tree-item.category {
            font-weight: bold;
            color: #3498db;
        }

        .tree-item.laboratory {
            padding-left: 40px;
            color: #ecf0f1;
        }

        .tree-item.laboratory:hover {
            background: #34495e;
        }

        .toggle-icon {
            transition: transform 0.3s;
            width: 20px;
            text-align: center;
        }

        .toggle-icon.expanded {
            transform: rotate(90deg);
        }

        .laboratories-container {
            display: none;
            background: #34495e;
        }

        .laboratories-container.expanded {
            display: block;
        }

        /* Main Content Styles */
        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-card h3 {
            color: #3498db;
            font-size: 24px;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: #7f8c8d;
            font-size: 14px;
        }

        .forms-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 30px;
        }

        .form-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .form-card h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3498db;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
        }

        .btn {
            background: #3498db;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            transition: background 0.3s;
        }

        .btn:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
        }

        .btn-success:hover {
            background: #229954;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.8);
        }

        .modal-content {
            background-color: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            height: 90%;
            overflow: hidden;
        }

        .modal-header {
            background: #3498db;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            margin: 0;
        }

        .close {
            color: white;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            height: calc(100% - 60px);
            padding: 0;
        }

        .modal-iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                max-height: 300px;
            }
            
            .forms-container {
                grid-template-columns: 1fr;
            }
            
            .main-content {
                padding: 20px;
            }
        }

        /* Loading Animation */
        .loading {
            text-align: center;
            padding: 20px;
            color: #7f8c8d;
        }

        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #3498db;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-flask"></i> MyoLab</h2>
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
                    <button onclick="logout()" class="btn" style="background: #e74c3c;">
                        <i class="fas fa-sign-out-alt"></i> Çıkış Yap
                    </button>
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
                <!-- Add Category Form -->
                <div class="form-card">
                    <h3><i class="fas fa-folder-plus"></i> Yeni Kategori Ekle</h3>
                    <form id="category-form">
                        <div class="form-group">
                            <label for="category-name">Kategori Adı:</label>
                            <input type="text" id="category-name" name="name" required>
                        </div>
                        <button type="submit" class="btn btn-success">Kategori Ekle</button>
                    </form>
                </div>

                <!-- Add Laboratory Form -->
                <div class="form-card">
                    <h3><i class="fas fa-flask"></i> Yeni Laboratuvar Ekle</h3>
                    <form id="laboratory-form">
                        <div class="form-group">
                            <label for="laboratory-category">Kategori:</label>
                            <select id="laboratory-category" name="category_id" required>
                                <option value="">Kategori seçin...</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="laboratory-name">Laboratuvar Adı:</label>
                            <input type="text" id="laboratory-name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="laboratory-url">Yönlendirme URL:</label>
                            <input type="url" id="laboratory-url" name="redirect_url" required>
                        </div>
                        <button type="submit" class="btn btn-success">Laboratuvar Ekle</button>
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
                <iframe id="modal-iframe" class="modal-iframe" src=""></iframe>
            </div>
        </div>
    </div>

    <script>
        // Global variables
        let treeData = [];
        let categories = [];

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadTreeData();
            loadCategories();
            setupEventListeners();
        });

        // Setup event listeners
        function setupEventListeners() {
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
                    console.error('Ağaç yapısı yüklenirken hata:', data.message);
                }
            } catch (error) {
                console.error('Ağaç yapısı yüklenirken hata:', error);
            }
        }

        // Load categories for dropdown
        async function loadCategories() {
            try {
                const response = await fetch('api_categories.php');
                const data = await response.json();
                
                if (data.success) {
                    categories = data.data;
                    populateCategoryDropdown();
                } else {
                    console.error('Kategoriler yüklenirken hata:', data.message);
                }
            } catch (error) {
                console.error('Kategoriler yüklenirken hata:', error);
            }
        }

        // Populate category dropdown
        function populateCategoryDropdown() {
            const select = document.getElementById('laboratory-category');
            select.innerHTML = '<option value="">Kategori seçin...</option>';
            
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.name;
                select.appendChild(option);
            });
        }

        // Render tree structure
        function renderTree() {
            const container = document.getElementById('tree-container');
            container.innerHTML = '';

            treeData.forEach(category => {
                const categoryDiv = document.createElement('div');
                categoryDiv.className = 'tree-item category';
                categoryDiv.innerHTML = `
                    <i class="fas fa-folder toggle-icon" data-category-id="${category.id}"></i>
                    <i class="fas fa-folder"></i>
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

                // Add click event for category toggle
                const toggleIcon = categoryDiv.querySelector('.toggle-icon');
                toggleIcon.addEventListener('click', (e) => {
                    e.stopPropagation();
                    toggleCategory(category.id);
                });
            });
        }

        // Toggle category expansion
        function toggleCategory(categoryId) {
            const container = document.getElementById(`labs-${categoryId}`);
            const icon = document.querySelector(`[data-category-id="${categoryId}"]`);
            
            if (container.classList.contains('expanded')) {
                container.classList.remove('expanded');
                icon.classList.remove('expanded');
            } else {
                container.classList.add('expanded');
                icon.classList.add('expanded');
            }
        }

        // Open laboratory in modal
        function openLaboratory(laboratory) {
            const modal = document.getElementById('laboratoryModal');
            const iframe = document.getElementById('modal-iframe');
            const title = document.getElementById('modal-title');
            
            title.textContent = laboratory.name;
            iframe.src = laboratory.redirect_url;
            modal.style.display = 'block';
        }

        // Close modal
        function closeModal() {
            const modal = document.getElementById('laboratoryModal');
            const iframe = document.getElementById('modal-iframe');
            
            modal.style.display = 'none';
            iframe.src = '';
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
                    alert('Kategori başarıyla eklendi!');
                    document.getElementById('category-form').reset();
                    loadTreeData();
                    loadCategories();
                } else {
                    alert('Hata: ' + data.message);
                }
            } catch (error) {
                console.error('Hata:', error);
                alert('Kategori eklenirken hata oluştu');
            }
        }

        // Add laboratory
        async function addLaboratory() {
            const formData = {
                name: document.getElementById('laboratory-name').value,
                category_id: document.getElementById('laboratory-category').value,
                redirect_url: document.getElementById('laboratory-url').value
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
                    alert('Laboratuvar başarıyla eklendi!');
                    document.getElementById('laboratory-form').reset();
                    loadTreeData();
                    updateStats();
                } else {
                    alert('Hata: ' + data.message);
                }
            } catch (error) {
                console.error('Hata:', error);
                alert('Laboratuvar eklenirken hata oluştu');
            }
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

        // Logout function
        function logout() {
            if (confirm('Çıkış yapmak istediğinizden emin misiniz?')) {
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
                        alert('Çıkış yapılırken hata oluştu: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Logout hatası:', error);
                    window.location.href = '../iamodinson.php?logout=1';
                });
            }
        }
    </script>
</body>
</html>

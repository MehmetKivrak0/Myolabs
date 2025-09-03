 // Global variables
 let treeData = [];
 let categories = [];
 
 // Image validation state management
 const ImageValidationState = {
     hasImage: false,
     isValidSize: false,
     
     // State'i güncelle
     updateState(hasImage, isValidSize) {
         this.hasImage = hasImage;
         this.isValidSize = isValidSize;
         this.updateButton();
     },
     
     // Buton durumunu güncelle
     updateButton() {
         const submitBtn = document.getElementById('device-submit-btn');
         if (!submitBtn) return;
         
         const hasInvalidImage = this.hasImage && !this.isValidSize;
         
         if (hasInvalidImage) {
             // Kırmızı buton - resim boyutu uygun değil
             submitBtn.disabled = true;
             submitBtn.classList.add('error-state');
             submitBtn.innerHTML = '<i class="fas fa-ban"></i> RESİM BOYUTU<br>UYGUN DEĞİL';
             console.log('🔴 Buton kırmızı - resim boyutu uygun değil');
         } else {
             // Yeşil buton - normal durum
             submitBtn.disabled = false;
             submitBtn.classList.remove('error-state');
             submitBtn.innerHTML = '<i class="fas fa-plus"></i> CİHAZ EKLE';
             console.log('🟢 Buton yeşil - normal durum');
         }
     },
     
     // Resim boyutu geçerli mi kontrol et
     canSubmit() {
         return !this.hasImage || this.isValidSize;
     }
 };

 // Show notification function
 function showNotification(message, type = 'info') {
     // Remove existing notifications
     const existingNotifications = document.querySelectorAll('.notification');
     existingNotifications.forEach(notification => notification.remove());
     
     // Create notification element
     const notification = document.createElement('div');
     notification.className = `notification notification-${type}`;
     
     // Mesajı satırlara böl
     const messageLines = message.split('\n').filter(line => line.trim());
     const messageHtml = messageLines.map(line => `<div>${line}</div>`).join('');
     
     notification.innerHTML = `
         <div class="notification-content">
             <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'}"></i>
             <div class="notification-text">
                 ${messageHtml}
             </div>
             <button class="notification-close" onclick="this.parentElement.parentElement.remove()">
                 <i class="fas fa-times"></i>
             </button>
         </div>
     `;
     
     // Add to page
     document.body.appendChild(notification);
     
     // Auto remove after 8 seconds for error notifications
     const autoRemoveTime = type === 'error' ? 8000 : 5000;
     setTimeout(() => {
         if (notification.parentElement) {
             notification.remove();
         }
     }, autoRemoveTime);
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
         
         // State kontrolü - form submit'i engelle
         if (!ImageValidationState.canSubmit()) {
             showNotification('❌ Lütfen belirtilen boyuttaki resimleri yükleyin! Cihaz eklenemez.', 'error');
             return false;
         }
         
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
     
     // Content laboratory selection change
     document.getElementById('content-lab').addEventListener('change', function() {
         // Laboratuvar değiştiğinde içerik tipini sıfırla
         document.getElementById('content-type').value = '';
         toggleContentInput();
         updateContentTypeOptions();
     });
     
     // Content image upload functionality
     setupContentImageUpload();
     
     // Initialize content form state
     toggleContentInput();
     
     // Initialize content form - laboratuvar seçilmeden resim yükleme deaktif
     initializeContentForm();
     
     // Metin alanı değişikliklerini dinle
     document.getElementById('content-value').addEventListener('input', function() {
         const contentType = document.getElementById('content-type').value;
         if (contentType === 'lab_title') {
             updateTextButtons();
         }
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
     
     // Başlangıçta resim yükleme alanını deaktif et
     initializeDeviceImageUpload();
     
     // Başlangıçta state'i başlat
     ImageValidationState.updateState(false, false);
 }

 // Load tree data
 async function loadTreeData() {
     try {
         const response = await fetch('./api_tree.php');
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
         const response = await fetch('./api_categories.php');
         
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
         const response = await fetch('./api_laboratories.php');
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
         const response = await fetch(`./api_devices.php?lab_id=${labId}`);
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

 // Initialize device image upload area
 function initializeDeviceImageUpload() {
     const imageUploadArea = document.getElementById('image-upload-area');
     const imageUploadContainer = imageUploadArea ? imageUploadArea.closest('.form-group') : null;
     
     if (imageUploadContainer) {
         imageUploadContainer.style.display = 'none';
         imageUploadArea.style.opacity = '0.5';
         imageUploadArea.style.pointerEvents = 'none';
     }
 }

 // Initialize content form
 function initializeContentForm() {
     const labSelect = document.getElementById('content-lab');
     const contentTypeSelect = document.getElementById('content-type');
     
     // Başlangıçta laboratuvar ve içerik tipi seçimi boş olsun
     if (labSelect) labSelect.value = '';
     if (contentTypeSelect) contentTypeSelect.value = '';
     
     // İçerik tipi seçeneklerini güncelle
     updateContentTypeOptions();
 }

 // Update content type options based on laboratory selection
 function updateContentTypeOptions() {
     const labSelect = document.getElementById('content-lab');
     const contentTypeSelect = document.getElementById('content-type');
     
     if (!labSelect || !contentTypeSelect) return;
     
     const labSelected = labSelect.value && labSelect.value.trim() !== '';
     
     // Resim seçeneğini aktif/deaktif et
     const imageOption = contentTypeSelect.querySelector('option[value="main_image"]');
     if (imageOption) {
         if (labSelected) {
             imageOption.disabled = false;
             imageOption.style.color = '';
         } else {
             imageOption.disabled = true;
             imageOption.style.color = '#ccc';
         }
     }
 }

 // Update device order automatically when laboratory is selected
 async function updateDeviceOrder() {
     const labSelect = document.getElementById('device-lab');
     const orderInput = document.getElementById('device-order');
     const imageUploadArea = document.getElementById('image-upload-area');
     const imageUploadContainer = imageUploadArea ? imageUploadArea.closest('.form-group') : null;
     
     if (labSelect.value) {
         const deviceCount = await getDeviceCount(labSelect.value);
         orderInput.value = deviceCount + 1; // 1'den başla
         
         // Resim yükleme alanını aktif et
         if (imageUploadContainer) {
             imageUploadContainer.style.display = 'block';
             imageUploadArea.style.opacity = '1';
             imageUploadArea.style.pointerEvents = 'auto';
         }
     } else {
         orderInput.value = 1; // Boşsa 1 göster
         
         // Resim yükleme alanını deaktif et
         if (imageUploadContainer) {
             imageUploadContainer.style.display = 'none';
             imageUploadArea.style.opacity = '0.5';
             imageUploadArea.style.pointerEvents = 'none';
         }
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
                     <span class="lab-status">✓ Ekli</span>
                 `;
                 // Laboratuvar tıklanabilir değil, sadece eklenip eklenmediği gösteriliyor
                 // Artık laboratuvarlara tıklanamaz, sadece durumları gösterilir
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

 // Laboratuvar tıklanabilir değil, sadece eklenip eklenmediği gösteriliyor
 // function openLaboratory(laboratory) {
 //     // Yeni detay sayfasında aç
 //     window.open('../lab_detail.php?id=' + laboratory.id, '_blank');
 // }

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
         const response = await fetch('./api_categories.php', {
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
         const response = await fetch('./api_laboratories.php', {
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
     // Son güvenlik kontrolü - state kontrolü
     if (!ImageValidationState.canSubmit()) {
         showNotification('❌ Lütfen belirtilen boyuttaki resimleri yükleyin! Cihaz eklenemez.', 'error');
         return;
     }

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
         const response = await fetch('./api_devices.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify(formData)
         });

         const data = await response.json();
         
         if (data.success) {
             showNotification('Cihaz başarıyla eklendi!', 'success');
             
             // Form reset
             document.getElementById('device-form').reset();
             
             // Resim alanını da reset et
             resetImageUpload();
             
             // Laboratuvar seçimini de reset et
             document.getElementById('device-lab').value = '';
             
             // Sıra numarasını reset et
             document.getElementById('device-order').value = '0';
             
             // Resim yükleme alanını deaktif et
             initializeDeviceImageUpload();
             
             // State'i reset et
             ImageValidationState.updateState(false, false);
             
             // Update order after successful addition
             updateDeviceOrder();
             
             console.log('✅ Form tamamen resetlendi');
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

         // Resim boyutunu kontrol et
         const img = new Image();
         const reader = new FileReader();
         
         reader.onload = function(e) {
             img.onload = function() {
                 const width = img.width;
                 const height = img.height;
                 
                 // Boyut bilgisini göster
                 showImageSizeInfo(width, height);
                 
                                 // Boyut kontrolü ve state güncelleme
                const isValidSize = (width === 500 && height === 500) || 
                                  (width >= 400 && height >= 400 && width <= 1600 && height <= 1600);
                 
                 if (width === 500 && height === 500) {
                     showNotification(`✅ Mükemmel! Resim boyutu 500x500 px`, 'success');
                                 } else if (width >= 400 && height >= 400 && width <= 1600 && height <= 1600) {
                    showNotification(`⚠️ Resim boyutu ${width}x${height} px. Kabul edilebilir ama önerilen: 500x500 px`, 'warning');
                 } else {
                     showNotification(`❌ Lütfen belirtilen boyuttaki resimleri yükleyin!\nMevcut boyut: ${width}x${height} px\nÖnerilen boyut: 500x500 px\nYükleme iptal edildi.`, 'error');
                 }
                 
                 // Preview göster
                 previewImg.src = e.target.result;
                 imagePreview.style.display = 'block';
                 uploadArea.style.display = 'none';
                 
                 // State güncelle
                 if (isValidSize) {
                     // Geçerli boyut - resmi yükle
                     uploadImage(file);
                     ImageValidationState.updateState(true, true);
                 } else {
                     // Geçersiz boyut - resmi yükleme
                     uploadedUrlInput.value = '';
                     ImageValidationState.updateState(true, false);
                 }
             };
             img.src = e.target.result;
         };
         reader.readAsDataURL(file);
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

         fetch('./api_upload_image.php', {
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
         
         // Boyut bilgisini gizle
         const sizeInfo = document.getElementById('image-size-info');
         if (sizeInfo) {
             sizeInfo.style.display = 'none';
             sizeInfo.innerHTML = '<i class="fas fa-info-circle"></i><span id="image-size-text">Boyut: 0x0 px</span>';
         }
         
         // State güncelle - resim yok
         ImageValidationState.updateState(false, false);
     }

     function showImageSizeInfo(width, height) {
         const sizeInfo = document.getElementById('image-size-info');
         const sizeText = document.getElementById('image-size-text');
         
         if (sizeInfo && sizeText) {
             sizeText.textContent = `Boyut: ${width}x${height} px`;
             
             // Boyut durumuna göre stil değiştir
             sizeInfo.className = 'image-size-info';
             
             if (width === 500 && height === 500) {
                 sizeInfo.classList.add('image-size-info');
                 sizeInfo.innerHTML = '<i class="fas fa-check-circle"></i><span>Boyut: 500x500 px (Mükemmel!)</span>';
                         } else if (width >= 400 && height >= 400 && width <= 1600 && height <= 1600) {
                sizeInfo.classList.add('image-size-warning');
                sizeInfo.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>Boyut: ${width}x${height} px (Kabul edilebilir)</span>`;
             } else {
                 sizeInfo.classList.add('image-size-error');
                 sizeInfo.innerHTML = `<i class="fas fa-times-circle"></i><span>Boyut: ${width}x${height} px (Önerilen: 500x500 px)</span>`;
             }
             
             sizeInfo.style.display = 'block';
         }
     }

     // Resim yükleme alanını reset et
     window.resetImageUpload = function() {
         const fileInput = document.getElementById('device-image');
         const uploadedUrlInput = document.getElementById('uploaded-image-url');
         const imagePreview = document.getElementById('image-preview');
         const uploadArea = document.getElementById('image-upload-area');
         const previewImg = document.getElementById('preview-img');
         const sizeInfo = document.getElementById('image-size-info');
         
         // Tüm resim alanlarını temizle
         fileInput.value = '';
         uploadedUrlInput.value = '';
         imagePreview.style.display = 'none';
         uploadArea.style.display = 'block';
         previewImg.src = '';
         
         // Boyut bilgisini gizle ve sıfırla
         if (sizeInfo) {
             sizeInfo.style.display = 'none';
             sizeInfo.innerHTML = '<i class="fas fa-info-circle"></i><span id="image-size-text">Boyut: 0x0 px</span>';
             sizeInfo.className = 'image-size-info';
         }
         
         // Upload progress'i gizle
         const progress = document.getElementById('upload-progress');
         if (progress) {
             progress.style.display = 'none';
             const fill = progress.querySelector('.upload-progress-fill');
             if (fill) fill.style.width = '0%';
         }
         
         // State güncelle - resim yok
         ImageValidationState.updateState(false, false);
         
         console.log('🔄 Resim yükleme alanı tamamen resetlendi');
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
     const labSelect = document.getElementById('content-lab');
     const textGroup = document.getElementById('text-input-group');
     const imageGroup = document.getElementById('image-input-group');
     const altTextInput = document.getElementById('content-alt');
     const altTextGroup = altTextInput ? altTextInput.closest('.form-group') : null;
     const textArea = document.getElementById('content-value');
     const textLabel = textGroup.querySelector('label');
     
     // Varsayılan olarak her şeyi gizle
     textGroup.style.display = 'none';
     imageGroup.style.display = 'none';
     if (altTextGroup) altTextGroup.style.display = 'none';
     textArea.removeAttribute('required');
     
     if (contentType === 'main_image') {
         // Laboratuvar seçilmiş mi kontrol et
         if (!labSelect.value) {
             showNotification('Resim yüklemek için önce laboratuvar seçmelisiniz!', 'warning');
             document.getElementById('content-type').value = '';
             return;
         }
         
         // Sadece resim yükleme alanını ve alt_text alanını göster
         imageGroup.style.display = 'block';
         if (altTextGroup) altTextGroup.style.display = 'block';
         updateImageButtons();
     } else if (contentType === 'lab_title') {
         // Laboratuvar başlığı için metin alanını göster
         textGroup.style.display = 'block';
         textArea.setAttribute('required', 'required');
         textLabel.innerHTML = '<i class="fas fa-heading"></i> Laboratuvar Başlığı';
         textArea.placeholder = 'Laboratuvar başlığını girin (örn: "Biyokimya Laboratuvarı", "Fizik Laboratuvarı")...';
         updateTextButtons();
     } else if (contentType === 'catalog_info') {
         // Katalog bilgisi için metin alanını göster
         textGroup.style.display = 'block';
         textArea.setAttribute('required', 'required');
         textLabel.innerHTML = '<i class="fas fa-info-circle"></i> Katalog Bilgisi';
         textArea.placeholder = 'Katalog sayfasında "Hakkında" bölümünde görünecek kısa bilgiyi girin...';
         updateTextButtons();
     } else if (contentType === 'detail_page_info') {
         // Detay sayfası bilgisi için metin alanını göster
         textGroup.style.display = 'block';
         textArea.setAttribute('required', 'required');
         textLabel.innerHTML = '<i class="fas fa-file-alt"></i> Detay Sayfası Bilgisi';
         textArea.placeholder = 'Laboratuvar detay sayfasında "Hakkında" bölümünde görünecek detaylı bilgiyi girin...';
         updateTextButtons();
     }
     // Eğer hiçbir seçenek seçilmemişse her şey gizli kalır
 }

 // Resim yükleme durumuna göre butonları güncelle
 function updateImageButtons() {
     const saveBtn = document.querySelector('.form-actions button[type="submit"]');
     const imageUrlInput = document.getElementById('uploaded-content-image-url');
     
     if (!saveBtn || !imageUrlInput) {
         console.error('Gerekli butonlar bulunamadı');
         return;
     }
     
     const imageUrl = imageUrlInput.value;
     
     if (imageUrl && imageUrl.trim() !== '') {
         // Resim yüklendi, güncelle butonu göster
         saveBtn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
         saveBtn.className = 'btn btn-warning modern-btn';
     } else {
         // Resim yüklenmedi, kaydet butonu göster
         saveBtn.innerHTML = '<i class="fas fa-save"></i> Kaydet';
         saveBtn.className = 'btn btn-success modern-btn';
     }
 }

 // Metin içeriği durumuna göre butonları güncelle
 function updateTextButtons() {
     const saveBtn = document.querySelector('.form-actions button[type="submit"]');
     const textValueInput = document.getElementById('content-value');
     
     if (!saveBtn || !textValueInput) {
         console.error('Gerekli butonlar bulunamadı');
         return;
     }
     
     const textValue = textValueInput.value;
     
     if (textValue && textValue.trim() !== '') {
         // Metin var, güncelle butonu göster
         saveBtn.innerHTML = '<i class="fas fa-save"></i> Güncelle';
         saveBtn.className = 'btn btn-warning modern-btn';
     } else {
         // Metin yok, kaydet butonu göster
         saveBtn.innerHTML = '<i class="fas fa-save"></i> Kaydet';
         saveBtn.className = 'btn btn-success modern-btn';
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
         updateImageButtons(); // Butonları güncelle
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
         const response = await fetch('./api_upload_image.php', {
             method: 'POST',
             body: formData
         });

         const data = await response.json();

         if (data.success) {
             document.getElementById('uploaded-content-image-url').value = data.data.url;
             hideContentUploadProgress();
             showNotification('Resim başarıyla yüklendi!', 'success');
             updateImageButtons(); // Butonları güncelle
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
     
     // Debug için log ekle
     console.log('🔍 Form değerleri:', { labId, contentType, contentValue, altText });
     
     if (!labId || !contentType || contentType.trim() === '') {
         showNotification('Laboratuvar ve içerik tipi seçin!', 'warning');
         console.error('Content type validation failed:', { labId, contentType });
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
         // Yeni tablo yapısına göre veri hazırla
         const requestData = {
             lab_id: labId
         };
         
         // İçerik tipine göre veriyi ekle
         if (contentType === 'main_image') {
             requestData.main_image = contentValue;
             if (altText) requestData.alt_text = altText;
         } else if (contentType === 'lab_title') {
             requestData.lab_title = contentValue;
         } else if (contentType === 'catalog_info') {
             requestData.catalog_info = contentValue;
         } else if (contentType === 'detail_page_info') {
             requestData.detail_page_info = contentValue;
         }
         
         console.log('Sending content data:', requestData);
         
         const response = await fetch('./api_lab_contents.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/json'
             },
             body: JSON.stringify(requestData)
         });

         console.log('Response status:', response.status);
         
         if (!response.ok) {
             throw new Error(`HTTP error! status: ${response.status}`);
         }
         
         // Response text'ini al ve JSON olarak parse et
         const responseText = await response.text();
         console.log('Raw response:', responseText);
         
         let data;
         try {
             data = JSON.parse(responseText);
         } catch (parseError) {
             console.error('JSON parse hatası:', parseError);
             console.error('Response text:', responseText);
             throw new Error(`API'den geçersiz JSON döndü: ${responseText.substring(0, 100)}...`);
         }
         
         console.log('Response data:', data);
         
         if (data.success) {
             showNotification('İçerik başarıyla kaydedildi!', 'success');
             
             // Form'u güvenli şekilde reset et
             console.log('🔄 Form tamamen resetleniyor...');
             
             try {
                 // Form'u manuel olarak temizle
                 const contentValueEl = document.getElementById('content-value');
                 const contentAltEl = document.getElementById('content-alt');
                 const uploadedImageUrlEl = document.getElementById('uploaded-content-image-url');
                 const contentTypeEl = document.getElementById('content-type');
                 const contentLabEl = document.getElementById('content-lab');
                 
                 if (contentValueEl) contentValueEl.value = '';
                 if (contentAltEl) contentAltEl.value = '';
                 if (uploadedImageUrlEl) uploadedImageUrlEl.value = '';
                 if (contentTypeEl) contentTypeEl.value = '';
                 if (contentLabEl) contentLabEl.value = '';
                 
                 // Reset image preview
                 const imagePreview = document.getElementById('content-image-preview');
                 const imageUploadArea = document.getElementById('content-image-upload-area');
                 
                 if (imagePreview) imagePreview.style.display = 'none';
                 if (imageUploadArea) imageUploadArea.style.display = 'block';
                 
                 console.log('🔄 Form tamamen resetlendi');
                 
                 // toggleContentInput'u çağır (content-type boş olduğu için tüm alanlar gizlenecek)
                 toggleContentInput();
                 
             } catch (resetError) {
                 console.error('Form reset hatası:', resetError);
                 showNotification('Form resetlenirken hata oluştu, ancak içerik kaydedildi', 'warning');
             }
         } else {
             let errorMessage = 'Hata: ' + (data.message || 'Bilinmeyen hata');
             if (data.error_details) {
                 errorMessage += '\nDetay: ' + JSON.stringify(data.error_details);
             }
             if (data.debug_info) {
                 errorMessage += '\nDebug: ' + JSON.stringify(data.debug_info);
             }
             showNotification(errorMessage, 'error');
             console.error('Content save error:', data);
         }
     } catch (error) {
         console.error('Content save exception:', error);
         
         let errorMessage = 'İçerik kaydedilirken hata oluştu';
         if (error.message) {
             errorMessage += ': ' + error.message;
         }
         
         showNotification(errorMessage, 'error');
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
         const response = await fetch(`./api_lab_contents.php?lab_id=${labId}`);
         const data = await response.json();
         
         if (data.success && data.data) {
             const contents = data.data;
             
             // Mevcut içerikleri göster
             let hasContent = false;
             
             if (contents.main_image && contents.main_image.content_value) {
                 document.getElementById('content-type').value = 'main_image';
                 document.getElementById('uploaded-content-image-url').value = contents.main_image.content_value;
                 document.getElementById('content-alt').value = contents.alt_text ? contents.alt_text.content_value || '' : '';
                 toggleContentInput();
                 
                 // Resim önizlemesini göster
                 document.getElementById('content-preview-img').src = contents.main_image.content_value;
                 document.getElementById('content-image-preview').style.display = 'block';
                 document.getElementById('content-image-upload-area').style.display = 'none';
                 
                 // Butonları güncelle
                 updateImageButtons();
                 hasContent = true;
             } else if (contents.lab_title && contents.lab_title.content_value) {
                 document.getElementById('content-type').value = 'lab_title';
                 document.getElementById('content-value').value = contents.lab_title.content_value;
                 document.getElementById('content-alt').value = contents.alt_text ? contents.alt_text.content_value || '' : '';
                 toggleContentInput();
                 
                 // Butonları güncelle
                 updateTextButtons();
                 hasContent = true;
             } else if (contents.catalog_info && contents.catalog_info.content_value) {
                 document.getElementById('content-type').value = 'catalog_info';
                 document.getElementById('content-value').value = contents.catalog_info.content_value;
                 document.getElementById('content-alt').value = contents.alt_text ? contents.alt_text.content_value || '' : '';
                 toggleContentInput();
                 
                 // Butonları güncelle
                 updateTextButtons();
                 hasContent = true;
             } else if (contents.detail_page_info && contents.detail_page_info.content_value) {
                 document.getElementById('content-type').value = 'detail_page_info';
                 document.getElementById('content-value').value = contents.detail_page_info.content_value;
                 document.getElementById('content-alt').value = contents.alt_text ? contents.alt_text.content_value || '' : '';
                 toggleContentInput();
                 
                 // Butonları güncelle
                 updateTextButtons();
                 hasContent = true;
             }
             
             if (!hasContent) {
                 showNotification('Bu laboratuvar için henüz içerik eklenmemiş.', 'info');
             }
         } else {
             showNotification('Hata: ' + data.message, 'error');
         }
     } catch (error) {
         showNotification('İçerikler yüklenirken hata: ' + error.message, 'error');
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
     const deviceListCard = document.getElementById('device-list-card');
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
         
         // Populate laboratory dropdown and auto-refresh
         populateDeviceListLabDropdown();
         
         // Otomatik olarak cihaz listesini yenile
         setTimeout(() => {
             const labSelect = document.getElementById('device-list-lab-select');
             if (labSelect.value) {
                 console.log('🔄 Cihaz listesi otomatik yenileniyor...');
                 loadDeviceList();
             }
         }, 200);
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
     
     fetch('./api_laboratories.php?action=get_all')
         .then(response => response.json())
         .then(data => {
             if (data.success && data.laboratories.length > 0) {
                 data.laboratories.forEach(lab => {
                     const option = document.createElement('option');
                     option.value = lab.id;
                     option.textContent = lab.name;
                     labSelect.appendChild(option);
                 });
                 
                 // Artık otomatik seçim yapılmıyor, kullanıcı manuel seçmeli
                 console.log('🔄 Laboratuvarlar yüklendi, kullanıcı seçim yapmalı');
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
     
     console.log('🔄 Cihaz listesi yükleniyor, Lab ID:', labId);
     
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
     
     // Cache-busting için timestamp ekle
     const timestamp = new Date().getTime();
     fetch(`./api_devices.php?action=get_by_lab&lab_id=${labId}&t=${timestamp}`)
         .then(response => {
             console.log('Response status:', response.status);
             if (!response.ok) {
                 throw new Error(`HTTP error! status: ${response.status}`);
             }
             return response.json();
         })
         .then(data => {
             console.log('📡 API Response:', data);
             console.log('📊 Veritabanından gelen cihaz sayısı:', data.devices ? data.devices.length : 0);
             console.log('🕐 Yükleme zamanı:', new Date().toLocaleTimeString());
             
             if (data.success) {
                 if (data.devices && data.devices.length > 0) {
                     console.log('✅ Cihazlar başarıyla yüklendi, görüntüleniyor...');
                     console.log('📋 Cihaz listesi:', data.devices);
                     displayDeviceList(data.devices);
                 } else {
                     console.log('ℹ️ Bu laboratuvarda cihaz bulunamadı');
                     contentDiv.innerHTML = `
                         <div class="device-list-empty">
                             <i class="fas fa-microchip"></i>
                             <p>Bu laboratuvarda henüz cihaz bulunmuyor</p>
                             <small>Yeni cihaz eklemek için "Yeni Cihaz Ekle" formunu kullanın</small>
                         </div>
                     `;
                 }
             } else {
                 console.error('API başarısız:', data.message);
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
                 // InfinityFree hosting'de DELETE method sorun çıkarabilir, GET ile deneyelim
                 const response = await fetch(`./api_devices.php?action=delete&id=${deviceId}`, {
                     method: 'GET',
                     headers: {
                         'Accept': 'application/json'
                     }
                 });

                 const data = await response.json();
                 
                 if (data.success) {
                     showNotification('Cihaz başarıyla silindi!', 'success');
                     // Reload device list with delay to ensure database consistency
                     reloadDeviceList();
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
     
     console.log('🔄 Cihaz listesi görüntüleniyor, cihaz sayısı:', devices.length);
     console.log('Cihazlar:', devices);
     
     let html = '<div class="device-list-grid">';
     devices.forEach(device => {
         console.log('Cihaz işleniyor:', device);
         
         html += '<div class="device-item" data-device-id="' + device.id + '">';
         html += '<div class="device-checkbox-container">';
         html += '<input type="checkbox" class="device-checkbox" id="device-' + device.id + '" value="' + device.id + '" onchange="updateBulkActions()">';
         html += '<label for="device-' + device.id + '" class="device-checkbox-label"></label>';
         html += '</div>';
         html += '<div class="device-item-header">';
         html += '<div class="device-action-buttons">';
         html += '<button onclick="editDevice(' + device.id + ')" class="device-edit-btn" title="Cihazı Düzenle">';
         html += '<i class="fas fa-edit"></i>';
         html += '</button>';
         html += '<button onclick="deleteDevice(' + device.id + ')" class="device-delete-btn" title="Cihazı Sil">';
         html += '<i class="fas fa-trash"></i>';
         html += '</button>';
         html += '</div>';
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
     
     // Toplu işlem kontrollerini gizle (yeni liste yüklendiğinde)
     hideBulkActions();
 }

 // Reload device list with delay to ensure database consistency
 function reloadDeviceList() {
     setTimeout(() => {
         const labId = document.getElementById('device-list-lab-select').value;
         if (labId) {
             console.log('🔄 Silme sonrası cihaz listesi yenileniyor...');
             loadDeviceList();
         }
     }, 500);
 }

 // Toplu işlem fonksiyonları
 function updateBulkActions() {
     const checkboxes = document.querySelectorAll('.device-checkbox:checked');
     const selectedCount = checkboxes.length;
     const bulkContainer = document.getElementById('bulk-actions-container');
     
     document.getElementById('selected-count').textContent = selectedCount;
     
     // Seçili cihaz kartlarına görsel geri bildirim
     const allDeviceItems = document.querySelectorAll('.device-item');
     allDeviceItems.forEach(item => {
         const checkbox = item.querySelector('.device-checkbox');
         if (checkbox && checkbox.checked) {
             item.classList.add('selected');
         } else {
             item.classList.remove('selected');
         }
     });
     
     if (selectedCount > 0) {
         bulkContainer.style.display = 'block';
     } else {
         bulkContainer.style.display = 'none';
     }
 }

 function selectAllDevices() {
     const checkboxes = document.querySelectorAll('.device-checkbox');
     checkboxes.forEach(checkbox => {
         checkbox.checked = true;
     });
     updateBulkActions();
 }

 function deselectAllDevices() {
     const checkboxes = document.querySelectorAll('.device-checkbox');
     checkboxes.forEach(checkbox => {
         checkbox.checked = false;
     });
     updateBulkActions();
 }

 function getSelectedDeviceIds() {
     const checkboxes = document.querySelectorAll('.device-checkbox:checked');
     return Array.from(checkboxes).map(checkbox => checkbox.value);
 }

 function hideBulkActions() {
     const bulkContainer = document.getElementById('bulk-actions-container');
     bulkContainer.style.display = 'none';
     document.getElementById('selected-count').textContent = '0';
     
     // Tüm seçimleri temizle ve görsel geri bildirimi kaldır
     const checkboxes = document.querySelectorAll('.device-checkbox');
     checkboxes.forEach(checkbox => {
         checkbox.checked = false;
     });
     
     const allDeviceItems = document.querySelectorAll('.device-item');
     allDeviceItems.forEach(item => {
         item.classList.remove('selected');
     });
 }

 async function deleteSelectedDevices() {
     const selectedIds = getSelectedDeviceIds();
     
     if (selectedIds.length === 0) {
         showNotification('Silinecek cihaz seçilmedi!', 'warning');
         return;
     }
     
     const deviceCount = selectedIds.length;
     const confirmMessage = deviceCount === 1 
         ? 'Bu cihazı silmek istediğinizden emin misiniz?' 
         : `${deviceCount} cihazı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!`;
     
     showConfirmation(confirmMessage, async () => {
         try {
             const response = await fetch('./api_devices.php', {
                 method: 'DELETE',
                 headers: {
                     'Content-Type': 'application/json'
                 },
                 body: JSON.stringify({
                     ids: selectedIds
                 })
             });

             const data = await response.json();
             
             if (data.success) {
                 showNotification(data.message, 'success');
                 hideBulkActions();
                 reloadDeviceList();
             } else {
                 showNotification('Hata: ' + data.message, 'error');
             }
         } catch (error) {
             showNotification('Cihazlar silinirken hata: ' + error.message, 'error');
         }
     });
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
                 console.log('🗑️ Kategori silme işlemi başlatılıyor...');
                 console.log('📋 Category ID:', categoryId);
                 console.log('🔗 API URL:', `./api_categories.php?action=delete&id=${categoryId}`);
                 
                 // InfinityFree hosting'de DELETE method sorun çıkarabilir, GET ile deneyelim
                 const response = await fetch(`./api_categories.php?action=delete&id=${categoryId}`, {
                     method: 'GET',
                     headers: {
                         'Accept': 'application/json'
                     }
                 });
                 
                 console.log('📡 Response status:', response.status);
                 console.log('📡 Response ok:', response.ok);

                 const responseText = await response.text();
                 console.log('📄 Raw response text:', responseText);
                 
                 let data;
                 try {
                     data = JSON.parse(responseText);
                     console.log('📊 Parsed data:', data);
                 } catch (parseError) {
                     console.error('❌ JSON parse hatası:', parseError);
                     console.error('❌ Response text:', responseText);
                     throw new Error(`API'den geçersiz JSON döndü: ${responseText.substring(0, 100)}...`);
                 }
                 
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
                 console.log('🏗️ Laboratuvar silme işlemi başlatılıyor...');
                 console.log('📋 Laboratory ID:', laboratoryId);
                 console.log('🔗 API URL:', `./api_laboratories.php?action=delete&id=${laboratoryId}`);
                 
                 // InfinityFree hosting'de DELETE method sorun çıkarabilir, GET ile deneyelim
                 const response = await fetch(`./api_laboratories.php?action=delete&id=${laboratoryId}`, {
                     method: 'GET',
                     headers: {
                         'Accept': 'application/json'
                     }
                 });

                 console.log('📡 Response status:', response.status);
                 console.log('📡 Response ok:', response.ok);
                 
                 const responseText = await response.text();
                 console.log('📄 Raw response text:', responseText);
                 
                 let data;
                 try {
                     data = JSON.parse(responseText);
                     console.log('📊 Parsed data:', data);
                 } catch (parseError) {
                     console.error('❌ JSON parse hatası:', parseError);
                     console.error('❌ Response text:', responseText);
                     throw new Error(`API'den geçersiz JSON döndü: ${responseText.substring(0, 100)}...`);
                 }
                 
                 if (data.success) {
                     showNotification('Laboratuvar başarıyla silindi!', 'success');
                     document.getElementById('delete-laboratory-select').value = '';
                     
                     // Silme işleminden sonra laboratuvarın gerçekten silinip silinmediğini kontrol et
                     setTimeout(async () => {
                         try {
                             const checkResponse = await fetch(`./api_laboratories.php?action=check_exists&id=${laboratoryId}`);
                             const checkData = await checkResponse.json();
                             
                             if (checkData.success && checkData.exists) {
                                 console.error('⚠️ Laboratuvar hala veritabanında mevcut!');
                                 showNotification('Uyarı: Laboratuvar hala veritabanında mevcut!', 'warning');
                             } else {
                                 console.log('✅ Laboratuvar başarıyla silindi ve veritabanından kaldırıldı.');
                             }
                         } catch (checkError) {
                             console.error('❌ Kontrol hatası:', checkError);
                         }
                     }, 1000);
                     
                     loadTreeData();
                     loadLaboratories();
                     updateStats();
                 } else {
                     console.error('❌ API Error:', data.message);
                     showNotification('Hata: ' + data.message, 'error');
                 }
             } catch (error) {
                 showNotification('Laboratuvar silinirken hata: ' + error.message, 'error');
             }
         }
     );
 }

 // Device Edit Functions
 async function editDevice(deviceId) {
     try {
         // Cihaz bilgilerini al
         const response = await fetch(`./api_devices.php?action=get_by_id&id=${deviceId}`);
         const data = await response.json();
         
         if (data.success) {
             const device = data.device;
             
             // Modal'ı aç ve form alanlarını doldur
             openDeviceModal();
             populateEditForm(device);
         } else {
             showNotification('Cihaz bilgileri alınamadı: ' + data.message, 'error');
         }
     } catch (error) {
         showNotification('Cihaz bilgileri alınırken hata: ' + error.message, 'error');
     }
 }

 // Modal'ı aç
 function openDeviceModal() {
     const modal = document.getElementById('deviceEditModal');
     modal.style.display = 'block';
     
     // Laboratuvar dropdown'ını doldur
     populateEditLabDropdown();
     
     // Resim yükleme fonksiyonlarını ayarla
     setupEditDeviceImageUpload();
 }

 // Modal'ı kapat
 function closeDeviceModal() {
     const modal = document.getElementById('deviceEditModal');
     modal.style.display = 'none';
     
     // Form'u temizle
     document.getElementById('device-edit-form').reset();
     document.getElementById('edit-device-image-preview').style.display = 'none';
     document.getElementById('edit-device-image-upload-area').style.display = 'block';
     document.getElementById('edit-uploaded-device-image-url').value = '';
 }

 // Edit form alanlarını doldur
 function populateEditForm(device) {
     console.log('🔧 Cihaz verisi yükleniyor:', device);
     
     document.getElementById('edit-device-id').value = device.id;
     document.getElementById('edit-device-order').value = device.order_num || 0;
     document.getElementById('edit-device-name').value = device.device_name;
     document.getElementById('edit-device-model').value = device.device_model || '';
     document.getElementById('edit-device-count').value = device.device_count;
     document.getElementById('edit-device-purpose').value = device.purpose || '';
     
     console.log('🔧 Order num yüklendi:', device.order_num || 0);
     
     // Laboratuvar seçimini ayarla
     setTimeout(() => {
         document.getElementById('edit-device-lab').value = device.lab_id;
     }, 100);
     
     // Mevcut resmi göster
     if (device.image_url) {
         document.getElementById('edit-uploaded-device-image-url').value = device.image_url;
         document.getElementById('edit-device-preview-img').src = '../' + device.image_url;
         document.getElementById('edit-device-image-preview').style.display = 'block';
         document.getElementById('edit-device-image-upload-area').style.display = 'none';
     }
 }

 // Edit laboratuvar dropdown'ını doldur
 async function populateEditLabDropdown() {
     try {
         const response = await fetch('./api_laboratories.php');
         const data = await response.json();
         
         if (data.success) {
             const select = document.getElementById('edit-device-lab');
             select.innerHTML = '<option value="">Laboratuvar seçin...</option>';
             
             data.data.forEach(lab => {
                 const option = document.createElement('option');
                 option.value = lab.id;
                 option.textContent = lab.name;
                 select.appendChild(option);
             });
         }
     } catch (error) {
         console.error('Laboratuvarlar yüklenirken hata:', error);
     }
 }

 // Edit cihaz resim yükleme fonksiyonlarını ayarla
 function setupEditDeviceImageUpload() {
     const uploadArea = document.getElementById('edit-device-image-upload-area');
     const fileInput = document.getElementById('edit-device-image');
     const preview = document.getElementById('edit-device-image-preview');
     const previewImg = document.getElementById('edit-device-preview-img');
     const removeBtn = document.getElementById('remove-edit-device-image');
     const progress = document.getElementById('edit-device-upload-progress');
     const hiddenInput = document.getElementById('edit-uploaded-device-image-url');

     // Click to upload
     uploadArea.addEventListener('click', () => fileInput.click());

     // Drag and drop
     uploadArea.addEventListener('dragover', (e) => {
         e.preventDefault();
         uploadArea.classList.add('drag-over');
     });

     uploadArea.addEventListener('dragleave', () => {
         uploadArea.classList.remove('drag-over');
     });

     uploadArea.addEventListener('drop', (e) => {
         e.preventDefault();
         uploadArea.classList.remove('drag-over');
         const files = e.dataTransfer.files;
         if (files.length > 0) {
             fileInput.files = files;
             handleEditDeviceImageUpload(files[0]);
         }
     });

     // File input change
     fileInput.addEventListener('change', (e) => {
         if (e.target.files.length > 0) {
             handleEditDeviceImageUpload(e.target.files[0]);
         }
     });

     // Remove image
     removeBtn.addEventListener('click', () => {
         preview.style.display = 'none';
         uploadArea.style.display = 'block';
         hiddenInput.value = '';
         fileInput.value = '';
         
         // Boyut bilgisini gizle
         const sizeInfo = document.getElementById('edit-device-image-size-info');
         if (sizeInfo) {
             sizeInfo.style.display = 'none';
             sizeInfo.innerHTML = '<i class="fas fa-info-circle"></i><span id="edit-device-image-size-text">Boyut: 0x0 px</span>';
         }
     });
 }

 // Edit cihaz resim yükleme işlemi
 async function handleEditDeviceImageUpload(file) {
     const progress = document.getElementById('edit-device-upload-progress');
     const hiddenInput = document.getElementById('edit-uploaded-device-image-url');
     const preview = document.getElementById('edit-device-image-preview');
     const previewImg = document.getElementById('edit-device-preview-img');
     const uploadArea = document.getElementById('edit-device-image-upload-area');
     const deviceId = document.getElementById('edit-device-id').value;

     // Dosya boyutu kontrolü
     if (file.size > 5 * 1024 * 1024) {
         showNotification('Dosya boyutu 5MB\'dan büyük olamaz!', 'error');
         return;
     }

     // Dosya tipi kontrolü
     const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
     if (!allowedTypes.includes(file.type)) {
         showNotification('Sadece JPG, PNG, GIF ve WebP dosyaları kabul edilir!', 'error');
         return;
     }

     // Resim boyutunu kontrol et
     const img = new Image();
     const reader = new FileReader();
     
     reader.onload = function(e) {
         img.onload = function() {
             const width = img.width;
             const height = img.height;
             
             // Boyut bilgisini göster
             showEditDeviceImageSizeInfo(width, height);
             
             // Boyut kontrolü ve bildirim
             if (width === 500 && height === 500) {
                 showNotification(`✅ Mükemmel! Resim boyutu 500x500 px`, 'success');
                 
                 // Preview göster
                 previewImg.src = e.target.result;
                 preview.style.display = 'block';
                 uploadArea.style.display = 'none';
                 
                 // Dosyayı yükle
                 uploadEditDeviceImage(file);
                             } else if (width >= 400 && height >= 400 && width <= 1600 && height <= 1600) {
                    showNotification(`⚠️ Resim boyutu ${width}x${height} px. Kabul edilebilir ama önerilen: 500x500 px`, 'warning');
                 
                 // Preview göster
                 previewImg.src = e.target.result;
                 preview.style.display = 'block';
                 uploadArea.style.display = 'none';
                 
                 // Dosyayı yükle
                 uploadEditDeviceImage(file);
             } else {
                 showNotification(`❌ Lütfen belirtilen boyuttaki resimleri yükleyin! Mevcut boyut: ${width}x${height} px. Önerilen boyut: 500x500 px. Yükleme iptal edildi.`, 'error');
                 
                 // Dosyayı yükleme, sadece boyut bilgisini göster
                 previewImg.src = e.target.result;
                 preview.style.display = 'block';
                 uploadArea.style.display = 'none';
                 
                 // Yükleme yapma
             }
         };
         img.src = e.target.result;
     };
     reader.readAsDataURL(file);
 }

 // Edit cihaz resim yükleme işlemi (ayrı fonksiyon)
 async function uploadEditDeviceImage(file) {
     const progress = document.getElementById('edit-device-upload-progress');
     const hiddenInput = document.getElementById('edit-uploaded-device-image-url');
     const preview = document.getElementById('edit-device-image-preview');
     const previewImg = document.getElementById('edit-device-preview-img');
     const uploadArea = document.getElementById('edit-device-image-upload-area');
     const deviceId = document.getElementById('edit-device-id').value;

     const formData = new FormData();
     formData.append('image', file);
     if (deviceId) {
         formData.append('device_id', deviceId);
     }

     try {
         progress.style.display = 'block';
         uploadArea.style.display = 'none';

         const response = await fetch('./api_upload_image.php', {
             method: 'POST',
             body: formData
         });

         const data = await response.json();

         if (data.success) {
             const imageUrl = data.data ? data.data.url : data.image_url;
             hiddenInput.value = imageUrl;
             previewImg.src = '../' + imageUrl;
             preview.style.display = 'block';
             showNotification('Resim başarıyla yüklendi!', 'success');
         } else {
             showNotification('Resim yüklenirken hata: ' + data.message, 'error');
             uploadArea.style.display = 'block';
         }
     } catch (error) {
         showNotification('Resim yüklenirken hata: ' + error.message, 'error');
         uploadArea.style.display = 'block';
     } finally {
         progress.style.display = 'none';
     }
 }

 // Edit cihaz resim boyut bilgisi gösterme
 function showEditDeviceImageSizeInfo(width, height) {
     const sizeInfo = document.getElementById('edit-device-image-size-info');
     const sizeText = document.getElementById('edit-device-image-size-text');
     
     if (sizeInfo && sizeText) {
         sizeText.textContent = `Boyut: ${width}x${height} px`;
         
         // Boyut durumuna göre stil değiştir
         sizeInfo.className = 'image-size-info';
         
         if (width === 500 && height === 500) {
             sizeInfo.classList.add('image-size-info');
             sizeInfo.innerHTML = '<i class="fas fa-check-circle"></i><span>Boyut: 500x500 px (Mükemmel!)</span>';
                     } else if (width >= 400 && height >= 400 && width <= 1600 && height <= 1600) {
                sizeInfo.classList.add('image-size-warning');
                sizeInfo.innerHTML = `<i class="fas fa-exclamation-triangle"></i><span>Boyut: ${width}x${height} px (Kabul edilebilir)</span>`;
         } else {
             sizeInfo.classList.add('image-size-error');
             sizeInfo.innerHTML = `<i class="fas fa-times-circle"></i><span>Boyut: ${width}x${height} px (Önerilen: 500x500 px)</span>`;
         }
         
         sizeInfo.style.display = 'block';
     }
 }

 // Edit form submit
 document.getElementById('device-edit-form').addEventListener('submit', async function(e) {
     e.preventDefault();
     
     const formData = new FormData(this);
     const deviceId = formData.get('device_id');
     const orderNum = formData.get('order_num');
     
     console.log('🔧 Form verileri:');
     console.log('- device_id:', deviceId);
     console.log('- order_num:', orderNum);
     
     if (!deviceId) {
         showNotification('Cihaz ID bulunamadı!', 'error');
         return;
     }

     try {
         // Mevcut order_num değerini al
         const currentOrderNum = parseInt(formData.get('order_num') || 0);
         console.log('🔧 Mevcut order_num:', currentOrderNum);
         
         const requestData = {
             id: deviceId,
             device_name: formData.get('device_name'),
             device_model: formData.get('device_model'),
             device_count: parseInt(formData.get('device_count')),
             lab_id: parseInt(formData.get('lab_id')),
             purpose: formData.get('purpose'),
             order_num: currentOrderNum // Mevcut sıra numarasını koru
         };
         
         console.log('🔧 Cihaz güncelleme verisi:', requestData);

         // Resim URL'i varsa ekle
         const imageUrl = formData.get('uploaded_image_url');
         if (imageUrl) {
             requestData.image_url = imageUrl;
         }

         // InfinityFree hosting'de PUT method sorun çıkarabilir, GET ile deneyelim
         const response = await fetch(`./api_devices.php?action=update&${new URLSearchParams(requestData).toString()}`, {
             method: 'GET',
             headers: {
                 'Accept': 'application/json'
             }
         });

         const data = await response.json();

         if (data.success) {
             showNotification('Cihaz başarıyla güncellendi!', 'success');
             closeDeviceModal();
             
             // Cihaz listesini yenile
             const labId = document.getElementById('device-list-lab-select').value;
             if (labId) {
                 console.log('🔄 Cihaz listesi yenileniyor, Lab ID:', labId);
                 // Kısa bir gecikme ile listeyi yenile (veritabanı güncellemesinin tamamlanması için)
                 setTimeout(() => {
                     loadDeviceList();
                 }, 100);
             }
         } else {
             showNotification('Hata: ' + data.message, 'error');
         }
     } catch (error) {
         showNotification('Cihaz güncellenirken hata: ' + error.message, 'error');
     }
 });

 // Logout function
 function logout() {
     showConfirmation(
         'Çıkış yapmak istediğinizden emin misiniz?',
         () => {
             fetch('./logout_ajax.php', {
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
<!DOCTYPE html>
<html lang="en">
<head>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Image Editor</title>
  <link rel="stylesheet" href="https://unpkg.com/react-image-crop@10.0.0/dist/ReactCrop.css">
  <link rel="stylesheet" href="https://unpkg.com/lucide@latest/font/lucide.css">
  <style>
    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      margin: 0;
      padding: 0;
    }
    
    .image-editor-overlay {
      position: fixed;
      inset: 0;
      background-color: rgba(0, 0, 0, 0.75);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 50;
      padding: 1rem;
    }
    
    .image-editor-container {
      background-color: white;
      border-radius: 1rem;
      max-width: 56rem;
      width: 100%;
      max-height: 90vh;
      overflow: hidden;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
    }
    
    .image-editor-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1.5rem;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .image-editor-title {
      font-size: 1.5rem;
      font-weight: bold;
      color: #111827;
    }
    
    .image-editor-close-btn {
      padding: 0.5rem;
      border-radius: 9999px;
      transition: background-color 0.2s;
    }
    
    .image-editor-close-btn:hover {
      background-color: #f3f4f6;
    }
    
    .image-editor-tabs {
      display: flex;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .image-editor-tab {
      padding: 1rem 1.5rem;
      font-weight: 500;
      transition: color 0.2s;
      position: relative;
      color: #4b5563;
    }
    
    .image-editor-tab:hover {
      color: #111827;
    }
    
    .image-editor-tab.active {
      color: #ea580c;
      border-bottom: 2px solid #ea580c;
    }
    
    .image-editor-content {
      padding: 1.5rem;
      max-height: 60vh;
      overflow-y: auto;
    }
    
    .image-editor-preview-container {
      background-color: #f9fafb;
      border-radius: 0.75rem;
      padding: 1rem;
      display: flex;
      justify-content: center;
    }
    
    .image-editor-preview {
      max-width: 100%;
      max-height: 24rem;
      object-fit: contain;
    }
    
    .image-editor-controls {
      margin-top: 1.5rem;
    }
    
    .image-editor-control-title {
      font-size: 1.125rem;
      font-weight: 600;
      color: #111827;
      margin-bottom: 1rem;
    }
    
    .image-editor-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
    }
    
    .image-editor-button {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      border-radius: 0.5rem;
      border: 2px solid #d1d5db;
      transition: all 0.2s;
      background-color: white;
      cursor: pointer;
    }
    
    .image-editor-button:hover {
      border-color: #9ca3af;
    }
    
    .image-editor-button.active {
      border-color: #ea580c;
      background-color: #fff7ed;
      color: #9a3412;
    }
    
    .image-editor-reset-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      color: #4b5563;
      border-radius: 0.5rem;
      transition: all 0.2s;
      background-color: white;
      border: none;
      cursor: pointer;
    }
    
    .image-editor-reset-btn:hover {
      color: #111827;
      background-color: #f3f4f6;
    }
    
    .image-editor-warning-option {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 0.75rem;
      border-radius: 0.5rem;
      cursor: pointer;
    }
    
    .image-editor-warning-option:hover {
      background-color: #f9fafb;
    }
    
    .image-editor-warning-checkbox {
      width: 1.25rem;
      height: 1.25rem;
      color: #ea580c;
      border-radius: 0.25rem;
      accent-color: #ea580c;
    }
    
    .image-editor-warning-label {
      font-weight: 500;
      color: #111827;
    }
    
    .image-editor-warning-description {
      font-size: 0.875rem;
      color: #4b5563;
    }
    
    .image-editor-footer {
      display: flex;
      align-items: center;
      justify-content: flex-end;
      gap: 0.75rem;
      padding: 1.5rem;
      border-top: 1px solid #e5e7eb;
      background-color: #f9fafb;
    }
    
    .image-editor-cancel-btn {
      padding: 0.5rem 1.5rem;
      color: #4b5563;
      border-radius: 0.5rem;
      transition: all 0.2s;
      background-color: white;
      border: none;
      cursor: pointer;
    }
    
    .image-editor-cancel-btn:hover {
      color: #111827;
      background-color: #e5e7eb;
    }
    
    .image-editor-save-btn {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1.5rem;
      background-color: #ea580c;
      color: white;
      border-radius: 0.5rem;
      transition: all 0.2s;
      border: none;
      font-weight: 500;
      cursor: pointer;
    }
    
    .image-editor-save-btn:hover {
      background-color: #c2410c;
    }
    
    .hidden {
      display: none;
    }
  </style>
</head>
<body>
  <div id="image-editor" class="hidden">
    <div class="image-editor-overlay">
      <div class="image-editor-container">
        <!-- Header -->
        <div class="image-editor-header">
          <h2 class="image-editor-title">Edit Image</h2>
          <button id="close-btn" class="image-editor-close-btn">
            <i data-lucide="x" class="w-6 h-6 text-gray-600"></i>
          </button>
        </div>

        <!-- Tabs -->
        <div class="image-editor-tabs">
          <button id="crop-tab" class="image-editor-tab active">
            Crop & Resize
          </button>
          <button id="warning-tab" class="image-editor-tab">
            Content Warning
          </button>
        </div>

        <!-- Content -->
        <div class="image-editor-content">
          <!-- Crop Tab Content -->
          <div id="crop-content">
            <div class="image-editor-preview-container">
              <div id="react-crop-container">
                <img id="crop-image" alt="Crop preview" class="image-editor-preview" />
              </div>
            </div>

            <div class="image-editor-controls">
              <h3 class="image-editor-control-title">Aspect Ratio</h3>
              <div class="image-editor-buttons">
                <button id="free-ratio-btn" class="image-editor-button active">
                  <i data-lucide="maximize-2" class="w-4 h-4"></i>
                  Free
                </button>
                <button id="square-ratio-btn" class="image-editor-button">
                  <i data-lucide="square" class="w-4 h-4"></i>
                  Square
                </button>
                <button id="rectangle-ratio-btn" class="image-editor-button">
                  <i data-lucide="rectangle-horizontal" class="w-4 h-4"></i>
                  Rectangle
                </button>
              </div>
            </div>

            <button id="reset-crop-btn" class="image-editor-reset-btn">
              <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
              Reset Crop
            </button>
          </div>

          <!-- Warning Tab Content -->
          <div id="warning-content" class="hidden">
            <div class="image-editor-preview-container">
              <img id="warning-image" alt="Content warning preview" class="image-editor-preview" />
            </div>

            <div class="image-editor-controls">
              <h3 class="image-editor-control-title">Content Warnings</h3>
              <div class="space-y-3">
                <label class="image-editor-warning-option">
                  <input type="checkbox" id="nudity-warning" class="image-editor-warning-checkbox">
                  <div>
                    <div class="image-editor-warning-label">Nudity</div>
                    <div class="image-editor-warning-description">Contains nudity or sexual content</div>
                  </div>
                </label>
                
                <label class="image-editor-warning-option">
                  <input type="checkbox" id="violence-warning" class="image-editor-warning-checkbox">
                  <div>
                    <div class="image-editor-warning-label">Violence</div>
                    <div class="image-editor-warning-description">Contains violent or disturbing content</div>
                  </div>
                </label>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="image-editor-footer">
          <button id="cancel-btn" class="image-editor-cancel-btn">
            Cancel
          </button>
          <button id="save-btn" class="image-editor-save-btn">
            <i data-lucide="save" class="w-4 h-4"></i>
            Save Changes
          </button>
        </div>
      </div>
      
      <!-- Hidden canvas for cropping -->
      <canvas id="crop-canvas" class="hidden"></canvas>
    </div>
  </div>

  <script src="https://unpkg.com/react-image-crop@10.0.0/dist/ReactCrop.umd.js"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize Lucide icons
      lucide.createIcons();
      
      // Editor state
      const state = {
        isOpen: false,
        imageSrc: '',
        crop: undefined,
        completedCrop: undefined,
        activeTab: 'crop',
        aspectRatio: undefined,
        contentWarning: {
          nudity: false,
          violence: false
        },
        onSave: null,
        onClose: null
      };
      
      // DOM elements
      const editor = document.getElementById('image-editor');
      const cropImage = document.getElementById('crop-image');
      const warningImage = document.getElementById('warning-image');
      const cropCanvas = document.getElementById('crop-canvas');
      const reactCropContainer = document.getElementById('react-crop-container');
      
      // Initialize ReactCrop
      let reactCrop;
      
      // Tab controls
      document.getElementById('crop-tab').addEventListener('click', () => {
        state.activeTab = 'crop';
        document.getElementById('crop-content').classList.remove('hidden');
        document.getElementById('warning-content').classList.add('hidden');
        document.getElementById('crop-tab').classList.add('active');
        document.getElementById('warning-tab').classList.remove('active');
      });
      
      document.getElementById('warning-tab').addEventListener('click', () => {
        state.activeTab = 'warning';
        document.getElementById('crop-content').classList.add('hidden');
        document.getElementById('warning-content').classList.remove('hidden');
        document.getElementById('crop-tab').classList.remove('active');
        document.getElementById('warning-tab').classList.add('active');
        
        // Update warning image style
        warningImage.style.filter = (state.contentWarning.nudity || state.contentWarning.violence) ? 'blur(20px)' : 'none';
      });
      
      // Aspect ratio buttons
      document.getElementById('free-ratio-btn').addEventListener('click', () => {
        handleAspectRatioChange(undefined, 'Free');
        updateAspectRatioButtons(undefined);
      });
      
      document.getElementById('square-ratio-btn').addEventListener('click', () => {
        handleAspectRatioChange(1, 'Square');
        updateAspectRatioButtons(1);
      });
      
      document.getElementById('rectangle-ratio-btn').addEventListener('click', () => {
        handleAspectRatioChange(16/9, 'Rectangle');
        updateAspectRatioButtons(16/9);
      });
      
      // Content warning checkboxes
      document.getElementById('nudity-warning').addEventListener('change', (e) => {
        state.contentWarning.nudity = e.target.checked;
        if (e.target.checked) {
          state.contentWarning.violence = false;
          document.getElementById('violence-warning').checked = false;
        }
        warningImage.style.filter = e.target.checked ? 'blur(20px)' : 'none';
      });
      
      document.getElementById('violence-warning').addEventListener('change', (e) => {
        state.contentWarning.violence = e.target.checked;
        if (e.target.checked) {
          state.contentWarning.nudity = false;
          document.getElementById('nudity-warning').checked = false;
        }
        warningImage.style.filter = e.target.checked ? 'blur(20px)' : 'none';
      });
      
      // Reset crop button
      document.getElementById('reset-crop-btn').addEventListener('click', handleReset);
      
      // Close button
      document.getElementById('close-btn').addEventListener('click', () => {
        if (state.onClose) state.onClose();
        closeEditor();
      });
      
      // Cancel button
      document.getElementById('cancel-btn').addEventListener('click', () => {
        if (state.onClose) state.onClose();
        closeEditor();
      });
      
      // Save button
      document.getElementById('save-btn').addEventListener('click', handleSave);
      
      function updateAspectRatioButtons(selectedRatio) {
        const buttons = [
          document.getElementById('free-ratio-btn'),
          document.getElementById('square-ratio-btn'),
          document.getElementById('rectangle-ratio-btn')
        ];
        
        const ratios = [undefined, 1, 16/9];
        
        buttons.forEach((btn, index) => {
          if (ratios[index] === selectedRatio) {
            btn.classList.add('active');
          } else {
            btn.classList.remove('active');
          }
        });
      }
      
      function handleAspectRatioChange(ratio, label) {
        state.aspectRatio = ratio;
        
        if (cropImage && ratio) {
          const width = cropImage.naturalWidth || cropImage.width;
          const height = cropImage.naturalHeight || cropImage.height;
          
          if (reactCrop) {
            const newCrop = ReactCrop.centerCrop(
              ReactCrop.makeAspectCrop(
                {
                  unit: '%',
                  width: 80,
                },
                ratio,
                width,
                height
              ),
              width,
              height
            );
            
            reactCrop.setCrop(newCrop);
            state.crop = newCrop;
          }
        } else if (reactCrop) {
          reactCrop.setAspect(undefined);
        }
      }
      
      function onImageLoad(e) {
        const { width, height } = e.currentTarget;
        
        // Set initial crop to center 80% of image
        const crop = ReactCrop.centerCrop(
          ReactCrop.makeAspectCrop(
            {
              unit: '%',
              width: 80,
            },
            state.aspectRatio || width / height,
            width,
            height
          ),
          width,
          height
        );
        
        if (reactCrop) {
          reactCrop.setCrop(crop);
        }
        state.crop = crop;
      }
      
      async function getCroppedImg() {
        if (!state.completedCrop || !cropImage || !cropCanvas) {
          return null;
        }

        const ctx = cropCanvas.getContext('2d');
        if (!ctx) return null;

        const scaleX = cropImage.naturalWidth / cropImage.width;
        const scaleY = cropImage.naturalHeight / cropImage.height;

        cropCanvas.width = state.completedCrop.width;
        cropCanvas.height = state.completedCrop.height;

        ctx.drawImage(
          cropImage,
          state.completedCrop.x * scaleX,
          state.completedCrop.y * scaleY,
          state.completedCrop.width * scaleX,
          state.completedCrop.height * scaleY,
          0,
          0,
          state.completedCrop.width,
          state.completedCrop.height
        );

        return new Promise((resolve) => {
          cropCanvas.toBlob((blob) => {
            resolve(blob);
          }, 'image/jpeg', 0.9);
        });
      }
      
      async function handleSave() {
        const croppedImageBlob = await getCroppedImg();
        if (croppedImageBlob && state.onSave) {
          const cropData = {
            crop: state.completedCrop,
            contentWarning: state.contentWarning.nudity ? 'nudity' : state.contentWarning.violence ? 'violence' : 'none'
          };
          state.onSave(croppedImageBlob, cropData);
        }
        closeEditor();
      }
      
      function handleReset() {
        if (cropImage) {
          const width = cropImage.naturalWidth || cropImage.width;
          const height = cropImage.naturalHeight || cropImage.height;
          const newCrop = ReactCrop.centerCrop(
            ReactCrop.makeAspectCrop(
              {
                unit: '%',
                width: 80,
              },
              state.aspectRatio || width / height,
              width,
              height
            ),
            width,
            height
          );
          
          if (reactCrop) {
            reactCrop.setCrop(newCrop);
          }
          state.crop = newCrop;
        }
      }
      
      function openEditor(options) {
        state.isOpen = true;
        state.imageSrc = options.imageSrc;
        state.onSave = options.onSave || null;
        state.onClose = options.onClose || null;
        
        // Reset state
        state.crop = undefined;
        state.completedCrop = undefined;
        state.activeTab = 'crop';
        state.aspectRatio = undefined;
        state.contentWarning = { nudity: false, violence: false };
        
        // Update UI
        editor.classList.remove('hidden');
        document.getElementById('crop-content').classList.remove('hidden');
        document.getElementById('warning-content').classList.add('hidden');
        document.getElementById('crop-tab').classList.add('active');
        document.getElementById('warning-tab').classList.remove('hidden');
        updateAspectRatioButtons(undefined);
        document.getElementById('nudity-warning').checked = false;
        document.getElementById('violence-warning').checked = false;
        
        // Set images
        cropImage.src = options.imageSrc;
        warningImage.src = options.imageSrc;
        warningImage.style.filter = 'none';
        
        // Initialize ReactCrop after image is loaded
        cropImage.onload = function(e) {
          onImageLoad(e);
          
          // Initialize ReactCrop
          if (reactCrop) {
            reactCrop.destroy();
          }
          
          reactCrop = new ReactCrop.default(reactCropContainer, {
            crop: state.crop,
            onChange: (crop) => {
              state.crop = crop;
            },
            onComplete: (crop) => {
              state.completedCrop = crop;
            },
            aspect: state.aspectRatio
          });
          
          reactCrop.add(cropImage);
        };
      }
      
      function closeEditor() {
        state.isOpen = false;
        editor.classList.add('hidden');
        if (reactCrop) {
          reactCrop.destroy();
          reactCrop = null;
        }
      }
      
      // Expose the openEditor function to the global scope
      window.ImageEditor = {
        open: openEditor,
        close: closeEditor
      };
    });
  </script>
</body>
</html>
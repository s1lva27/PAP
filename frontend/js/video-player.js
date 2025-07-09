// Modern Video Player JavaScript - Fixed for Modal-Only Controls
class ModernVideoPlayer {
    constructor(videoElement) {
        this.video = videoElement;
        this.container = videoElement.closest('.modal-video-container');
        this.isPlaying = false;
        this.isDragging = false;
        this.currentTime = 0;
        this.duration = 0;
        this.volume = 1;
        this.isMuted = false;
        this.isFullscreen = false;

        this.init();
    }

    init() {
        // Hide native controls
        this.video.controls = false;
        this.video.preload = 'metadata';

        this.createControls();
        this.bindEvents();

        // Wait for video to load metadata
        if (this.video.readyState >= 1) {
            this.onLoadedMetadata();
        }
    }

    createControls() {
        const controlsHTML = `
      <div class="video-loading"></div>
      <div class="video-controls">
        <div class="progress-container">
          <div class="buffer-progress"></div>
          <div class="progress-bar">
            <div class="progress-handle"></div>
          </div>
        </div>
        <div class="controls-row">
          <div class="controls-left">
            <button class="control-btn play-pause-btn" title="Play/Pause">
              <i class="fas fa-play"></i>
            </button>
            <div class="volume-container">
              <button class="control-btn volume-btn" title="Mute/Unmute">
                <i class="fas fa-volume-up"></i>
              </button>
              <div class="volume-slider">
                <div class="volume-progress"></div>
              </div>
            </div>
            <div class="time-display">
              <span class="current-time">0:00</span> / <span class="duration">0:00</span>
            </div>
          </div>
          <div class="controls-right">
            <button class="control-btn fullscreen-btn" title="Fullscreen">
              <i class="fas fa-expand"></i>
            </button>
          </div>
        </div>
      </div>
    `;

        this.container.insertAdjacentHTML('beforeend', controlsHTML);
        this.cacheElements();
    }

    cacheElements() {
        this.playPauseBtn = this.container.querySelector('.play-pause-btn');
        this.progressContainer = this.container.querySelector('.progress-container');
        this.progressBar = this.container.querySelector('.progress-bar');
        this.progressHandle = this.container.querySelector('.progress-handle');
        this.bufferProgress = this.container.querySelector('.buffer-progress');
        this.volumeBtn = this.container.querySelector('.volume-btn');
        this.volumeSlider = this.container.querySelector('.volume-slider');
        this.volumeProgress = this.container.querySelector('.volume-progress');
        this.currentTimeEl = this.container.querySelector('.current-time');
        this.durationEl = this.container.querySelector('.duration');
        this.fullscreenBtn = this.container.querySelector('.fullscreen-btn');
        this.videoControls = this.container.querySelector('.video-controls');
        this.videoLoading = this.container.querySelector('.video-loading');
    }

    bindEvents() {
        // Video events
        this.video.addEventListener('loadedmetadata', () => this.onLoadedMetadata());
        this.video.addEventListener('timeupdate', () => this.onTimeUpdate());
        this.video.addEventListener('progress', () => this.onProgress());
        this.video.addEventListener('ended', () => this.onEnded());
        this.video.addEventListener('play', () => this.onPlay());
        this.video.addEventListener('pause', () => this.onPause());
        this.video.addEventListener('waiting', () => this.onWaiting());
        this.video.addEventListener('canplay', () => this.onCanPlay());
        this.video.addEventListener('loadstart', () => this.onLoadStart());
        this.video.addEventListener('error', (e) => this.onError(e));

        // Control events
        this.playPauseBtn.addEventListener('click', () => this.togglePlay());
        this.video.addEventListener('click', () => this.togglePlay());

        // Progress bar events
        this.progressContainer.addEventListener('click', (e) => this.onProgressClick(e));
        this.progressContainer.addEventListener('mousedown', (e) => this.onProgressMouseDown(e));

        // Volume events
        this.volumeBtn.addEventListener('click', () => this.toggleMute());
        this.volumeSlider.addEventListener('click', (e) => this.onVolumeClick(e));

        // Fullscreen events
        this.fullscreenBtn.addEventListener('click', () => this.toggleFullscreen());
        document.addEventListener('fullscreenchange', () => this.onFullscreenChange());
        document.addEventListener('webkitfullscreenchange', () => this.onFullscreenChange());
        document.addEventListener('mozfullscreenchange', () => this.onFullscreenChange());

        // Keyboard events
        this.container.addEventListener('keydown', (e) => this.onKeyDown(e));
        this.container.setAttribute('tabindex', '0');

        // Mouse events for dragging
        document.addEventListener('mousemove', (e) => this.onMouseMove(e));
        document.addEventListener('mouseup', () => this.onMouseUp());

        // Touch events for mobile
        this.progressContainer.addEventListener('touchstart', (e) => this.onTouchStart(e));
        this.progressContainer.addEventListener('touchmove', (e) => this.onTouchMove(e));
        this.progressContainer.addEventListener('touchend', () => this.onTouchEnd());

        // Show/hide controls on hover
        this.container.addEventListener('mouseenter', () => this.showControls());
        this.container.addEventListener('mouseleave', () => this.hideControls());
        this.container.addEventListener('mousemove', () => this.showControls());

        // Auto-hide controls timer
        this.controlsTimer = null;
    }

    showControls() {
        this.videoControls.classList.add('visible');
        clearTimeout(this.controlsTimer);

        if (this.isPlaying) {
            this.controlsTimer = setTimeout(() => {
                this.hideControls();
            }, 3000);
        }
    }

    hideControls() {
        if (!this.container.matches(':hover') && this.isPlaying) {
            this.videoControls.classList.remove('visible');
        }
    }

    onLoadStart() {
        this.container.classList.add('loading');
    }

    onWaiting() {
        this.container.classList.add('loading');
    }

    onCanPlay() {
        this.container.classList.remove('loading');
    }

    onError(e) {
        console.error('Video error:', e);
        this.container.classList.remove('loading');
        // Show error message to user
        const errorMsg = document.createElement('div');
        errorMsg.className = 'video-error';
        errorMsg.textContent = 'Erro ao carregar o vídeo';
        errorMsg.style.cssText = `
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      color: white;
      background: rgba(0,0,0,0.8);
      padding: 10px 20px;
      border-radius: 5px;
    `;
        this.container.appendChild(errorMsg);
    }

    onLoadedMetadata() {
        this.duration = this.video.duration;
        this.updateTimeDisplay();
        this.updateVolumeDisplay();
        this.container.classList.remove('loading');

        // Initialize progress bar
        this.updateProgressBar();

        // Show controls initially
        this.showControls();
    }

    onTimeUpdate() {
        if (!this.isDragging) {
            this.currentTime = this.video.currentTime;
            this.updateProgressBar();
            this.updateTimeDisplay();
        }
    }

    onProgress() {
        if (this.video.buffered.length > 0 && this.duration > 0) {
            const bufferedEnd = this.video.buffered.end(this.video.buffered.length - 1);
            const bufferedPercent = (bufferedEnd / this.duration) * 100;
            this.bufferProgress.style.width = `${bufferedPercent}%`;
        }
    }

    onPlay() {
        this.isPlaying = true;
        this.container.classList.add('playing');
        this.container.classList.remove('paused');
        this.playPauseBtn.innerHTML = '<i class="fas fa-pause"></i>';
        this.playPauseBtn.title = 'Pause';
        this.showControls();
    }

    onPause() {
        this.isPlaying = false;
        this.container.classList.add('paused');
        this.container.classList.remove('playing');
        this.playPauseBtn.innerHTML = '<i class="fas fa-play"></i>';
        this.playPauseBtn.title = 'Play';
        this.showControls();
        clearTimeout(this.controlsTimer);
    }

    onEnded() {
        this.isPlaying = false;
        this.container.classList.add('paused');
        this.container.classList.remove('playing');
        this.playPauseBtn.innerHTML = '<i class="fas fa-replay"></i>';
        this.playPauseBtn.title = 'Replay';
        this.showControls();
        clearTimeout(this.controlsTimer);
    }

    togglePlay() {
        if (this.video.paused || this.video.ended) {
            const playPromise = this.video.play();
            if (playPromise !== undefined) {
                playPromise.catch(error => {
                    console.error('Play failed:', error);
                    this.container.classList.remove('loading');
                });
            }
        } else {
            this.video.pause();
        }
    }

    onProgressClick(e) {
        if (this.duration > 0) {
            const rect = this.progressContainer.getBoundingClientRect();
            const percent = (e.clientX - rect.left) / rect.width;
            this.seekTo(percent);
        }
    }

    onProgressMouseDown(e) {
        this.isDragging = true;
        this.onProgressClick(e);
        e.preventDefault();
    }

    onMouseMove(e) {
        if (this.isDragging && this.duration > 0) {
            const rect = this.progressContainer.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
            this.seekTo(percent);
        }
    }

    onMouseUp() {
        this.isDragging = false;
    }

    onTouchStart(e) {
        if (this.duration > 0) {
            this.isDragging = true;
            const touch = e.touches[0];
            const rect = this.progressContainer.getBoundingClientRect();
            const percent = (touch.clientX - rect.left) / rect.width;
            this.seekTo(percent);
            e.preventDefault();
        }
    }

    onTouchMove(e) {
        if (this.isDragging && this.duration > 0) {
            e.preventDefault();
            const touch = e.touches[0];
            const rect = this.progressContainer.getBoundingClientRect();
            const percent = Math.max(0, Math.min(1, (touch.clientX - rect.left) / rect.width));
            this.seekTo(percent);
        }
    }

    onTouchEnd() {
        this.isDragging = false;
    }

    seekTo(percent) {
        if (this.duration > 0) {
            const time = percent * this.duration;
            this.video.currentTime = time;
            this.currentTime = time;
            this.updateProgressBar();
            this.updateTimeDisplay();
        }
    }

    updateProgressBar() {
        if (this.duration > 0) {
            const percent = (this.currentTime / this.duration) * 100;
            this.progressBar.style.width = `${percent}%`;
        }
    }

    updateTimeDisplay() {
        this.currentTimeEl.textContent = this.formatTime(this.currentTime);
        this.durationEl.textContent = this.formatTime(this.duration);
    }

    formatTime(seconds) {
        if (isNaN(seconds) || seconds < 0) return '0:00';

        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
    }

    toggleMute() {
        if (this.video.muted) {
            this.video.muted = false;
            this.isMuted = false;
        } else {
            this.video.muted = true;
            this.isMuted = true;
        }
        this.updateVolumeDisplay();
    }

    onVolumeClick(e) {
        const rect = this.volumeSlider.getBoundingClientRect();
        const percent = (e.clientX - rect.left) / rect.width;
        this.video.volume = Math.max(0, Math.min(1, percent));
        this.volume = this.video.volume;
        this.video.muted = false;
        this.isMuted = false;
        this.updateVolumeDisplay();
    }

    updateVolumeDisplay() {
        const volume = this.video.muted ? 0 : this.video.volume;
        this.volumeProgress.style.width = `${volume * 100}%`;

        if (volume === 0 || this.video.muted) {
            this.volumeBtn.innerHTML = '<i class="fas fa-volume-mute"></i>';
            this.volumeBtn.title = 'Unmute';
        } else if (volume < 0.5) {
            this.volumeBtn.innerHTML = '<i class="fas fa-volume-down"></i>';
            this.volumeBtn.title = 'Mute';
        } else {
            this.volumeBtn.innerHTML = '<i class="fas fa-volume-up"></i>';
            this.volumeBtn.title = 'Mute';
        }
    }

    toggleFullscreen() {
        if (!this.isFullscreen) {
            this.enterFullscreen();
        } else {
            this.exitFullscreen();
        }
    }

    enterFullscreen() {
        const element = this.container;

        if (element.requestFullscreen) {
            element.requestFullscreen();
        } else if (element.webkitRequestFullscreen) {
            element.webkitRequestFullscreen();
        } else if (element.mozRequestFullScreen) {
            element.mozRequestFullScreen();
        } else if (element.msRequestFullscreen) {
            element.msRequestFullscreen();
        }
    }

    exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        } else if (document.webkitExitFullscreen) {
            document.webkitExitFullscreen();
        } else if (document.mozCancelFullScreen) {
            document.mozCancelFullScreen();
        } else if (document.msExitFullscreen) {
            document.msExitFullscreen();
        }
    }

    onFullscreenChange() {
        this.isFullscreen = !!(document.fullscreenElement ||
            document.webkitFullscreenElement ||
            document.mozFullScreenElement ||
            document.msFullscreenElement);

        if (this.isFullscreen) {
            this.fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i>';
            this.fullscreenBtn.title = 'Exit Fullscreen';
            this.container.classList.add('fullscreen');
        } else {
            this.fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i>';
            this.fullscreenBtn.title = 'Fullscreen';
            this.container.classList.remove('fullscreen');
        }
    }

    onKeyDown(e) {
        switch (e.code) {
            case 'Space':
                e.preventDefault();
                this.togglePlay();
                break;
            case 'ArrowLeft':
                e.preventDefault();
                this.video.currentTime = Math.max(0, this.video.currentTime - 10);
                break;
            case 'ArrowRight':
                e.preventDefault();
                this.video.currentTime = Math.min(this.duration, this.video.currentTime + 10);
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.video.volume = Math.min(1, this.video.volume + 0.1);
                this.updateVolumeDisplay();
                break;
            case 'ArrowDown':
                e.preventDefault();
                this.video.volume = Math.max(0, this.video.volume - 0.1);
                this.updateVolumeDisplay();
                break;
            case 'KeyM':
                e.preventDefault();
                this.toggleMute();
                break;
            case 'KeyF':
                e.preventDefault();
                this.toggleFullscreen();
                break;
        }
    }

    destroy() {
        clearTimeout(this.controlsTimer);
        // Remove event listeners and clean up
        this.video.controls = true;
        const controls = this.container.querySelector('.video-controls');
        const loading = this.container.querySelector('.video-loading');

        if (controls) controls.remove();
        if (loading) loading.remove();
    }
}

// Function to get video duration for thumbnails
function getVideoDuration(videoElement) {
    return new Promise((resolve) => {
        if (videoElement.duration && !isNaN(videoElement.duration)) {
            resolve(videoElement.duration);
        } else {
            const handleLoadedMetadata = () => {
                resolve(videoElement.duration);
                videoElement.removeEventListener('loadedmetadata', handleLoadedMetadata);
            };
            videoElement.addEventListener('loadedmetadata', handleLoadedMetadata);

            // Force load metadata if not already loaded
            if (videoElement.readyState < 1) {
                videoElement.load();
            }
        }
    });
}

// Function to format time for duration display
function formatDuration(seconds) {
    if (isNaN(seconds) || seconds < 0) return '0:00';

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = Math.floor(seconds % 60);
    return `${minutes}:${remainingSeconds.toString().padStart(2, '0')}`;
}

// Initialize video thumbnails in posts
function initializeVideoThumbnails() {
    const videoContainers = document.querySelectorAll('.video-container:not([data-thumbnail-initialized])');

    videoContainers.forEach(container => {
        const video = container.querySelector('video');
        if (!video) return;

        // Mark as initialized
        container.dataset.thumbnailInitialized = 'true';

        // Ensure video is properly configured for thumbnail
        video.muted = true;
        video.preload = 'metadata';
        video.controls = false;
        video.playsInline = true;

        // Create thumbnail overlay
        const overlay = document.createElement('div');
        overlay.className = 'video-thumbnail-overlay';

        const playButton = document.createElement('button');
        playButton.className = 'video-play-button';
        playButton.innerHTML = '<i class="fas fa-play"></i>';

        overlay.appendChild(playButton);
        container.appendChild(overlay);

        // Add duration display
        getVideoDuration(video).then(duration => {
            if (duration && !isNaN(duration)) {
                const durationEl = document.createElement('div');
                durationEl.className = 'video-duration';
                durationEl.textContent = formatDuration(duration);
                container.appendChild(durationEl);
            }
        });

        // Handle click to open modal
        container.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            // Get the video source
            const source = video.querySelector('source');
            const videoSrc = source ? source.src : video.src;

            if (videoSrc) {
                openVideoModal(videoSrc);
            }
        });
    });
}

// Function to open video in modal
// Modifique a função openVideoModal para lidar com múltiplos vídeos
function openVideoModal(videoSrc, postId, mediaIndex = 0) {
    // Obter todas as mídias do post
    const postElement = document.querySelector(`.post[data-post-id="${postId}"]`);
    if (!postElement) return;

    const medias = [];
    const mediaElements = postElement.querySelectorAll('.media-item');

    mediaElements.forEach(item => {
        const videoElement = item.querySelector('video');
        const imgElement = item.querySelector('img');

        if (videoElement) {
            const source = videoElement.querySelector('source');
            medias.push({
                url: source ? source.src.split('/').pop() : '',
                tipo: 'video'
            });
        } else if (imgElement) {
            medias.push({
                url: imgElement.src.split('/').pop(),
                tipo: 'imagem'
            });
        }
    });

    if (medias.length === 0) return;

    // Criar modal
    const modal = document.createElement('div');
    modal.className = 'image-modal';
    modal.style.display = 'flex';

    const modalContent = document.createElement('div');
    modalContent.className = 'image-modal-content';

    const closeBtn = document.createElement('button');
    closeBtn.className = 'close-image-modal';
    closeBtn.innerHTML = '&times;';

    // Controles de navegação
    const navControls = document.createElement('div');
    navControls.className = 'image-modal-nav';

    const prevBtn = document.createElement('button');
    prevBtn.className = 'modal-nav-btn';
    prevBtn.id = 'prevImageBtn';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';

    const counter = document.createElement('span');
    counter.className = 'image-counter';
    counter.id = 'imageCounter';

    const nextBtn = document.createElement('button');
    nextBtn.className = 'modal-nav-btn';
    nextBtn.id = 'nextImageBtn';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';

    navControls.appendChild(prevBtn);
    navControls.appendChild(counter);
    navControls.appendChild(nextBtn);

    const videoContainer = document.createElement('div');
    videoContainer.className = 'modal-video-container';
    videoContainer.id = 'modalVideoContainer';

    modalContent.appendChild(closeBtn);
    modalContent.appendChild(videoContainer);
    modal.appendChild(modalContent);
    modal.appendChild(navControls);
    document.body.appendChild(modal);

    // Estado atual do modal
    const modalState = {
        currentIndex: mediaIndex,
        medias: medias,
        player: null
    };

    // Função para carregar a mídia atual
    function loadCurrentMedia() {
        videoContainer.innerHTML = '';
        const currentMedia = modalState.medias[modalState.currentIndex];

        if (currentMedia.tipo === 'video') {
            const video = document.createElement('video');
            video.src = `images/publicacoes/${currentMedia.url}`;
            video.autoplay = false;
            video.controls = false;
            video.muted = false;
            video.preload = 'metadata';
            video.playsInline = true;

            videoContainer.appendChild(video);

            // Inicializar player
            modalState.player = new ModernVideoPlayer(video);
        } else {
            const img = document.createElement('img');
            img.src = `images/publicacoes/${currentMedia.url}`;
            img.className = 'modal-media';
            videoContainer.appendChild(img);
        }

        // Atualizar contador
        counter.textContent = `${modalState.currentIndex + 1} / ${modalState.medias.length}`;

        // Atualizar estado dos botões
        prevBtn.disabled = modalState.currentIndex === 0;
        nextBtn.disabled = modalState.currentIndex === modalState.medias.length - 1;
    }

    // Navegação
    function navigate(direction) {
        if (direction === 'prev' && modalState.currentIndex > 0) {
            modalState.currentIndex--;
        } else if (direction === 'next' && modalState.currentIndex < modalState.medias.length - 1) {
            modalState.currentIndex++;
        }
        loadCurrentMedia();
    }

    // Event listeners
    prevBtn.addEventListener('click', () => navigate('prev'));
    nextBtn.addEventListener('click', () => navigate('next'));

    // Fechar modal
    const closeModal = () => {
        if (modalState.player) {
            modalState.player.destroy();
        }
        document.body.removeChild(modal);
        document.body.style.overflow = 'auto';
    };

    closeBtn.addEventListener('click', closeModal);
    modal.addEventListener('click', (e) => {
        if (e.target === modal) closeModal();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') closeModal();
        if (e.key === 'ArrowLeft') navigate('prev');
        if (e.key === 'ArrowRight') navigate('next');
    });

    // Carregar mídia inicial
    loadCurrentMedia();
    document.body.style.overflow = 'hidden';
}

// Atualize a função initializeVideoThumbnails para lidar com múltiplos vídeos
function initializeVideoThumbnails() {
    const videoContainers = document.querySelectorAll('.video-container:not([data-thumbnail-initialized])');

    videoContainers.forEach(container => {
        const video = container.querySelector('video');
        if (!video) return;

        container.dataset.thumbnailInitialized = 'true';
        video.muted = true;
        video.preload = 'metadata';
        video.controls = false;
        video.playsInline = true;

        // Criar overlay
        const overlay = document.createElement('div');
        overlay.className = 'video-thumbnail-overlay';

        const playButton = document.createElement('button');
        playButton.className = 'video-play-button';
        playButton.innerHTML = '<i class="fas fa-play"></i>';

        overlay.appendChild(playButton);
        container.appendChild(overlay);

        // Adicionar duração
        getVideoDuration(video).then(duration => {
            if (duration && !isNaN(duration)) {
                const durationEl = document.createElement('div');
                durationEl.className = 'video-duration';
                durationEl.textContent = formatDuration(duration);
                container.appendChild(durationEl);
            }
        });

        // Lidar com clique
        container.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const postElement = container.closest('.post');
            if (!postElement) return;

            const postId = postElement.dataset.postId;
            const mediaIndex = Array.from(postElement.querySelectorAll('.media-item')).indexOf(container.closest('.media-item'));

            const source = video.querySelector('source');
            const videoSrc = source ? source.src : video.src;

            if (videoSrc && postId) {
                openVideoModal(videoSrc, postId, mediaIndex);
            }
        });
    });
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
    initializeVideoThumbnails();
});

// Function to initialize new videos (for dynamically loaded content)
function initializeNewVideos() {
    initializeVideoThumbnails();
}

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ModernVideoPlayer, initializeVideoThumbnails, initializeNewVideos };
}

// Make functions globally available
window.ModernVideoPlayer = ModernVideoPlayer;
window.initializeVideoThumbnails = initializeVideoThumbnails;
window.initializeNewVideos = initializeNewVideos;
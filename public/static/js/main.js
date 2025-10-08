class LogoAnimation {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        if (this.container) {
            this.init();
        }
    }

    init() {
        const canvas = document.createElement('canvas');
        canvas.width = this.container.offsetWidth || 50;
        canvas.height = this.container.offsetHeight || 50;
        this.container.appendChild(canvas);

        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = Math.min(centerX, centerY) - 5;

        const nodes = [];
        const nodeCount = 8;
        for (let i = 0; i < nodeCount; i++) {
            const angle = (i / nodeCount) * Math.PI * 2;
            nodes.push({
                baseAngle: angle,
                currentAngle: angle,
                pulse: 0
            });
        }

        let rotation = 0;

        const animate = () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            rotation += 0.01;

            nodes.forEach((node, index) => {
                node.currentAngle = node.baseAngle + rotation;
                node.pulse = Math.sin(Date.now() * 0.003 + index) * 2;

                const x = centerX + Math.cos(node.currentAngle) * radius;
                const y = centerY + Math.sin(node.currentAngle) * radius;

                ctx.strokeStyle = 'rgba(139, 21, 56, 0.3)';
                ctx.lineWidth = 1;
                ctx.beginPath();
                ctx.moveTo(centerX, centerY);
                ctx.lineTo(x, y);
                ctx.stroke();

                ctx.fillStyle = '#8B1538';
                ctx.beginPath();
                ctx.arc(x, y, 3 + node.pulse, 0, Math.PI * 2);
                ctx.fill();
            });

            ctx.fillStyle = '#8B1538';
            ctx.strokeStyle = '#A52A4A';
            ctx.lineWidth = 2;
            const boxSize = 20;
            ctx.fillRect(centerX - boxSize / 2, centerY - boxSize / 2, boxSize, boxSize);
            ctx.strokeRect(centerX - boxSize / 2, centerY - boxSize / 2, boxSize, boxSize);

            ctx.fillStyle = 'white';
            ctx.font = 'bold 12px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText('IN', centerX, centerY);

            requestAnimationFrame(animate);
        };

        animate();
    }
}

class AntiCheat {
    constructor(onWarning, logoutUrl = '/logout', restartUrl = null, attemptId = null) {
        this.warningCount = 0;
        this.maxWarnings = 1;
        this.onWarning = onWarning;
        this.enabled = false;
        this.logoutUrl = logoutUrl;
        this.restartUrl = restartUrl;
        this.tabSwitchCount = 0;
        this.maxTabSwitches = 1;
        this.attemptId = attemptId;
        this.isUploadingFile = false;
        this.visibilityTimeout = null;
    }

    enable() {
        this.enabled = true;
        this.setupListeners();
    }

    disable() {
        this.enabled = false;
        this.removeListeners();
    }

    setupListeners() {
        this.copyHandler = this.handleCopy.bind(this);
        this.cutHandler = this.handleCut.bind(this);
        this.contextHandler = this.handleContextMenu.bind(this);
        this.keyHandler = this.handleKeyDown.bind(this);
        this.visibilityHandler = this.handleVisibilityChange.bind(this);
        this.blurHandler = this.handleWindowBlur.bind(this);
        this.mouseHandler = this.handleMouseDown.bind(this);
        
        document.addEventListener('copy', this.copyHandler);
        document.addEventListener('cut', this.cutHandler);
        document.addEventListener('contextmenu', this.contextHandler);
        document.addEventListener('keydown', this.keyHandler);
        document.addEventListener('keyup', this.keyHandler);
        document.addEventListener('visibilitychange', this.visibilityHandler);
        window.addEventListener('blur', this.blurHandler);
        document.addEventListener('mousedown', this.mouseHandler);
        
        this.checkDevTools();
        this.devToolsInterval = setInterval(() => this.checkDevTools(), 1000);
    }

    removeListeners() {
        document.removeEventListener('copy', this.copyHandler);
        document.removeEventListener('cut', this.cutHandler);
        document.removeEventListener('contextmenu', this.contextHandler);
        document.removeEventListener('keydown', this.keyHandler);
        document.removeEventListener('keyup', this.keyHandler);
        document.removeEventListener('visibilitychange', this.visibilityHandler);
        window.removeEventListener('blur', this.blurHandler);
        document.removeEventListener('mousedown', this.mouseHandler);
        
        if (this.devToolsInterval) {
            clearInterval(this.devToolsInterval);
        }
    }

    handleCopy(e) {
        if (this.enabled) {
            e.preventDefault();
            this.triggerLogout('Terdeteksi mencoba copy! Anda akan dikeluarkan dari ujian.');
        }
    }

    handleCut(e) {
        if (this.enabled) {
            e.preventDefault();
            this.triggerLogout('Terdeteksi mencoba cut! Anda akan dikeluarkan dari ujian.');
        }
    }

    handleContextMenu(e) {
        if (this.enabled) {
            e.preventDefault();
            this.showWarningNotification('Klik kanan tidak diperbolehkan!');
        }
    }

    handleMouseDown(e) {
        if (this.enabled && e.button === 1) {
            e.preventDefault();
            this.showWarningNotification('Tombol tengah mouse tidak diperbolehkan!');
        }
    }

    checkDevTools() {
        if (!this.enabled) return;
        
        const threshold = 160;
        const widthThreshold = window.outerWidth - window.innerWidth > threshold;
        const heightThreshold = window.outerHeight - window.innerHeight > threshold;
        
        if (widthThreshold || heightThreshold) {
            this.triggerLogout('Developer Tools terdeteksi! Anda akan dikeluarkan dari ujian.');
        }
    }

    handleKeyDown(e) {
        if (this.enabled) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'v' || e.key === 'x')) {
                e.preventDefault();
                this.triggerLogout('Terdeteksi menggunakan shortcut copy/paste! Anda akan dikeluarkan dari ujian.');
                return;
            }

            if (e.key === 'PrintScreen' || e.key === 'Print' || e.code === 'PrintScreen') {
                e.preventDefault();
                this.triggerLogout('Screenshot terdeteksi! Anda akan dikeluarkan dari ujian.');
                return;
            }

            if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'J' || e.key === 'C'))) {
                e.preventDefault();
                this.triggerLogout('Developer Tools terdeteksi! Anda akan dikeluarkan dari ujian.');
                return;
            }

            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.triggerLogout('Screenshot terdeteksi! Anda akan dikeluarkan dari ujian.');
                return;
            }

            if ((e.metaKey || e.ctrlKey) && e.shiftKey && (e.key === '3' || e.key === '4' || e.key === '5')) {
                e.preventDefault();
                this.triggerLogout('Screenshot terdeteksi! Anda akan dikeluarkan dari ujian.');
                return;
            }
        }
    }

    handleVisibilityChange() {
        if (this.enabled && document.hidden && !this.isUploadingFile) {
            console.log('⚠️ Tab/Window tidak terlihat - menunggu 3 detik...');
            
            if (this.visibilityTimeout) {
                clearTimeout(this.visibilityTimeout);
            }
            
            this.visibilityTimeout = setTimeout(() => {
                if (document.hidden && this.enabled && !this.isUploadingFile) {
                    console.log('❌ Masih tidak terlihat setelah 3 detik - LOGOUT!');
                    this.triggerLogout('Terdeteksi pindah tab/aplikasi terlalu lama! Anda akan dikeluarkan dari ujian.');
                }
            }, 3000);
        } else if (!document.hidden && this.visibilityTimeout) {
            console.log('✓ Tab kembali terlihat - batalkan timeout');
            clearTimeout(this.visibilityTimeout);
            this.visibilityTimeout = null;
        }
    }

    handleWindowBlur() {
        if (this.enabled && !this.isUploadingFile) {
            console.log('⚠️ Window blur terdeteksi - menunggu 3 detik...');
            
            if (this.visibilityTimeout) {
                clearTimeout(this.visibilityTimeout);
            }
            
            this.visibilityTimeout = setTimeout(() => {
                if (!document.hasFocus() && this.enabled && !this.isUploadingFile) {
                    console.log('❌ Window masih blur setelah 3 detik - LOGOUT!');
                    this.triggerLogout('Terdeteksi pindah window/aplikasi terlalu lama! Anda akan dikeluarkan dari ujian.');
                }
            }, 3000);
        }
    }
    
    setUploadingState(isUploading) {
        this.isUploadingFile = isUploading;
        if (this.visibilityTimeout) {
            clearTimeout(this.visibilityTimeout);
            this.visibilityTimeout = null;
        }
    }

    async markCheating() {
        if (this.attemptId) {
            try {
                const formData = new FormData();
                formData.append('action', 'mark_cheating');
                
                await fetch(window.location.pathname, {
                    method: 'POST',
                    body: formData
                });
            } catch (error) {
                console.error('Failed to mark cheating:', error);
            }
        }
    }

    triggerLogout(message) {
        this.disable();
        this.markCheating().then(() => {
            this.showCountdownModal(message, this.logoutUrl);
        });
    }
    
    showCountdownModal(message, redirectUrl) {
        const modal = document.createElement('div');
        modal.id = 'cheatingCountdownModal';
        modal.className = 'modal';
        modal.style.display = 'block';
        modal.innerHTML = `
            <div class="modal-content" style="max-width: 500px; margin: 100px auto; text-align: center;">
                <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 2rem; border-radius: 8px 8px 0 0;">
                    <h2 style="margin: 0; font-size: 1.8rem;">⛔ TERDETEKSI CURANG!</h2>
                </div>
                <div style="padding: 2rem; background: white;">
                    <div style="font-size: 3rem; font-weight: bold; color: #dc3545; margin: 1rem 0;" id="countdownTimer">3</div>
                    <p style="font-size: 1.1rem; margin: 1rem 0;">${message}</p>
                    <p style="color: #666;">Anda akan dikeluarkan dalam <span id="countdownText">3</span> detik...</p>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 1rem; margin-top: 1rem;">
                        <strong>⚠️ Peringatan:</strong> Pelanggaran ini telah dicatat dalam sistem!
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        let countdown = 3;
        const countdownInterval = setInterval(() => {
            countdown--;
            const timerElement = document.getElementById('countdownTimer');
            const textElement = document.getElementById('countdownText');
            if (timerElement && textElement) {
                timerElement.textContent = countdown;
                textElement.textContent = countdown;
            }
            
            if (countdown <= 0) {
                clearInterval(countdownInterval);
                window.location.href = redirectUrl;
            }
        }, 1000);
    }

    triggerRestart(message) {
        this.disable();
        this.markCheating().then(() => {
            alert(message + '\n\n⛔ ANDA TERDETEKSI CURANG!\nAnda tidak akan bisa mengerjakan TO ini lagi.');
            window.location.href = this.restartUrl;
        });
    }

    addWarning(message) {
        this.warningCount++;
        
        if (this.onWarning) {
            this.onWarning(this.warningCount, message);
        }

        this.showWarningNotification(message);
    }

    showWarningNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-danger';
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            left: auto;
            z-index: 9999;
            animation: slideDown 0.3s ease;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            border-radius: 8px;
            padding: 15px 20px;
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
            color: white;
            border: none;
            font-size: 0.95rem;
        `;
        notification.innerHTML = `
            <strong style="display: block; margin-bottom: 5px;">🔒 Peringatan Anti-Cheating:</strong>
            <span style="opacity: 0.95;">${message}</span>
        `;
        document.body.appendChild(notification);

        const isMobile = window.innerWidth <= 768;
        if (isMobile) {
            notification.style.cssText += `
                left: 10px;
                right: 10px;
                top: 80px;
                max-width: calc(100% - 20px);
            `;
        }

        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

let securityWarningCallback = null;

function showSecurityWarningModal(callback) {
    securityWarningCallback = callback;
    
    const modal = document.createElement('div');
    modal.id = 'securityWarningModal';
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px; margin: 20px auto; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; padding: 20px;">
                <h2 style="margin: 0; font-size: 1.5rem; text-align: center;">🔒 Anti-Kecurangan Ketat</h2>
                <p style="margin: 8px 0 0 0; font-size: 0.9rem; text-align: center; opacity: 0.95;">Proteksi copy-paste, screenshot, dan pindah tab selama ujian</p>
            </div>
            <div class="modal-body" style="padding: 25px;">
                <div style="text-align: left; line-height: 1.8;">
                    <h3 style="color: #dc3545; margin-bottom: 20px; font-size: 1.2rem;">⚠️ Peraturan Selama Ujian:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <strong style="font-size: 1rem;">🚫 DILARANG COPY-PASTE</strong><br>
                            <small style="color: #666; line-height: 1.6;">Jika terdeteksi mencoba copy, Anda akan OTOMATIS LOGOUT</small>
                        </li>
                        <li style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <strong style="font-size: 1rem;">🚫 DILARANG PINDAH TAB/WINDOW (LEBIH DARI 3 DETIK)</strong><br>
                            <small style="color: #666; line-height: 1.6;">Jika pindah tab lebih dari 3 detik, Anda akan OTOMATIS LOGOUT</small>
                        </li>
                        <li style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #dc3545;">
                            <strong style="font-size: 1rem;">🚫 DILARANG SCREENSHOT</strong><br>
                            <small style="color: #666; line-height: 1.6;">Screenshot dan Developer Tools akan logout otomatis</small>
                        </li>
                        <li style="margin-bottom: 15px; padding: 12px; background: #d4edda; border-radius: 8px; border-left: 4px solid #28a745;">
                            <strong style="font-size: 1rem;">✅ FOKUS PADA UJIAN</strong><br>
                            <small style="color: #155724; line-height: 1.6;">Tetap di halaman ujian sampai selesai</small>
                        </li>
                    </ul>
                    <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 8px; margin-top: 20px;">
                        <strong style="color: #856404;">⚡ Penting:</strong> <span style="color: #856404;">Sistem akan memantau aktivitas Anda selama ujian. Pelanggaran akan mengakibatkan tindakan otomatis.</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; padding: 20px; border-top: 1px solid #dee2e6;">
                <button onclick="acceptSecurityWarning()" class="btn btn-danger" style="padding: 15px 30px; font-size: 1.1rem; font-weight: bold; width: 100%; border-radius: 8px; border: none; cursor: pointer; transition: all 0.3s ease;">
                    Saya Mengerti dan Setuju
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
    
    const style = document.createElement('style');
    style.textContent = `
        @media (max-width: 768px) {
            #securityWarningModal .modal-content {
                max-width: 95% !important;
                margin: 10px auto !important;
            }
            #securityWarningModal .modal-header h2 {
                font-size: 1.3rem !important;
            }
            #securityWarningModal .modal-header p {
                font-size: 0.85rem !important;
            }
            #securityWarningModal .modal-body {
                padding: 20px 15px !important;
            }
            #securityWarningModal .modal-body h3 {
                font-size: 1.1rem !important;
            }
            #securityWarningModal .modal-body li {
                padding: 10px !important;
                margin-bottom: 12px !important;
            }
            #securityWarningModal .modal-body strong {
                font-size: 0.95rem !important;
            }
            #securityWarningModal .modal-footer button {
                padding: 12px 20px !important;
                font-size: 1rem !important;
            }
        }
        @media (max-width: 480px) {
            #securityWarningModal .modal-content {
                max-width: 98% !important;
                margin: 5px auto !important;
            }
            #securityWarningModal .modal-header {
                padding: 15px !important;
            }
            #securityWarningModal .modal-header h2 {
                font-size: 1.1rem !important;
            }
            #securityWarningModal .modal-header p {
                font-size: 0.8rem !important;
            }
            #securityWarningModal .modal-body {
                padding: 15px 12px !important;
            }
            #securityWarningModal .modal-body li {
                padding: 8px !important;
            }
        }
    `;
    document.head.appendChild(style);
}

function acceptSecurityWarning() {
    console.log('✓ User clicked "Saya Mengerti dan Setuju"');
    const modal = document.getElementById('securityWarningModal');
    if (modal) {
        modal.remove();
        console.log('✓ Modal removed');
    }
    if (securityWarningCallback && typeof securityWarningCallback === 'function') {
        console.log('✓ Calling callback function...');
        securityWarningCallback();
        securityWarningCallback = null;
    } else {
        console.error('❌ Callback function not found!');
    }
}

class ExamTimer {
    constructor(durationSeconds, onTimeUp) {
        this.duration = durationSeconds;
        this.remaining = durationSeconds;
        this.onTimeUp = onTimeUp;
        this.interval = null;
        this.element = null;
    }

    startRealtime(elementId) {
        this.element = document.getElementById(elementId);
        if (!this.element) return;

        const updateTimer = () => {
            const hours = Math.floor(this.remaining / 3600);
            const minutes = Math.floor((this.remaining % 3600) / 60);
            const seconds = this.remaining % 60;
            const timeString = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;

            const timerDisplay = document.getElementById('timer-display');
            if (timerDisplay) {
                timerDisplay.textContent = timeString;
            } else {
                this.element.innerHTML = `
                    <div style="text-align: center;">
                        <div style="font-size: 0.8rem; opacity: 0.9;">Sisa Waktu</div>
                        <div style="font-size: 2rem;">${timeString}</div>
                    </div>
                `;
            }

            if (this.remaining <= 300) {
                this.element.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
                this.element.style.animation = 'pulse 1s infinite';
            }
        };

        updateTimer();

        this.interval = setInterval(() => {
            this.remaining--;
            updateTimer();

            if (this.remaining <= 0) {
                this.stop();
                alert('⏰ WAKTU HABIS!\n\nUjian akan otomatis diselesaikan dan Anda akan keluar.');
                if (this.onTimeUp) {
                    this.onTimeUp();
                }
            }
        }, 1000);
    }

    start(elementId) {
        this.startRealtime(elementId);
    }

    stop() {
        if (this.interval) {
            clearInterval(this.interval);
            this.interval = null;
        }
    }
}

function showModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'none';
    }
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
};

class ParticleBackground {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        if (!this.canvas) return;
        
        this.ctx = this.canvas.getContext('2d');
        this.particles = [];
        this.particleCount = 80;
        
        this.resize();
        this.init();
        this.animate();
        
        window.addEventListener('resize', () => this.resize());
    }
    
    resize() {
        this.canvas.width = window.innerWidth;
        this.canvas.height = window.innerHeight;
    }
    
    init() {
        for (let i = 0; i < this.particleCount; i++) {
            this.particles.push({
                x: Math.random() * this.canvas.width,
                y: Math.random() * this.canvas.height,
                radius: Math.random() * 2 + 1,
                vx: (Math.random() - 0.5) * 0.5,
                vy: (Math.random() - 0.5) * 0.5,
                opacity: Math.random() * 0.5 + 0.2
            });
        }
    }
    
    animate() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        
        this.particles.forEach(particle => {
            particle.x += particle.vx;
            particle.y += particle.vy;
            
            if (particle.x < 0 || particle.x > this.canvas.width) particle.vx *= -1;
            if (particle.y < 0 || particle.y > this.canvas.height) particle.vy *= -1;
            
            this.ctx.beginPath();
            this.ctx.arc(particle.x, particle.y, particle.radius, 0, Math.PI * 2);
            this.ctx.fillStyle = `rgba(139, 21, 56, ${particle.opacity})`;
            this.ctx.fill();
        });
        
        this.particles.forEach((p1, i) => {
            this.particles.slice(i + 1).forEach(p2 => {
                const dx = p1.x - p2.x;
                const dy = p1.y - p2.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 120) {
                    this.ctx.beginPath();
                    this.ctx.strokeStyle = `rgba(139, 21, 56, ${0.15 * (1 - distance / 120)})`;
                    this.ctx.lineWidth = 1;
                    this.ctx.moveTo(p1.x, p1.y);
                    this.ctx.lineTo(p2.x, p2.y);
                    this.ctx.stroke();
                }
            });
        });
        
        requestAnimationFrame(() => this.animate());
    }
}

function animateStats() {
    const statNumbers = document.querySelectorAll('.stat-number[data-count]');
    statNumbers.forEach(element => {
        const targetValue = parseInt(element.getAttribute('data-count'));
        let currentValue = 0;
        const increment = targetValue / 60;
        const duration = 1500;
        const startTime = Date.now();
        
        const timer = setInterval(() => {
            const elapsed = Date.now() - startTime;
            const progress = Math.min(elapsed / duration, 1);
            
            currentValue = Math.floor(targetValue * easeOutQuad(progress));
            element.textContent = currentValue;
            
            if (progress >= 1) {
                element.textContent = targetValue;
                clearInterval(timer);
            }
        }, 16);
    });
}

function easeOutQuad(t) {
    return t * (2 - t);
}

function initScrollAnimations() {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('[data-animate]').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(30px)';
        el.style.transition = 'all 0.8s cubic-bezier(0.68, -0.55, 0.265, 1.55)';
        observer.observe(el);
    });
}

function animateLeaderboard() {
    const items = document.querySelectorAll('.leaderboard-item');
    items.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateX(-50px)';
        
        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateX(0)';
        }, index * 100);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const logoContainer = document.getElementById('logo-animation');
    if (logoContainer) {
        new LogoAnimation('logo-animation');
    }
    
    const particleCanvas = document.getElementById('particles-bg');
    if (particleCanvas) {
        new ParticleBackground('particles-bg');
    }

    if (document.querySelector('.stat-number[data-count]')) {
        setTimeout(() => animateStats(), 300);
    }

    if (document.querySelector('.leaderboard-item')) {
        animateLeaderboard();
    }
    
    initScrollAnimations();

    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', (e) => {
            if (!confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                e.preventDefault();
            }
        });
    });
});

if (typeof module !== 'undefined' && module.exports) {
    module.exports = { LogoAnimation, AntiCheat, ExamTimer };
}

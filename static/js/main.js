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
    constructor(onWarning, logoutUrl = '/logout', restartUrl = null) {
        this.warningCount = 0;
        this.maxWarnings = 1;
        this.onWarning = onWarning;
        this.enabled = false;
        this.logoutUrl = logoutUrl;
        this.restartUrl = restartUrl;
        this.tabSwitchCount = 0;
        this.maxTabSwitches = 1;
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
        
        document.addEventListener('copy', this.copyHandler);
        document.addEventListener('cut', this.cutHandler);
        document.addEventListener('contextmenu', this.contextHandler);
        document.addEventListener('keydown', this.keyHandler);
        document.addEventListener('visibilitychange', this.visibilityHandler);
        window.addEventListener('blur', this.blurHandler);
    }

    removeListeners() {
        document.removeEventListener('copy', this.copyHandler);
        document.removeEventListener('cut', this.cutHandler);
        document.removeEventListener('contextmenu', this.contextHandler);
        document.removeEventListener('keydown', this.keyHandler);
        document.removeEventListener('visibilitychange', this.visibilityHandler);
        window.removeEventListener('blur', this.blurHandler);
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

    handleKeyDown(e) {
        if (this.enabled) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'v' || e.key === 'x')) {
                e.preventDefault();
                this.triggerLogout('Terdeteksi menggunakan shortcut copy/paste! Anda akan dikeluarkan dari ujian.');
            }

            if (e.key === 'PrintScreen' || e.key === 'F12') {
                e.preventDefault();
                this.showWarningNotification('Screenshot dan DevTools tidak diperbolehkan!');
            }
        }
    }

    handleVisibilityChange() {
        if (this.enabled && document.hidden) {
            this.tabSwitchCount++;
            
            if (this.tabSwitchCount >= this.maxTabSwitches && this.restartUrl) {
                this.triggerRestart('Terdeteksi pindah tab! Ujian akan dimulai ulang.');
            } else {
                this.showWarningNotification('Jangan pindah tab selama ujian!');
            }
        }
    }

    handleWindowBlur() {
        if (this.enabled) {
            this.showWarningNotification('Fokus pada ujian! Jangan pindah window!');
        }
    }

    triggerLogout(message) {
        this.disable();
        alert(message);
        window.location.href = this.logoutUrl;
    }

    triggerRestart(message) {
        this.disable();
        alert(message);
        window.location.href = this.restartUrl;
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
        notification.style.position = 'fixed';
        notification.style.top = '100px';
        notification.style.right = '20px';
        notification.style.zIndex = '9999';
        notification.style.animation = 'slideDown 0.3s ease';
        notification.innerHTML = `
            <strong>⚠️ Peringatan:</strong> ${message}
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
}

function showSecurityWarningModal() {
    const modal = document.createElement('div');
    modal.id = 'securityWarningModal';
    modal.className = 'modal';
    modal.style.display = 'block';
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 600px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white;">
                <h2>⚠️ PERINGATAN KEAMANAN UJIAN</h2>
            </div>
            <div class="modal-body" style="padding: 30px;">
                <div style="text-align: left; line-height: 1.8;">
                    <h3 style="color: #dc3545; margin-bottom: 20px;">Peraturan Selama Ujian:</h3>
                    <ul style="list-style: none; padding: 0;">
                        <li style="margin-bottom: 15px;">
                            <strong>🚫 DILARANG COPY-PASTE</strong><br>
                            <small>Jika terdeteksi mencoba copy, Anda akan OTOMATIS LOGOUT</small>
                        </li>
                        <li style="margin-bottom: 15px;">
                            <strong>🚫 DILARANG PINDAH TAB/WINDOW</strong><br>
                            <small>Jika pindah tab, ujian akan OTOMATIS RESTART dari awal</small>
                        </li>
                        <li style="margin-bottom: 15px;">
                            <strong>🚫 DILARANG SCREENSHOT</strong><br>
                            <small>Screenshot dan Developer Tools tidak diperbolehkan</small>
                        </li>
                        <li style="margin-bottom: 15px;">
                            <strong>✅ FOKUS PADA UJIAN</strong><br>
                            <small>Tetap di halaman ujian sampai selesai</small>
                        </li>
                    </ul>
                    <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin-top: 20px;">
                        <strong>⚡ Penting:</strong> Sistem akan memantau aktivitas Anda selama ujian. Pelanggaran akan mengakibatkan tindakan otomatis.
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: #f8f9fa; padding: 20px;">
                <button onclick="acceptSecurityWarning()" class="btn btn-danger" style="padding: 12px 30px; font-size: 16px; width: 100%;">
                    Saya Mengerti dan Setuju
                </button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function acceptSecurityWarning() {
    const modal = document.getElementById('securityWarningModal');
    if (modal) {
        modal.remove();
    }
    if (typeof startExamAfterWarning === 'function') {
        startExamAfterWarning();
    }
}

class ExamTimer {
    constructor(durationMinutes, onTimeUp) {
        this.duration = durationMinutes * 60;
        this.remaining = this.duration;
        this.onTimeUp = onTimeUp;
        this.interval = null;
        this.element = null;
    }

    start(elementId) {
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
                if (this.onTimeUp) {
                    this.onTimeUp();
                }
            }
        }, 1000);
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

function animateStats() {
    const statCards = document.querySelectorAll('.stat-card h3');
    statCards.forEach(card => {
        const targetValue = parseInt(card.textContent);
        let currentValue = 0;
        const increment = targetValue / 50;
        
        const timer = setInterval(() => {
            currentValue += increment;
            if (currentValue >= targetValue) {
                card.textContent = targetValue;
                clearInterval(timer);
            } else {
                card.textContent = Math.floor(currentValue);
            }
        }, 20);
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

    if (document.querySelector('.stat-card')) {
        animateStats();
    }

    if (document.querySelector('.leaderboard-item')) {
        animateLeaderboard();
    }

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

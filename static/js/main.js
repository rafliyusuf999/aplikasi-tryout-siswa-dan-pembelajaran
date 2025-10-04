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
    constructor(onWarning) {
        this.warningCount = 0;
        this.maxWarnings = 3;
        this.onWarning = onWarning;
        this.enabled = false;
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
        document.addEventListener('copy', this.handleCopy.bind(this));
        document.addEventListener('cut', this.handleCut.bind(this));
        document.addEventListener('contextmenu', this.handleContextMenu.bind(this));
        document.addEventListener('keydown', this.handleKeyDown.bind(this));
        document.addEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        window.addEventListener('blur', this.handleWindowBlur.bind(this));
    }

    removeListeners() {
        document.removeEventListener('copy', this.handleCopy.bind(this));
        document.removeEventListener('cut', this.handleCut.bind(this));
        document.removeEventListener('contextmenu', this.handleContextMenu.bind(this));
        document.removeEventListener('keydown', this.handleKeyDown.bind(this));
        document.removeEventListener('visibilitychange', this.handleVisibilityChange.bind(this));
        window.removeEventListener('blur', this.handleWindowBlur.bind(this));
    }

    handleCopy(e) {
        if (this.enabled) {
            e.preventDefault();
            this.addWarning('Copy-paste tidak diperbolehkan!');
        }
    }

    handleCut(e) {
        if (this.enabled) {
            e.preventDefault();
            this.addWarning('Cut tidak diperbolehkan!');
        }
    }

    handleContextMenu(e) {
        if (this.enabled) {
            e.preventDefault();
            this.addWarning('Klik kanan tidak diperbolehkan!');
        }
    }

    handleKeyDown(e) {
        if (this.enabled) {
            if ((e.ctrlKey || e.metaKey) && (e.key === 'c' || e.key === 'v' || e.key === 'x')) {
                e.preventDefault();
                this.addWarning('Shortcut tidak diperbolehkan!');
            }

            if (e.key === 'PrintScreen' || e.key === 'F12') {
                e.preventDefault();
                this.addWarning('Screenshot dan DevTools tidak diperbolehkan!');
            }
        }
    }

    handleVisibilityChange() {
        if (this.enabled && document.hidden) {
            this.addWarning('Jangan pindah tab selama ujian!');
        }
    }

    handleWindowBlur() {
        if (this.enabled) {
            this.addWarning('Fokus pada ujian! Jangan pindah window!');
        }
    }

    addWarning(message) {
        this.warningCount++;
        
        if (this.onWarning) {
            this.onWarning(this.warningCount, message);
        }

        this.showWarningNotification(message);

        if (this.warningCount >= this.maxWarnings) {
            this.showMaxWarningsReached();
        }
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
            <strong>⚠️ Peringatan #${this.warningCount}:</strong> ${message}
            <br><small>Maksimal ${this.maxWarnings} peringatan!</small>
        `;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.animation = 'fadeOut 0.3s ease';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    showMaxWarningsReached() {
        alert('ANDA TELAH MENCAPAI BATAS PERINGATAN!\n\nUjian Anda akan ditandai sebagai mencurigakan.');
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

        this.interval = setInterval(() => {
            this.remaining--;

            const hours = Math.floor(this.remaining / 3600);
            const minutes = Math.floor((this.remaining % 3600) / 60);
            const seconds = this.remaining % 60;

            this.element.innerHTML = `
                <div style="text-align: center;">
                    <div style="font-size: 0.8rem; opacity: 0.9;">Sisa Waktu</div>
                    <div style="font-size: 2rem;">${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}</div>
                </div>
            `;

            if (this.remaining <= 0) {
                this.stop();
                if (this.onTimeUp) {
                    this.onTimeUp();
                }
            } else if (this.remaining <= 300) {
                this.element.style.background = 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)';
                this.element.style.animation = 'pulse 1s infinite';
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

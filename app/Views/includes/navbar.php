    <nav class="navbar">
        <div class="container">
            <a href="<?php echo url('index.php'); ?>" class="navbar-brand">
                <div id="logo-animation" class="logo-container"></div>
                <span>INSPIRANET</span>
            </a>
            <button class="navbar-toggle" onclick="toggleMobileMenu()" aria-label="Toggle navigation">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="navbar-nav" id="navbarMenu">
                <?php if(isLoggedIn()): 
                    $user = getCurrentUser();
                    if($user['role'] == 'admin'): ?>
                        <li><a href="<?php echo url('admin/dashboard.php'); ?>" onclick="closeMobileMenu()">Dashboard</a></li>
                        <li><a href="<?php echo url('admin/students.php'); ?>" onclick="closeMobileMenu()">Siswa</a></li>
                        <li><a href="<?php echo url('admin/exams.php'); ?>" onclick="closeMobileMenu()">Try Out</a></li>
                        <li><a href="<?php echo url('admin/payments.php'); ?>" onclick="closeMobileMenu()">Pembayaran</a></li>
                    <?php elseif($user['role'] == 'teacher'): ?>
                        <li><a href="<?php echo url('teacher/dashboard.php'); ?>" onclick="closeMobileMenu()">Dashboard</a></li>
                        <li><a href="<?php echo url('teacher/exams.php'); ?>" onclick="closeMobileMenu()">Try Out</a></li>
                        <li><a href="<?php echo url('admin/payments.php'); ?>" onclick="closeMobileMenu()">Pembayaran</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo url('student/dashboard.php'); ?>" onclick="closeMobileMenu()">Dashboard</a></li>
                        <li><a href="<?php echo url('student/exams.php'); ?>" onclick="closeMobileMenu()">Try Out</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo url('leaderboards.php'); ?>" onclick="closeMobileMenu()">Peringkat</a></li>
                    <li class="user-profile">
                        <div class="profile-info">
                            <?php if(!empty($user['profile_photo'])): ?>
                            <a href="<?php echo url('profile.php'); ?>" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                                <img src="<?php echo url('storage/uploads/profiles/' . $user['profile_photo']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>" class="profile-photo-nav">
                                <span class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </a>
                            <?php else: ?>
                            <a href="<?php echo url('profile.php'); ?>" style="display: flex; align-items: center; gap: 0.75rem; text-decoration: none;">
                                <div class="profile-photo-placeholder"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
                                <span class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></span>
                            </a>
                            <?php endif; ?>
                        </div>
                    </li>
                    <li><a href="<?php echo url('logout.php'); ?>" onclick="closeMobileMenu()">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo url('login.php'); ?>" onclick="closeMobileMenu()">Login</a></li>
                    <li><a href="<?php echo url('register.php'); ?>" onclick="closeMobileMenu()">Daftar</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <script>
        function toggleMobileMenu() {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            menu.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        function closeMobileMenu() {
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            menu.classList.remove('active');
            toggle.classList.remove('active');
        }

        document.addEventListener('click', function(event) {
            const navbar = document.querySelector('.navbar');
            const menu = document.getElementById('navbarMenu');
            const toggle = document.querySelector('.navbar-toggle');
            
            if (menu && menu.classList.contains('active') && !navbar.contains(event.target)) {
                closeMobileMenu();
            }
        });
    </script>

    <div class="container">
        <?php 
        $flash = getFlash();
        if($flash): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>"><?php echo htmlspecialchars($flash['message']); ?></div>
        <?php endif; ?>

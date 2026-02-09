<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item nav-category">Dashboard</li>
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="menu-icon mdi mdi-view-dashboard"></i>
                <span class="menu-title">Dashboard Monev</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-chart-bar"></i>
                <span class="menu-title">Dashboard Skor Arsip</span>
            </a>
        </li>
        <li class="nav-item nav-category">Menu Utama</li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-file-document-outline"></i>
                <span class="menu-title">Pelaporan Monev</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#statistik-menu" aria-expanded="false" aria-controls="statistik-menu">
                <i class="menu-icon mdi mdi-chart-line"></i>
                <span class="menu-title">Statistik</span>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="statistik-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('aktivitas-pegawai.index') }}">
                            <i class="mdi mdi-account-check"></i>
                            Aktivitas Pegawai
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('staging.index') }}">
                            <i class="mdi mdi-account-alert"></i>
                            Pegawai Belum Terdata
                        </a>
                    </li>
                </ul>
            </div>
        </li>
        <li class="nav-item nav-category">Pengaturan</li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#pengaturan-menu" aria-expanded="false" aria-controls="pengaturan-menu">
                <i class="menu-icon mdi mdi-cog"></i>
                <span class="menu-title">Pengaturan</span>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="pengaturan-menu">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('pegawai.index') }}">
                            <i class="mdi mdi-account-multiple"></i>
                            Kelola Pengguna
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="mdi mdi-clipboard-check"></i>
                            Kelola IKK
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('instansi.index') }}">
                            <i class="mdi mdi-office-building"></i>
                            Kelola Instansi
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="{{ route('pic.index') }}">
                            <i class="mdi mdi-account-tie"></i>
                            Kelola PIC
                        </a>
                    </li>
                </ul>
            </div>
        </li>
    </ul>
</nav>

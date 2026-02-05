<nav class="sidebar sidebar-offcanvas" id="sidebar">
    <ul class="nav">
        <li class="nav-item">
            <a class="nav-link" href="{{ route('dashboard') }}">
                <i class="mdi mdi-grid-large menu-icon"></i>
                <span class="menu-title">Dashboard</span>
            </a>
        </li>
        <li class="nav-item nav-category">Menu Utama</li>
        <li class="nav-item">
            <a class="nav-link" data-bs-toggle="collapse" href="#ui-basic" aria-expanded="false" aria-controls="ui-basic">
                <i class="menu-icon mdi mdi-floor-plan"></i>
                <span class="menu-title">Data Master</span>
                <i class="menu-arrow"></i>
            </a>
            <div class="collapse" id="ui-basic">
                <ul class="nav flex-column sub-menu">
                    <li class="nav-item"> <a class="nav-link" href="#">Sub Menu 1</a></li>
                    <li class="nav-item"> <a class="nav-link" href="#">Sub Menu 2</a></li>
                </ul>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-chart-line"></i>
                <span class="menu-title">Monitoring</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-file-document"></i>
                <span class="menu-title">Evaluasi</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-file-chart"></i>
                <span class="menu-title">Laporan</span>
            </a>
        </li>
        <li class="nav-item nav-category">Pengaturan</li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-account-circle-outline"></i>
                <span class="menu-title">Pengguna</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#">
                <i class="menu-icon mdi mdi-cog"></i>
                <span class="menu-title">Konfigurasi</span>
            </a>
        </li>
    </ul>
</nav>

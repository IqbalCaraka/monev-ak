<nav class="navbar default-layout col-lg-12 col-12 p-0 fixed-top d-flex align-items-top flex-row">
    <div class="text-center navbar-brand-wrapper d-flex align-items-center justify-content-start">
        <div class="me-3">
            <button class="navbar-toggler navbar-toggler align-self-center" type="button" data-bs-toggle="minimize">
                <span class="icon-menu"></span>
            </button>
        </div>
        <div>
            <a class="navbar-brand brand-logo" href="{{ route('dashboard') }}">
                <img src="{{ asset('assets/images/logo.svg') }}" alt="logo" />
            </a>
            <a class="navbar-brand brand-logo-mini" href="{{ route('dashboard') }}">
                <img src="{{ asset('assets/images/logo-mini.svg') }}" alt="logo" />
            </a>
        </div>
    </div>
    <div class="navbar-menu-wrapper d-flex align-items-top">
        <ul class="navbar-nav">
            <li class="nav-item fw-semibold d-none d-lg-block ms-0">
                <h1 class="welcome-text">Selamat Datang, <span class="text-black fw-bold">Admin</span></h1>
                <h3 class="welcome-sub-text">Sistem Monitoring & Evaluasi</h3>
            </li>
        </ul>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item d-none d-lg-block">
                <div id="datepicker-popup" class="input-group date datepicker navbar-date-picker">
                    <span class="input-group-addon input-group-prepend border-right">
                        <span class="icon-calendar input-group-text calendar-icon"></span>
                    </span>
                    <input type="text" class="form-control">
                </div>
            </li>
            <li class="nav-item">
                <form class="search-form" action="#">
                    <i class="icon-search"></i>
                    <input type="search" class="form-control" placeholder="Cari..." title="Search here">
                </form>
            </li>
            <li class="nav-item dropdown">
                <a class="nav-link count-indicator" id="notificationDropdown" href="#" data-bs-toggle="dropdown">
                    <i class="icon-bell"></i>
                    <span class="count"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown preview-list pb-0" aria-labelledby="notificationDropdown">
                    <a class="dropdown-item py-3 border-bottom">
                        <p class="mb-0 fw-medium float-start">Notifikasi</p>
                        <span class="badge badge-pill badge-primary float-end">Lihat semua</span>
                    </a>
                    <a class="dropdown-item preview-item py-3">
                        <div class="preview-thumbnail">
                            <i class="mdi mdi-alert m-auto text-primary"></i>
                        </div>
                        <div class="preview-item-content">
                            <h6 class="preview-subject fw-normal text-dark mb-1">Tidak ada notifikasi</h6>
                            <p class="fw-light small-text mb-0">Saat ini</p>
                        </div>
                    </a>
                </div>
            </li>
            <li class="nav-item dropdown d-none d-lg-block user-dropdown">
                <a class="nav-link" id="UserDropdown" href="#" data-bs-toggle="dropdown" aria-expanded="false">
                    <img class="img-xs rounded-circle" src="{{ asset('assets/images/faces/face8.jpg') }}" alt="Profile image">
                </a>
                <div class="dropdown-menu dropdown-menu-right navbar-dropdown" aria-labelledby="UserDropdown">
                    <div class="dropdown-header text-center">
                        <img class="img-md rounded-circle" src="{{ asset('assets/images/faces/face8.jpg') }}" alt="Profile image">
                        <p class="mb-1 mt-3 fw-semibold">Admin User</p>
                        <p class="fw-light text-muted mb-0">admin@example.com</p>
                    </div>
                    <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-account-outline text-primary me-2"></i> Profil Saya</a>
                    <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-message-text-outline text-primary me-2"></i> Pesan</a>
                    <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-calendar-check-outline text-primary me-2"></i> Aktivitas</a>
                    <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-help-circle-outline text-primary me-2"></i> FAQ</a>
                    <a class="dropdown-item"><i class="dropdown-item-icon mdi mdi-power text-primary me-2"></i> Keluar</a>
                </div>
            </li>
        </ul>
        <button class="navbar-toggler navbar-toggler-right d-lg-none align-self-center" type="button" data-bs-toggle="offcanvas">
            <span class="mdi mdi-menu"></span>
        </button>
    </div>
</nav>

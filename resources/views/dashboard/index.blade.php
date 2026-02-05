@extends('layouts.app')

@section('title', 'Dashboard - Monitoring & Evaluasi')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="home-tab">
            <div class="d-sm-flex align-items-center justify-content-between border-bottom">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active ps-0" id="home-tab" data-bs-toggle="tab" href="#overview" role="tab" aria-controls="overview" aria-selected="true">Overview</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="profile-tab" data-bs-toggle="tab" href="#audiences" role="tab" aria-selected="false">Statistik</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="contact-tab" data-bs-toggle="tab" href="#demographics" role="tab" aria-selected="false">Laporan</a>
                    </li>
                </ul>
            </div>
            <div class="tab-content tab-content-basic">
                <div class="tab-pane fade show active" id="overview" role="tabpanel" aria-labelledby="overview">
                    <div class="row">
                        <div class="col-sm-12">
                            <div class="statistics-details d-flex align-items-center justify-content-between">
                                <div>
                                    <p class="statistics-title">Total Proyek</p>
                                    <h3 class="rate-percentage">32</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span>+2.5%</span></p>
                                </div>
                                <div>
                                    <p class="statistics-title">Sedang Berjalan</p>
                                    <h3 class="rate-percentage">24</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span>+1.8%</span></p>
                                </div>
                                <div>
                                    <p class="statistics-title">Selesai</p>
                                    <h3 class="rate-percentage">7</h3>
                                    <p class="text-danger d-flex"><i class="mdi mdi-menu-down"></i><span>-0.5%</span></p>
                                </div>
                                <div class="d-none d-md-block">
                                    <p class="statistics-title">Pending</p>
                                    <h3 class="rate-percentage">1</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-menu-down"></i><span>-0.8%</span></p>
                                </div>
                                <div class="d-none d-md-block">
                                    <p class="statistics-title">Evaluasi</p>
                                    <h3 class="rate-percentage">15</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span>+3.2%</span></p>
                                </div>
                                <div class="d-none d-md-block">
                                    <p class="statistics-title">Rating</p>
                                    <h3 class="rate-percentage">4.8</h3>
                                    <p class="text-success d-flex"><i class="mdi mdi-menu-up"></i><span>+0.2</span></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-8 d-flex flex-column">
                            <div class="row flex-grow">
                                <div class="col-12 grid-margin stretch-card">
                                    <div class="card card-rounded">
                                        <div class="card-body">
                                            <div class="d-sm-flex justify-content-between align-items-start">
                                                <div>
                                                    <h4 class="card-title card-title-dash">Grafik Monitoring</h4>
                                                    <p class="card-subtitle card-subtitle-dash">Progress proyek tahun ini</p>
                                                </div>
                                                <div>
                                                    <div class="dropdown">
                                                        <button class="btn btn-light dropdown-toggle toggle-dark btn-sm mb-0 me-0" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> Bulan ini </button>
                                                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                                                            <a class="dropdown-item" href="#">Hari ini</a>
                                                            <a class="dropdown-item" href="#">Minggu ini</a>
                                                            <a class="dropdown-item" href="#">Bulan ini</a>
                                                            <a class="dropdown-item" href="#">Tahun ini</a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="chartjs-wrapper mt-5">
                                                <canvas id="dashboardChart" height="100"></canvas>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 d-flex flex-column">
                            <div class="row flex-grow">
                                <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                    <div class="card bg-primary card-rounded">
                                        <div class="card-body pb-0">
                                            <h4 class="card-title card-title-dash text-white mb-4">Status Saat Ini</h4>
                                            <div class="row">
                                                <div class="col-sm-4">
                                                    <p class="text-white mb-0">Aktif</p>
                                                    <p class="text-white"><i class="mdi mdi-arrow-up"></i> 24</p>
                                                </div>
                                                <div class="col-sm-8">
                                                    <div class="status-summary-chart-wrapper pb-4">
                                                        <canvas id="status-summary"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 col-lg-12 grid-margin stretch-card">
                                    <div class="card card-rounded">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-sm-6">
                                                    <div class="d-flex justify-content-between align-items-center mb-2 mb-sm-0">
                                                        <div class="circle-progress-width">
                                                            <p class="text-muted mb-2">Pencapaian Target</p>
                                                            <h4 class="mb-0 fw-bold">85%</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-sm-6">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="circle-progress-width">
                                                            <p class="text-muted mb-2">Tingkat Keberhasilan</p>
                                                            <h4 class="mb-0 fw-bold">92%</h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 d-flex flex-column">
                            <div class="row flex-grow">
                                <div class="col-12 grid-margin stretch-card">
                                    <div class="card card-rounded">
                                        <div class="card-body">
                                            <div class="d-sm-flex justify-content-between align-items-start">
                                                <div>
                                                    <h4 class="card-title card-title-dash">Proyek Terbaru</h4>
                                                    <p class="card-subtitle card-subtitle-dash">Daftar proyek yang baru ditambahkan</p>
                                                </div>
                                                <div>
                                                    <button class="btn btn-primary btn-sm text-white mb-0 me-0" type="button">Lihat Semua</button>
                                                </div>
                                            </div>
                                            <div class="table-responsive mt-1">
                                                <table class="table select-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Nama Proyek</th>
                                                            <th>Status</th>
                                                            <th>Progress</th>
                                                            <th>Deadline</th>
                                                            <th>Aksi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6>Proyek Website Instansi</h6>
                                                                        <p>Development & Design</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="badge badge-opacity-success">Aktif</div>
                                                            </td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-success" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                            <td>15 Feb 2026</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary">Detail</button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6>Aplikasi Mobile</h6>
                                                                        <p>Mobile Development</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="badge badge-opacity-warning">Review</div>
                                                            </td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-warning" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                            <td>28 Feb 2026</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary">Detail</button>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td>
                                                                <div class="d-flex">
                                                                    <div>
                                                                        <h6>Sistem Informasi</h6>
                                                                        <p>Backend System</p>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td>
                                                                <div class="badge badge-opacity-info">Planning</div>
                                                            </td>
                                                            <td>
                                                                <div class="progress">
                                                                    <div class="progress-bar bg-info" role="progressbar" style="width: 30%" aria-valuenow="30" aria-valuemin="0" aria-valuemax="100"></div>
                                                                </div>
                                                            </td>
                                                            <td>10 Mar 2026</td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary">Detail</button>
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('plugin-scripts')
<script src="{{ asset('assets/vendors/chart.js/chart.umd.js') }}"></script>
@endpush

@push('scripts')
<script>
    // Dashboard Chart
    if ($("#dashboardChart").length) {
        var ctx = document.getElementById('dashboardChart').getContext("2d");
        var gradientStrokeViolet = ctx.createLinearGradient(0, 0, 0, 181);
        gradientStrokeViolet.addColorStop(0, 'rgba(218, 140, 255, 1)');
        gradientStrokeViolet.addColorStop(1, 'rgba(154, 85, 255, 1)');

        var myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
                datasets: [{
                    label: 'Proyek Selesai',
                    data: [5, 8, 12, 15, 18, 21, 24, 28, 30, 32, 35, 38],
                    backgroundColor: gradientStrokeViolet,
                    borderColor: [
                        '#9B51E0',
                    ],
                    borderWidth: 2,
                    fill: true,
                    pointBorderColor: "#fff",
                    pointBackgroundColor: "#9B51E0",
                    pointBorderWidth: 2,
                    pointRadius: 4,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        border: {
                            display: false
                        },
                        grid: {
                            display: true,
                            color: "rgba(0, 0, 0, 0.05)"
                        },
                        ticks: {
                            color: "#9ca2a9"
                        }
                    },
                    x: {
                        border: {
                            display: false
                        },
                        grid: {
                            display: false,
                        },
                        ticks: {
                            color: "#9ca2a9"
                        }
                    }
                }
            }
        });
    }
</script>
@endpush

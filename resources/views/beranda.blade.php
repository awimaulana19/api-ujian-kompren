@extends('LayoutUser.app', ['title' => 'Beranda'])

@section('content')
    <!-- ======= Hero Section ======= -->
    <section id="hero" class="d-flex align-items-center">
        <div class="container" data-aos="zoom-out" data-aos-delay="100">
            <h1> Beranda <span> Website</span></h1>
            <h2>Ujian Komprehensif Jurusan Sistem Informasi</h2>
        </div>
    </section><!-- End Hero -->
    <!-- ======= Featured Services Section ======= -->
    <section id="featured-services" class="featured-services">
        <div class="container" data-aos="fade-up">
            <div class="section-title">
                <h2>Jenis Ujian Komprehensif</h2>

            </div>
            <div class="row justify-content-center">
                <div class="col-md-3 mb-5 mb-lg-0" style="cursor: pointer;">
                    <div class="card shadow p-3 border-none" data-aos="fade-up" data-aos-delay="200">
                        <a href="{{ url('/regis') }}" class="text-center">
                            <div class="icon"><i class="bx bx-file"></i></div>
                            <h4 class="title text-center"><a>Ujian Komprehensif Rekayasa Perangkat Lunak</a></h4>
                        </a>
                    </div>
                </div>
                <div class="col-md-3 mb-5 mb-lg-0" style="cursor: pointer;">
                    <div class="card shadow p-3 border-none" data-aos="fade-up" data-aos-delay="200">
                        <a href="{{ url('/regis') }}" class="text-center">
                            <div class="icon"><i class="bx bx-file"></i></div>
                            <h4 class="title text-center"><a>Ujian Komprehensif Jaringan Komputer</a></h4>
                        </a>
                    </div>
                </div>
                <div class="col-md-3 mb-5 mb-lg-0" style="cursor: pointer;">
                    <div class="card shadow p-3 border-none" data-aos="fade-up" data-aos-delay="200">
                        <a href="{{ url('/regis') }}" class="text-center">
                            <div class="icon"><i class="bx bx-file"></i></div>
                            <h4 class="title text-center"><a>Ujian Komprehensif Dirasah Islamiyah</a></h4>
                        </a>
                    </div>
                </div>

            </div>

        </div>
    </section><!-- End Featured Services Section -->
@endsection

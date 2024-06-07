  <!-- ======= Header ======= -->
  <header id="header" class="d-flex align-items-center">
      <div class="container d-flex align-items-center justify-content-between">

          <div class="logo">
              <a href="{{ url('/') }}" class="d-flex">
                <img src="{{ asset('assets2/img/logo.png') }}" alt="">
                  <p class="mb-0 ms-2 align-self-center" style="font-size: 14px">UJIAN KOMPREHENSIF<br>
                      <span>Jurusan Sistem Informasi</span>
                  </p>
              </a>
          </div>
          <!-- Uncomment below if you prefer to use an image logo -->
          <!-- <a href="index.html" class="logo"><img src="assets/img/logo.png" alt=""></a>-->

          <nav id="navbar" class="navbar">
              <ul>
                  <li class="active"><a class="nav-link scrollto active" href="{{ url('/') }}">Beranda</a></li>
                  <li><a class="nav-link scrollto" href="{{ url('/login') }}">Login</a></li>
              </ul>
              <i class="bi bi-list mobile-nav-toggle"></i>
          </nav><!-- .navbar -->

      </div>
  </header><!-- End Header -->

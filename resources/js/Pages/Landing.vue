<template>
  <div class="min-h-screen bg-gradient-to-br from-blue-50 via-indigo-50 to-purple-50 overflow-hidden">
    <!-- Animated Background Elements -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute -top-40 -right-40 w-80 h-80 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob"></div>
      <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-2000"></div>
      <div class="absolute top-40 left-40 w-80 h-80 bg-blue-300 rounded-full mix-blend-multiply filter blur-xl opacity-20 animate-blob animation-delay-4000"></div>
    </div>

    <!-- Header -->
    <header class="bg-white/80 backdrop-blur-lg shadow-sm sticky top-0 z-50 transition-all duration-300">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <div class="flex items-center space-x-3">
            <div class="w-12 h-12 flex items-center justify-center hover-scale">
              <img src="/logo.png" alt="HelpDesk Kemlu" class="w-12 h-12">
            </div>
            <div>
              <h1 class="text-xl font-bold text-gray-900">HelpDesk Kemlu</h1>
              <p class="text-xs text-gray-500">Kementerian Luar Negeri RI</p>
            </div>
          </div>
          <div class="hidden sm:flex items-center space-x-4">
            <Link
              href="/register"
              class="text-gray-700 hover:text-indigo-600 transition-colors duration-200 font-medium"
            >
              Register
            </Link>
            <Link
              href="/login"
              class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-6 py-2.5 rounded-lg hover:from-indigo-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg hover-lift"
            >
              Login
            </Link>
          </div>
          <!-- Mobile Menu Button -->
          <button
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="sm:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500"
          >
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <!-- Mobile Menu -->
        <div v-show="mobileMenuOpen" class="sm:hidden py-3 space-y-2 animate-slideInDown">
          <Link
            href="/register"
            class="block px-3 py-2 rounded-md text-gray-700 hover:text-indigo-600 hover:bg-gray-50 transition-colors duration-200"
          >
            Register
          </Link>
          <Link
            href="/login"
            class="block px-3 py-2 rounded-md bg-gradient-to-r from-indigo-600 to-purple-600 text-white"
          >
            Login
          </Link>
        </div>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-20 relative">
      <div class="text-center max-w-5xl mx-auto">
        <div class="animate-fadeInUp">
          <h2 class="text-4xl sm:text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 mb-6 leading-tight">
            Sistem Helpdesk<br/>
            <span class="bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent">Terpadu & Efisien</span>
          </h2>
          <p class="text-lg sm:text-xl md:text-2xl text-gray-600 mb-8 leading-relaxed max-w-3xl mx-auto">
            Solusi manajemen tiket layanan TI yang modern untuk<br/>
            <span class="font-semibold">Kementerian Luar Negeri Republik Indonesia</span>
          </p>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 justify-center items-center animate-fadeInUp animation-delay-200">
          <Link
            href="/login"
            class="group inline-flex items-center justify-center bg-gradient-to-r from-indigo-600 to-purple-600 text-white px-8 py-4 rounded-xl text-lg font-semibold hover:from-indigo-700 hover:to-purple-700 transition-all duration-300 shadow-lg hover:shadow-xl hover-lift transform"
          >
            <svg class="w-5 h-5 mr-2 group-hover:translate-x-1 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Mulai Sekarang
          </Link>
          <a
            href="#features"
            class="group inline-flex items-center justify-center bg-white text-indigo-600 px-8 py-4 rounded-xl text-lg font-semibold hover:bg-gray-50 transition-all duration-300 shadow-md hover:shadow-lg border-2 border-indigo-600 hover-lift transform"
          >
            <svg class="w-5 h-5 mr-2 group-hover:rotate-12 transition-transform duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Pelajari Lebih Lanjut
          </a>
        </div>
      </div>

      <!-- Enhanced Stats with Animated Counters -->
      <div class="mt-16 sm:mt-20 grid grid-cols-1 sm:grid-cols-3 gap-6 sm:gap-8 max-w-4xl mx-auto">
        <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl p-6 sm:p-8 text-center hover-scale hover-glow animate-fadeInUp animation-delay-400">
          <div class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent mb-2">
            <animated-number :target="stats.totalTickets" suffix="+"></animated-number>
          </div>
          <div class="text-gray-600 font-medium">Tiket Terselesaikan</div>
          <div class="mt-3 text-sm text-gray-500">Bulan ini</div>
        </div>
        <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl p-6 sm:p-8 text-center hover-scale hover-glow animate-fadeInUp animation-delay-600">
          <div class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-green-600 to-emerald-600 bg-clip-text text-transparent mb-2">
            <animated-number :target="stats.avgResponseTime" suffix="h" :decimals="1"></animated-number>
          </div>
          <div class="text-gray-600 font-medium">Response Time</div>
          <div class="mt-3 text-sm text-gray-500">Rata-rata</div>
        </div>
        <div class="bg-white/80 backdrop-blur-lg rounded-2xl shadow-xl p-6 sm:p-8 text-center hover-scale hover-glow animate-fadeInUp animation-delay-800">
          <div class="text-3xl sm:text-4xl lg:text-5xl font-bold bg-gradient-to-r from-purple-600 to-pink-600 bg-clip-text text-transparent mb-2">
            <animated-number :target="stats.satisfactionRate" suffix="%"></animated-number>
          </div>
          <div class="text-gray-600 font-medium">Tingkat Kepuasan</div>
          <div class="mt-3 text-sm text-gray-500">User rating</div>
        </div>
      </div>
    </section>

    <!-- Enhanced Features Section -->
    <section id="features" class="bg-white/80 backdrop-blur-lg py-20">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h3 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-4">Fitur Unggulan</h3>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">Solusi lengkap dan modern untuk kebutuhan helpdesk Anda</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 lg:gap-10">
          <!-- Feature 1 -->
          <div class="group bg-gradient-to-br from-blue-50 to-indigo-50 p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover-lift border border-blue-100">
            <div class="text-5xl sm:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">📝</div>
            <h4 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-3">Buat Tiket Mudah</h4>
            <p class="text-gray-600 leading-relaxed">Laporkan masalah TI Anda dengan cepat melalui form yang user-friendly dan intuitif</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">User-Friendly</span>
              <span class="px-3 py-1 bg-blue-100 text-blue-700 text-xs rounded-full">Fast</span>
            </div>
          </div>

          <!-- Feature 2 -->
          <div class="group bg-gradient-to-br from-green-50 to-emerald-50 p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover-lift border border-green-100">
            <div class="text-5xl sm:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">📊</div>
            <h4 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-3">Tracking Real-time</h4>
            <p class="text-gray-600 leading-relaxed">Monitor status tiket Anda secara langsung dengan notifikasi real-time dan update otomatis</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">Live Updates</span>
              <span class="px-3 py-1 bg-green-100 text-green-700 text-xs rounded-full">Notifications</span>
            </div>
          </div>

          <!-- Feature 3 -->
          <div class="group bg-gradient-to-br from-purple-50 to-pink-50 p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover-lift border border-purple-100">
            <div class="text-5xl sm:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">⚡</div>
            <h4 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-3">Respons Cepat</h4>
            <p class="text-gray-600 leading-relaxed">Tim teknisi profesional siap membantu menyelesaikan masalah Anda dengan cepat dan efisien</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">Fast Response</span>
              <span class="px-3 py-1 bg-purple-100 text-purple-700 text-xs rounded-full">SLA</span>
            </div>
          </div>

          <!-- Feature 4 -->
          <div class="group bg-gradient-to-br from-yellow-50 to-orange-50 p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover-lift border border-yellow-100">
            <div class="text-5xl sm:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">👥</div>
            <h4 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-3">Multi-Role System</h4>
            <p class="text-gray-600 leading-relaxed">Sistem role-based yang terpisah untuk User, Admin, dan Teknisi dengan akses yang tepat</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">4 Roles</span>
              <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs rounded-full">Secure</span>
            </div>
          </div>

          <!-- Feature 5 -->
          <div class="group bg-gradient-to-br from-red-50 to-rose-50 p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover-lift border border-red-100">
            <div class="text-5xl sm:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">📈</div>
            <h4 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-3">Laporan & Analytics</h4>
            <p class="text-gray-600 leading-relaxed">Dashboard analytics lengkap dengan laporan performa dan statistik yang komprehensif</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full">Reports</span>
              <span class="px-3 py-1 bg-red-100 text-red-700 text-xs rounded-full">Charts</span>
            </div>
          </div>

          <!-- Feature 6 -->
          <div class="group bg-gradient-to-br from-indigo-50 to-blue-50 p-6 sm:p-8 rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 hover-lift border border-indigo-100">
            <div class="text-5xl sm:text-6xl mb-4 group-hover:scale-110 transition-transform duration-300">🔒</div>
            <h4 class="text-xl sm:text-2xl font-semibold text-gray-900 mb-3">Keamanan Terjamin</h4>
            <p class="text-gray-600 leading-relaxed">Sistem keamanan berlapis dengan autentikasi NIP dan enkripsi data end-to-end</p>
            <div class="mt-4 flex flex-wrap gap-2">
              <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full">Encrypted</span>
              <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full">Secure</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced How It Works Section -->
    <section class="py-20 bg-gradient-to-br from-indigo-50 via-purple-50 to-pink-50">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-16">
          <h3 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-4">Cara Kerja</h3>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">Proses sederhana dan intuitif untuk menyelesaikan masalah Anda</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
          <!-- Step 1 -->
          <div class="group text-center animate-fadeInUp">
            <div class="relative mx-auto w-20 h-20 mb-6">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
              <div class="absolute inset-0 flex items-center justify-center text-white text-2xl font-bold">1</div>
              <div class="absolute -top-2 -right-2 w-6 h-6 bg-indigo-600 rounded-full animate-ping"></div>
            </div>
            <h4 class="text-xl font-semibold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors duration-300">Login</h4>
            <p class="text-gray-600 leading-relaxed">Masuk menggunakan NIP Anda dengan aman dan mudah</p>
            <div class="mt-4 inline-flex items-center text-sm text-indigo-600 font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
              </svg>
              Secure Authentication
            </div>
          </div>

          <!-- Step 2 -->
          <div class="group text-center animate-fadeInUp animation-delay-200">
            <div class="relative mx-auto w-20 h-20 mb-6">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
              <div class="absolute inset-0 flex items-center justify-center text-white text-2xl font-bold">2</div>
              <div class="absolute -top-2 -right-2 w-6 h-6 bg-indigo-600 rounded-full animate-ping animation-delay-200"></div>
            </div>
            <h4 class="text-xl font-semibold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors duration-300">Buat Tiket</h4>
            <p class="text-gray-600 leading-relaxed">Laporkan masalah TI Anda dengan detail yang lengkap</p>
            <div class="mt-4 inline-flex items-center text-sm text-indigo-600 font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
              </svg>
              Quick Reporting
            </div>
          </div>

          <!-- Step 3 -->
          <div class="group text-center animate-fadeInUp animation-delay-400">
            <div class="relative mx-auto w-20 h-20 mb-6">
              <div class="absolute inset-0 bg-gradient-to-br from-indigo-600 to-purple-600 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
              <div class="absolute inset-0 flex items-center justify-center text-white text-2xl font-bold">3</div>
              <div class="absolute -top-2 -right-2 w-6 h-6 bg-indigo-600 rounded-full animate-ping animation-delay-400"></div>
            </div>
            <h4 class="text-xl font-semibold text-gray-900 mb-3 group-hover:text-indigo-600 transition-colors duration-300">Tracking</h4>
            <p class="text-gray-600 leading-relaxed">Monitor progress penyelesaian secara real-time</p>
            <div class="mt-4 inline-flex items-center text-sm text-indigo-600 font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
              </svg>
              Live Updates
            </div>
          </div>

          <!-- Step 4 -->
          <div class="group text-center animate-fadeInUp animation-delay-600">
            <div class="relative mx-auto w-20 h-20 mb-6">
              <div class="absolute inset-0 bg-gradient-to-br from-green-600 to-emerald-600 rounded-full group-hover:scale-110 transition-transform duration-300"></div>
              <div class="absolute inset-0 flex items-center justify-center text-white text-2xl font-bold">✓</div>
              <div class="absolute -top-2 -right-2 w-6 h-6 bg-green-600 rounded-full animate-ping animation-delay-600"></div>
            </div>
            <h4 class="text-xl font-semibold text-gray-900 mb-3 group-hover:text-green-600 transition-colors duration-300">Selesai</h4>
            <p class="text-gray-600 leading-relaxed">Masalah terselesaikan dengan solusi terbaik</p>
            <div class="mt-4 inline-flex items-center text-sm text-green-600 font-medium">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
              </svg>
              Problem Solved
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Enhanced FAQ Section -->
    <section class="py-20 bg-white/80 backdrop-blur-lg">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8 max-w-5xl">
        <div class="text-center mb-16">
          <h3 class="text-3xl sm:text-4xl md:text-5xl font-bold text-gray-900 mb-4">Frequently Asked Questions</h3>
          <p class="text-xl text-gray-600 max-w-2xl mx-auto">Pertanyaan yang sering diajukan tentang sistem HelpDesk Kemlu</p>
        </div>

        <div class="space-y-4">
          <!-- FAQ Item 1 -->
          <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-xl">
            <button
              @click="toggleFAQ(0)"
              class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200"
            >
              <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                <span class="mr-3 text-indigo-600">📝</span>
                Bagaimana cara membuat tiket?
              </h4>
              <svg
                :class="['w-5 h-5 text-gray-500 transition-transform duration-300', faqItems[0].open ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              :class="['px-6 transition-all duration-300', faqItems[0].open ? 'py-4' : 'hidden']"
            >
              <div class="border-t border-gray-200 pt-4">
                <p class="text-gray-600 leading-relaxed">
                  Login menggunakan NIP Anda, kemudian klik tombol "Buat Tiket Baru" di dashboard. Isi form dengan detail masalah yang Anda alami, termasuk aplikasi, prioritas, dan deskripsi lengkap.
                </p>
                <div class="mt-3 flex items-center text-sm text-indigo-600">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                  </svg>
                  Proses hanya memakan waktu 2 menit
                </div>
              </div>
            </div>
          </div>

          <!-- FAQ Item 2 -->
          <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-xl">
            <button
              @click="toggleFAQ(1)"
              class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200"
            >
              <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                <span class="mr-3 text-green-600">⏱️</span>
                Berapa lama waktu respons tiket?
              </h4>
              <svg
                :class="['w-5 h-5 text-gray-500 transition-transform duration-300', faqItems[1].open ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              :class="['px-6 transition-all duration-300', faqItems[1].open ? 'py-4' : 'hidden']"
            >
              <div class="border-t border-gray-200 pt-4">
                <p class="text-gray-600 leading-relaxed">
                  Waktu respons bervariasi tergantung prioritas tiket:
                </p>
                <ul class="mt-3 space-y-2 text-gray-600">
                  <li class="flex items-start">
                    <span class="text-red-600 mr-2">•</span>
                    <span><strong>Urgent:</strong> 2 jam</span>
                  </li>
                  <li class="flex items-start">
                    <span class="text-orange-600 mr-2">•</span>
                    <span><strong>High Priority:</strong> 4 jam</span>
                  </li>
                  <li class="flex items-start">
                    <span class="text-yellow-600 mr-2">•</span>
                    <span><strong>Medium/Low Priority:</strong> 24 jam</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- FAQ Item 3 -->
          <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-xl">
            <button
              @click="toggleFAQ(2)"
              class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200"
            >
              <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                <span class="mr-3 text-purple-600">👥</span>
                Siapa yang bisa menggunakan sistem ini?
              </h4>
              <svg
                :class="['w-5 h-5 text-gray-500 transition-transform duration-300', faqItems[2].open ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              :class="['px-6 transition-all duration-300', faqItems[2].open ? 'py-4' : 'hidden']"
            >
              <div class="border-t border-gray-200 pt-4">
                <p class="text-gray-600 leading-relaxed">
                  Semua pegawai Kementerian Luar Negeri yang memiliki NIP dapat menggunakan sistem ini untuk melaporkan masalah TI. Sistem ini dirancang untuk 4 role utama:
                </p>
                <ul class="mt-3 space-y-2 text-gray-600">
                  <li class="flex items-start">
                    <span class="text-blue-600 mr-2">•</span>
                    <span><strong>User (Pegawai):</strong> Membuat dan melacak tiket</span>
                  </li>
                  <li class="flex items-start">
                    <span class="text-indigo-600 mr-2">•</span>
                    <span><strong>Admin Helpdesk:</strong> Mengelola sistem dan tiket</span>
                  </li>
                  <li class="flex items-start">
                    <span class="text-green-600 mr-2">•</span>
                    <span><strong>Admin Aplikasi:</strong> Mengelola aplikasi</span>
                  </li>
                  <li class="flex items-start">
                    <span class="text-purple-600 mr-2">•</span>
                    <span><strong>Teknisi:</strong> Menyelesaikan masalah teknis</span>
                  </li>
                </ul>
              </div>
            </div>
          </div>

          <!-- FAQ Item 4 -->
          <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-xl">
            <button
              @click="toggleFAQ(3)"
              class="w-full px-6 py-4 text-left flex items-center justify-between hover:bg-gray-50 transition-colors duration-200"
            >
              <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                <span class="mr-3 text-indigo-600">🔍</span>
                Bagaimana cara melacak status tiket saya?
              </h4>
              <svg
                :class="['w-5 h-5 text-gray-500 transition-transform duration-300', faqItems[3].open ? 'rotate-180' : '']"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
              </svg>
            </button>
            <div
              :class="['px-6 transition-all duration-300', faqItems[3].open ? 'py-4' : 'hidden']"
            >
              <div class="border-t border-gray-200 pt-4">
                <p class="text-gray-600 leading-relaxed">
                  Anda dapat melihat status tiket di dashboard atau melalui notifikasi real-time yang akan dikirimkan setiap ada update pada tiket Anda. Sistem juga mengirim email notifikasi untuk setiap perubahan status penting.
                </p>
                <div class="mt-3 flex items-center text-sm text-indigo-600">
                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 012-2h6a2 2 0 012 2v6.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                  Notifikasi real-time tersedia
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Contact Support -->
        <div class="mt-12 text-center">
          <p class="text-gray-600 mb-4">Masih punya pertanyaan?</p>
          <Link
            href="#contact"
            class="inline-flex items-center bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 transition-colors duration-200"
          >
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            Hubungi Support
          </Link>
        </div>
      </div>
    </section>

    <!-- Enhanced Footer -->
    <footer id="contact" class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 text-white py-16">
      <div class="container mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8 lg:gap-12">
          <!-- Company Info -->
          <div class="lg:col-span-2">
            <div class="flex items-center space-x-3 mb-6">
              <div class="w-12 h-12 flex items-center justify-center hover-scale">
                <img src="/logo.png" alt="HelpDesk Kemlu" class="w-12 h-12">
              </div>
              <div>
                <h3 class="text-xl font-bold">HelpDesk Kemlu</h3>
                <p class="text-sm text-gray-400">Kementerian Luar Negeri RI</p>
              </div>
            </div>
            <p class="text-gray-300 leading-relaxed max-w-md">
              Sistem Helpdesk Terpadu Kementerian Luar Negeri Republik Indonesia. Solusi modern untuk manajemen layanan TI yang efisien dan terpercaya.
            </p>
            <div class="mt-6 flex space-x-4">
              <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-indigo-600 rounded-lg flex items-center justify-center transition-colors duration-200">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-4.221 7.276 2.635 11.795 6.79 12.23-.735 2.027-2.896 3.6-5.446 3.811.93.802 2.13 1.281 3.424 1.281 4.18 0 7.412-3.717 6.639-8.083 1.416.865 2.672 2.188 3.477 3.748-1.312-.041-2.548-.402-3.617-1.003-.001.033-.001.066-.001.099 0 3.963 2.826 7.273 6.58 8.03-.688 1.873-2.108 3.324-3.983 4.033 1.072.652 2.335 1.003 3.68 1.003 4.4 0 6.81-3.645 6.664-6.904.047-.18.09-.364.129-.549 1.327 1.318 3.05 2.121 4.92 2.124"/>
                </svg>
              </a>
              <a href="#" class="w-10 h-10 bg-gray-800 hover:bg-indigo-600 rounded-lg flex items-center justify-center transition-colors duration-200">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/>
                </svg>
              </a>
            </div>
          </div>

          <!-- Quick Links -->
          <div>
            <h4 class="text-lg font-semibold mb-6">Quick Links</h4>
            <ul class="space-y-3">
              <li>
                <Link href="/login" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center">
                  <span class="mr-2">→</span> Login
                </Link>
              </li>
              <li>
                <Link href="/register" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center">
                  <span class="mr-2">→</span> Register
                </Link>
              </li>
              <li>
                <a href="#features" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center">
                  <span class="mr-2">→</span> Features
                </a>
              </li>
              <li>
                <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center">
                  <span class="mr-2">→</span> Documentation
                </a>
              </li>
              <li>
                <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200 flex items-center">
                  <span class="mr-2">→</span> Support
                </a>
              </li>
            </ul>
          </div>

          <!-- Contact Info -->
          <div>
            <h4 class="text-lg font-semibold mb-6">Hubungi Kami</h4>
            <ul class="space-y-4 text-gray-300">
              <li class="flex items-start">
                <span class="mr-3 text-indigo-400">📧</span>
                <div>
                  <a href="mailto:helpdesk@kemlu.go.id" class="hover:text-white transition-colors duration-200">
                    helpdesk@kemlu.go.id
                  </a>
                  <p class="text-xs text-gray-500">Email Support</p>
                </div>
              </li>
              <li class="flex items-start">
                <span class="mr-3 text-green-400">📞</span>
                <div>
                  <a href="tel:+622112345678" class="hover:text-white transition-colors duration-200">
                    +62 21 1234 5678
                  </a>
                  <p class="text-xs text-gray-500">Phone Support</p>
                </div>
              </li>
              <li class="flex items-start">
                <span class="mr-3 text-purple-400">📍</span>
                <div>
                  <p>Jl. Taman Pejambon No. 6</p>
                  <p class="text-xs text-gray-500">Jakarta Pusat, 10110</p>
                </div>
              </li>
            </ul>
          </div>
        </div>

        <div class="border-t border-gray-800 mt-12 pt-8 text-center text-gray-400">
          <p class="text-sm">
            &copy; {{ new Date().getFullYear() }} Kementerian Luar Negeri Republik Indonesia. All rights reserved.
          </p>
          <p class="text-xs mt-2">
            HelpDesk Kemlu
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import AnimatedNumber from '@/Components/UI/AnimatedNumber.vue';

const props = defineProps({
  stats: {
    type: Object,
    default: () => ({
      totalTickets: 1250,
      avgResponseTime: 2.5,
      satisfactionRate: 95
    })
  }
});

// Use default stats if not provided
const stats = props.stats || {
  totalTickets: 1250,
  avgResponseTime: 2.5,
  satisfactionRate: 95
};

// Reactive data
const mobileMenuOpen = ref(false);
const faqItems = ref([
  { open: false },
  { open: false },
  { open: false },
  { open: false }
]);

// FAQ toggle function
const toggleFAQ = (index) => {
  faqItems.value[index].open = !faqItems.value[index].open;
};

// Smooth scroll function
const smoothScroll = (elementId) => {
  const element = document.getElementById(elementId);
  if (element) {
    element.scrollIntoView({ behavior: 'smooth' });
  }
};

// Handle smooth scroll for anchor links
onMounted(() => {
  // Add smooth scroll behavior
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const targetId = this.getAttribute('href').substring(1);
      smoothScroll(targetId);
    });
  });

  // Add scroll animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: '0px 0px -50px 0px'
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        entry.target.classList.add('animate-fadeInUp');
      }
    });
  }, observerOptions);

  // Observe elements for animation
  document.querySelectorAll('.animate-on-scroll').forEach(el => {
    observer.observe(el);
  });
});
</script>

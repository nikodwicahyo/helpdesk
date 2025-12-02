<template>
  <div class="min-h-screen flex overflow-hidden">
    <!-- Left Side - Enhanced Branding -->
    <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-indigo-600 via-purple-700 to-indigo-800 p-6 lg:p-8 flex-col justify-between relative overflow-hidden">
      <!-- Enhanced Animated Background -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-80 h-80 bg-white/10 rounded-full mix-blend-multiply filter blur-xl animate-blob"></div>
        <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-purple-500/10 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-2000"></div>
        <div class="absolute top-40 left-20 w-60 h-60 bg-indigo-500/10 rounded-full mix-blend-multiply filter blur-xl animate-blob animation-delay-4000"></div>
      </div>

      <!-- Content -->
      <div class="relative z-10 h-full flex flex-col">
        <!-- Logo Section -->
        <div class="flex items-center space-x-3 mb-4 animate-slideInDown">
          <div class="w-12 h-12 flex items-center justify-center hover-scale transition-all duration-300">
            <img src="/logo.png" alt="HelpDesk Kemlu" class="w-10 h-10">
          </div>
          <div>
            <h1 class="text-2xl font-bold text-white mb-1">HelpDesk Kemlu</h1>
            <p class="text-indigo-100 text-xs">Kementerian Luar Negeri RI</p>
          </div>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex items-center justify-center animate-fadeInUp">
          <div>
            <h2 class="text-3xl lg:text-4xl font-bold text-white mb-4 leading-tight">
              Sistem Helpdesk<br/>
              <span class="bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">Terpadu & Efisien</span>
            </h2>
            <p class="text-indigo-100 text-base lg:text-lg leading-relaxed mb-6 max-w-md">
              Solusi manajemen tiket TI modern untuk meningkatkan produktivitas penanganan masalah teknis.
            </p>

            <!-- Enhanced Features -->
            <div class="space-y-3 animate-fadeInUp animation-delay-200">
              <div class="flex items-center space-x-3 group">
                <div class="w-8 h-8 bg-white/20 backdrop-blur-lg rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                  </svg>
                </div>
                <span class="text-white text-sm group-hover:text-indigo-100 transition-colors duration-300">Tracking tiket real-time</span>
              </div>
              <div class="flex items-center space-x-3 group">
                <div class="w-8 h-8 bg-white/20 backdrop-blur-lg rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 012-2h6a2 2 0 012 2v6.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                  </svg>
                </div>
                <span class="text-white text-sm group-hover:text-indigo-100 transition-colors duration-300">Notifikasi otomatis</span>
              </div>
              <div class="flex items-center space-x-3 group">
                <div class="w-8 h-8 bg-white/20 backdrop-blur-lg rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                  </svg>
                </div>
                <span class="text-white text-sm group-hover:text-indigo-100 transition-colors duration-300">Laporan & analytics</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Footer -->
        <div class="relative z-10 text-indigo-200 text-xs animate-slideInUp animation-delay-400">
          <p>&copy; {{ new Date().getFullYear() }} Kementerian Luar Negeri Republik Indonesia</p>
        </div>
      </div>
    </div>

    <!-- Right Side - Enhanced Login Form -->
    <div class="flex-1 flex items-center justify-center p-3 sm:p-4 lg:p-6 bg-gradient-to-br from-gray-50 to-gray-100 relative overflow-hidden">
      <!-- Subtle background elements -->
      <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 right-20 w-32 h-32 bg-indigo-100/30 rounded-full blur-2xl animate-pulse"></div>
        <div class="absolute bottom-20 left-20 w-40 h-40 bg-purple-100/20 rounded-full blur-2xl animate-pulse animation-delay-2000"></div>
      </div>

      <div class="w-full max-w-md relative z-10 py-2 sm:py-4">
        <!-- Mobile Logo -->
        <div class="lg:hidden flex items-center justify-center space-x-3 mb-4">
          <div class="w-10 h-10 flex items-center justify-center">
            <img src="/logo.png" alt="HelpDesk Kemlu" class="w-8 h-8">
          </div>
          <div>
            <h1 class="text-lg font-bold text-gray-900">HelpDesk Kemlu</h1>
            <p class="text-gray-500 text-xs">Kementerian Luar Negeri RI</p>
          </div>
        </div>

        <!-- Enhanced Login Card -->
        <div class="bg-white/95 backdrop-blur-xl rounded-xl shadow-xl p-4 sm:p-6 border border-white/20 hover-lift animate-fadeIn">
          <div class="mb-4 sm:mb-6 text-center">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 animate-slideInDown">Selamat Datang</h2>
            <p class="text-gray-600 text-xs sm:text-sm animate-slideInUp animation-delay-200">Silakan login untuk melanjutkan</p>
          </div>

          <!-- Enhanced Success Message -->
          <transition name="slide-fade" appear>
            <div v-if="auth.loginForm.message && auth.loginForm.message.type === 'success'" class="mb-4 p-3 bg-green-50/80 backdrop-blur border border-green-200/50 rounded-lg animate-slideInDown">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-green-600 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <span class="text-green-800 text-xs font-medium">{{ auth.loginForm.message.text }}</span>
                </div>
              </div>
            </div>
          </transition>

          <!-- Enhanced Warning Message (Session Expired) -->
          <transition name="slide-fade" appear>
            <div v-if="auth.loginForm.message && auth.loginForm.message.type === 'warning'" class="mb-4 p-3 bg-yellow-50/80 backdrop-blur border border-yellow-200/50 rounded-lg animate-slideInDown">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-yellow-600 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <span class="text-yellow-800 text-xs font-medium">{{ auth.loginForm.message.text }}</span>
                </div>
              </div>
            </div>
          </transition>

          <!-- Enhanced Error Message -->
          <transition name="slide-fade" appear>
            <div v-if="auth.loginForm.message && auth.loginForm.message.type === 'error'" class="mb-4 p-3 bg-red-50/80 backdrop-blur border border-red-200/50 rounded-lg animate-slideInDown">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-red-600 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <span class="text-red-800 text-xs font-medium">{{ auth.loginForm.message.text }}</span>
                </div>
              </div>
            </div>
          </transition>

          <!-- Enhanced Loading State -->
          <transition name="fade" appear>
            <div v-if="auth.loginForm.processing" class="mb-4 p-3 bg-blue-50/80 backdrop-blur border border-blue-200/50 rounded-lg">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="animate-spin -ml-1 mr-2 h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                  </svg>
                </div>
                <div class="flex-1">
                  <span class="text-blue-800 text-xs font-medium">Memproses login...</span>
                </div>
              </div>
            </div>
          </transition>

          <!-- Login Form -->
          <form @submit.prevent="submit">
            <!-- Enhanced NIP Input -->
            <div class="mb-4">
              <label for="nip" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 flex items-center">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                NIP (Nomor Induk Pegawai)
              </label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                  </svg>
                </div>
                <input
                  id="nip"
                  v-model="auth.loginForm.nip"
                  type="text"
                  required
                  maxlength="18"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  autocomplete="username"
                  :disabled="auth.loginForm.processing"
                  class="block w-full pl-8 sm:pl-10 pr-10 py-2 sm:py-2.5 border-2 rounded-lg focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 disabled:bg-gray-50 disabled:cursor-not-allowed text-xs sm:text-sm"
                  :class="[
                    auth.loginForm.errors.nip
                      ? 'border-red-500 focus:ring-red-500/50 focus:border-red-500 bg-red-50/30'
                      : 'border-gray-300 hover:border-gray-400',
                    auth.loginForm.nip && auth.loginForm.nip.length === 18 && !auth.loginForm.errors.nip
                      ? 'border-green-500 bg-green-50/30 focus:ring-green-500/50 focus:border-green-500'
                      : ''
                  ]"
                  placeholder="Masukkan 18 digit NIP Anda"
                  @input="handleNipInput"
                  @blur="validateNip"
                />
                <!-- Enhanced NIP Validation Icon -->
                <div v-if="auth.loginForm.nip" class="absolute inset-y-0 right-0 pr-2 sm:pr-3 flex items-center">
                  <transition name="bounce" appear>
                    <svg v-if="auth.loginForm.nip.length === 18 && /^\d+$/.test(auth.loginForm.nip)" class="h-4 w-4 text-green-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else-if="auth.loginForm.nip.length > 0" class="h-4 w-4 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                  </transition>
                </div>
              </div>
              <!-- Enhanced NIP Validation Feedback -->
              <transition name="slide-fade" appear>
                <div v-if="auth.loginForm.errors.nip" class="mt-1.5 text-xs text-red-600 animate-fade-in flex items-start">
                  <svg class="w-3 h-3 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <span>{{ auth.loginForm.errors.nip }}</span>
                </div>
                <div v-else-if="auth.loginForm.nip && auth.loginForm.nip.length > 0" class="mt-1.5 text-xs">
                  <span v-if="auth.loginForm.nip.length < 18" class="text-yellow-600 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    {{ 18 - auth.loginForm.nip.length }} digit lagi
                  </span>
                  <span v-else-if="auth.loginForm.nip.length === 18 && /^\d+$/.test(auth.loginForm.nip)" class="text-green-600 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    Format NIP valid
                  </span>
                  <span v-else class="text-amber-600 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    NIP hanya boleh berisi angka
                  </span>
                </div>
              </transition>
            </div>

            <!-- Enhanced Password Input -->
            <div class="mb-4">
              <label for="password" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 flex items-center justify-between">
                <span class="flex items-center">
                  <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                  Password
                </span>
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  :disabled="auth.loginForm.processing"
                  class="text-xs text-indigo-600 hover:text-indigo-800 font-medium disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                  {{ showPassword ? 'Sembunyikan' : 'Tampilkan' }}
                </button>
              </label>
              <div class="relative group">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                  <svg class="h-5 w-5 text-gray-400 group-focus-within:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                  </svg>
                </div>
                <input
                  id="password"
                  v-model="auth.loginForm.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  autocomplete="current-password"
                  :disabled="auth.loginForm.processing"
                  class="block w-full pl-8 sm:pl-10 pr-10 py-2 sm:py-2.5 border-2 rounded-lg focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 disabled:bg-gray-50 disabled:cursor-not-allowed text-xs sm:text-sm"
                  :class="[
                    auth.loginForm.errors.password
                      ? 'border-red-500 focus:ring-red-500/50 focus:border-red-500 bg-red-50/30'
                      : 'border-gray-300 hover:border-gray-400',
                    auth.loginForm.password && auth.loginForm.password.length >= 6 && !auth.loginForm.errors.password
                      ? 'border-green-500 bg-green-50/30 focus:ring-green-500/50 focus:border-green-500'
                      : ''
                  ]"
                  placeholder="Masukkan password Anda"
                  @input="handlePasswordInput"
                />
                <!-- Enhanced Password Toggle Button -->
                <button
                  type="button"
                  @click="showPassword = !showPassword"
                  :disabled="auth.loginForm.processing"
                  class="absolute inset-y-0 right-0 pr-2 sm:pr-3 flex items-center disabled:cursor-not-allowed disabled:opacity-50 hover:bg-gray-50/50 rounded-lg px-1 py-1 transition-all duration-200"
                >
                  <transition name="flip" appear>
                    <svg v-if="!showPassword" class="h-4 w-4 text-gray-400 hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="h-4 w-4 text-gray-400 hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                  </transition>
                </button>
              </div>
              <!-- Enhanced Password Error Message -->
              <transition name="slide-fade" appear>
                <div v-if="auth.loginForm.errors.password" class="mt-1.5 text-xs text-red-600 animate-fade-in flex items-start">
                  <svg class="w-3 h-3 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <span>{{ auth.loginForm.errors.password }}</span>
                </div>
              </transition>

              <!-- Enhanced Password Strength Indicator -->
              <transition name="slide-fade" appear>
                <div v-if="auth.loginForm.password && !auth.loginForm.errors.password" class="mt-2 space-y-1.5">
                  <div class="flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-600">Kekuatan:</span>
                    <span class="text-xs font-medium" :class="getPasswordStrengthTextColor()">{{ getPasswordStrengthText() }}</span>
                  </div>
                  <div class="relative">
                    <div class="flex-1 bg-gray-200 rounded-full h-1.5 overflow-hidden">
                      <div
                        class="h-1.5 rounded-full transition-all duration-500 ease-out relative"
                        :class="getPasswordStrengthClass()"
                        :style="{ width: getPasswordStrengthPercentage() + '%' }"
                      >
                        <div class="absolute inset-0 bg-gradient-to-r from-transparent via-white/30 to-transparent animate-shimmer"></div>
                      </div>
                    </div>
                  </div>
                  <!-- Password Requirements -->
                  <div class="grid grid-cols-1 sm:grid-cols-2 gap-1 text-xs">
                    <div class="flex items-center" :class="auth.loginForm.password.length >= 6 ? 'text-green-600' : 'text-gray-400'">
                      <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                      Minimal 6 karakter
                    </div>
                    <div class="flex items-center" :class="auth.loginForm.password.length >= 8 ? 'text-green-600' : 'text-gray-400'">
                      <svg class="w-2.5 h-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                      </svg>
                      8+ karakter lebih aman
                    </div>
                  </div>
                </div>
              </transition>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between mb-4">
              <label class="flex items-center">
                <input
                  v-model="auth.loginForm.remember"
                  type="checkbox"
                  :disabled="auth.loginForm.processing"
                  class="h-3 w-3 sm:h-4 sm:w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded disabled:opacity-50"
                />
                <span class="ml-1.5 text-xs sm:text-sm text-gray-700" :class="auth.loginForm.processing ? 'opacity-50' : ''">Ingat saya</span>
              </label>
              <button
                type="button"
                @click="showForgotPasswordModal = true"
                class="text-xs text-indigo-600 hover:text-indigo-800 font-medium disabled:opacity-50 disabled:cursor-not-allowed"
                :disabled="auth.loginForm.processing"
              >
                Lupa password?
              </button>
            </div>

            <!-- Submit Button -->
            <button
              type="submit"
              :disabled="auth.loginForm.processing || !isFormValid"
              class="w-full flex justify-center items-center py-2.5 sm:py-3 px-4 border border-transparent rounded-lg shadow-sm text-xs sm:text-sm font-medium text-white transition disabled:opacity-50 disabled:cursor-not-allowed"
              :class="[
                auth.loginForm.processing || !isFormValid
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500'
              ]"
            >
              <span v-if="!auth.loginForm.processing">Login</span>
              <span v-else class="flex items-center">
                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Memproses...
              </span>
            </button>
          </form>

          <!-- Divider -->
          <div class="mt-4">
            <div class="relative">
              <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-gray-300"></div>
              </div>
              <div class="relative flex justify-center text-xs">
                <span class="px-2 bg-white text-gray-500">Belum punya akun?</span>
              </div>
            </div>
          </div>

          <!-- Register Link -->
          <div class="mt-4 text-center">
            <Link
              href="/register"
              class="inline-flex items-center text-xs font-medium text-indigo-600 hover:text-indigo-800"
            >
              Daftar sebagai Pegawai
              <svg class="ml-1 w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
              </svg>
            </Link>
          </div>

          <!-- Info Box -->
          <div class="mt-4 bg-blue-50 border border-blue-200 rounded-lg p-3">
            <div class="flex">
              <svg class="h-4 w-4 text-blue-600 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
              </svg>
              <div class="text-xs text-blue-800">
                <p class="font-medium mb-0.5">Gunakan NIP Anda untuk login</p>
                <p>Sistem otomatis mendeteksi role anda</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Enhanced Back to Home -->
        <div class="mt-4 text-center animate-fadeInUp animation-delay-400">
          <Link
            href="/"
            class="inline-flex items-center text-xs text-gray-600 hover:text-indigo-600 font-medium px-3 py-2 rounded-lg hover:bg-gray-50 transition-all duration-200 group"
          >
            <svg class="w-3 h-3 mr-1.5 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Kembali ke Beranda
          </Link>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useAuth } from '@/composables/useAuth.js';

// Composables
const auth = useAuth();
const page = usePage();

// Component state
const showPassword = ref(false);
const showForgotPasswordModal = ref(false);
const sessionExpiredMessage = ref(false);

// Check for session_expired query parameter on mount
onMounted(() => {
  // Check URL for session_expired parameter
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get('session_expired') === '1') {
    sessionExpiredMessage.value = true;
    // Show warning message
    auth.loginForm.message = { 
      type: 'warning', 
      text: 'Sesi Anda telah berakhir karena tidak aktif. Silakan login kembali.' 
    };
    
    // Clean up the URL
    const url = new URL(window.location.href);
    url.searchParams.delete('session_expired');
    window.history.replaceState({}, '', url.pathname);
    
    // Auto-clear message after 10 seconds
    setTimeout(() => {
      if (sessionExpiredMessage.value) {
        auth.loginForm.message = null;
        sessionExpiredMessage.value = false;
      }
    }, 10000);
  }
  
  // Also check for flash warning message from backend
  if (page.props?.flash?.warning) {
    auth.loginForm.message = { 
      type: 'warning', 
      text: page.props.flash.warning 
    };
  }
});

// Computed properties
const isFormValid = computed(() => {
  return auth.loginForm.nip.length === 18 &&
         /^\d+$/.test(auth.loginForm.nip) &&
         auth.loginForm.password.length >= 6 &&
         !auth.loginForm.processing;
});

// Methods
const handleNipInput = async () => {
  auth.clearFormErrors();

  // Real-time NIP validation
  if (auth.loginForm.nip.length === 18 && /^\d+$/.test(auth.loginForm.nip)) {
    try {
      const nipStatus = await auth.checkNip(auth.loginForm.nip);
      if (!nipStatus.exists) {
        auth.setFormError('nip', 'NIP tidak ditemukan dalam sistem');
      } else if (!nipStatus.active) {
        auth.setFormError('nip', 'Akun tidak aktif. Silakan hubungi administrator');
      }
    } catch (error) {
      console.error('NIP validation error:', error);
    }
  }
};

const validateNip = () => {
  if (auth.loginForm.nip && auth.loginForm.nip.length !== 18) {
    auth.setFormError('nip', 'NIP harus terdiri dari 18 digit');
  } else if (auth.loginForm.nip && !/^\d+$/.test(auth.loginForm.nip)) {
    auth.setFormError('nip', 'NIP hanya boleh berisi angka');
  }
};

const handlePasswordInput = () => {
  auth.clearFormErrors();
};

const getPasswordStrengthClass = () => {
  const length = auth.loginForm.password.length;
  if (length >= 8) return 'bg-gradient-to-r from-green-400 to-green-600';
  if (length >= 6) return 'bg-gradient-to-r from-yellow-400 to-yellow-600';
  return 'bg-gradient-to-r from-red-400 to-red-600';
};

const getPasswordStrengthTextColor = () => {
  const length = auth.loginForm.password.length;
  if (length >= 8) return 'text-green-600';
  if (length >= 6) return 'text-yellow-600';
  return 'text-red-600';
};

const getPasswordStrengthPercentage = () => {
  const length = auth.loginForm.password.length;
  if (length >= 12) return 100;
  if (length >= 8) return 80;
  if (length >= 6) return 60;
  if (length >= 4) return 40;
  if (length >= 2) return 20;
  return 0;
};

const getPasswordStrengthText = () => {
  const length = auth.loginForm.password.length;
  if (length >= 12) return 'Sangat Kuat';
  if (length >= 8) return 'Kuat';
  if (length >= 6) return 'Sedang';
  if (length >= 4) return 'Lemah';
  return 'Sangat Lemah';
};

const submit = async () => {
  // Validate form before submission
  if (!auth.validateForm()) {
    return;
  }

  // Show loading state
  auth.loginForm.processing = true;

  try {
    const result = await auth.login();

    if (result.success) {
      // Show success message briefly before redirect
      showSuccessMessage('Login berhasil! Mengalihkan ke dashboard...');

      // The redirect is now handled by the auth composable and login response
      // No need to manually redirect here as the backend will provide the redirect URL

    } else {
      // Show error message
      showErrorMessage(result.message);

      // Set specific field errors if available
      if (result.errors) {
        Object.keys(result.errors).forEach(field => {
          auth.setFormError(field, result.errors[field]);
        });
      }
    }
  } catch (error) {
    console.error('Login submission error:', error);
    showErrorMessage('Terjadi kesalahan saat login. Silakan coba lagi.');
  } finally {
    auth.loginForm.processing = false;
  }
};

// Notification helpers
const showSuccessMessage = (message) => {
  auth.loginForm.message = { type: 'success', text: message };
  setTimeout(() => {
    auth.loginForm.message = null;
  }, 5000);
};

const showErrorMessage = (message) => {
  auth.loginForm.message = { type: 'error', text: message };
  setTimeout(() => {
    auth.loginForm.message = null;
  }, 5000);
};

// Watch for form changes to clear messages
watch(() => auth.loginForm.nip, () => {
  if (auth.loginForm.message) {
    auth.loginForm.message = null;
  }
});

watch(() => auth.loginForm.password, () => {
  if (auth.loginForm.message) {
    auth.loginForm.message = null;
  }
});
</script>

<style scoped>
/* Enhanced Animations */
@keyframes fadeIn {
  from {
    opacity: 0;
    transform: translateY(-10px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInDown {
  from {
    opacity: 0;
    transform: translateY(-20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes slideInUp {
  from {
    opacity: 0;
    transform: translateY(20px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}

@keyframes bounce {
  0%, 20%, 53%, 80%, 100% {
    transform: translate3d(0, 0, 0);
  }
  40%, 43% {
    transform: translate3d(0, -10px, 0);
  }
  70% {
    transform: translate3d(0, -5px, 0);
  }
  90% {
    transform: translate3d(0, -2px, 0);
  }
}

@keyframes flip {
  from {
    transform: rotateY(0);
  }
  to {
    transform: rotateY(180deg);
  }
}

@keyframes shimmer {
  0% {
    transform: translateX(-100%);
  }
  100% {
    transform: translateX(100%);
  }
}

/* Animation Classes */
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}

.animate-slideInDown {
  animation: slideInDown 0.5s ease-out;
}

.animate-slideInUp {
  animation: slideInUp 0.5s ease-out;
}

.animate-bounce {
  animation: bounce 1s ease-out;
}

.animate-shimmer {
  animation: shimmer 2s infinite;
}

/* Transition Classes */
.slide-fade-enter-active,
.slide-fade-leave-active {
  transition: all 0.3s ease-out;
}

.slide-fade-enter-from {
  transform: translateY(-10px);
  opacity: 0;
}

.slide-fade-leave-to {
  transform: translateY(10px);
  opacity: 0;
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease-out;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.bounce-enter-active {
  animation: bounce 0.6s ease-out;
}

.flip-enter-active {
  animation: flip 0.3s ease-out;
}

/* Custom scrollbar for webkit browsers */
::-webkit-scrollbar {
  width: 6px;
}

::-webkit-scrollbar-track {
  background: #f1f5f9;
}

::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Enhanced Focus styles for better accessibility */
input:focus {
  outline: none;
}

button:focus {
  outline: none;
}

/* Glass morphism effect enhancement */
.bg-white\/95 {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

/* Loading state styles */
.pointer-events-none {
  pointer-events: none;
}

/* Enhanced Responsive improvements */
@media (max-width: 640px) {
  .min-h-screen {
    min-height: 100vh;
    min-height: 100svh; /* Better mobile support */
  }

  /* Adjust text sizes for better mobile readability */
  .text-2xl {
    font-size: 1.25rem;
    line-height: 1.75rem;
  }

  .text-3xl {
    font-size: 1.5rem;
    line-height: 2rem;
  }

  .text-4xl {
    font-size: 1.875rem;
    line-height: 2.25rem;
  }

  .text-6xl {
    font-size: 2.25rem;
    line-height: 2.5rem;
  }

  /* Better touch targets on mobile */
  button {
    min-height: 44px;
    min-width: 44px;
  }

  input {
    min-height: 40px;
  }

  /* Enhanced mobile form spacing */
  .mb-4 {
    margin-bottom: 1rem;
  }

  .mt-4 {
    margin-top: 1rem;
  }

  .mt-6 {
    margin-top: 1.5rem;
  }

  /* More compact mobile layout */
  .max-w-md {
    max-width: 20rem; /* 320px */
  }
}

@media (max-width: 380px) {
  /* Extra small screens */
  .px-6 {
    padding-left: 1rem;
    padding-right: 1rem;
  }

  .py-8 {
    padding-top: 1.5rem;
    padding-bottom: 1.5rem;
  }
}

/* Reduce motion for users who prefer it */
@media (prefers-reduced-motion: reduce) {
  *,
  *::before,
  *::after {
    animation-duration: 0.01ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: 0.01ms !important;
  }
}

/* Dark mode support (if needed in the future) */
@media (prefers-color-scheme: dark) {
  /* Add dark mode styles here if needed */
}

/* High contrast mode support */
@media (prefers-contrast: high) {
  .border-gray-300 {
    border-color: #000000;
  }

  .text-gray-600 {
    color: #000000;
  }

  .bg-gray-50 {
    background-color: #ffffff;
  }
}
</style>

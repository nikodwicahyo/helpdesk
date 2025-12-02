<template>
  <div class="min-h-screen flex">
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
              Bergabung dengan<br/>
              <span class="bg-gradient-to-r from-white to-indigo-200 bg-clip-text text-transparent">HelpDesk Kemlu</span>
            </h2>
            <p class="text-indigo-100 text-base lg:text-lg leading-relaxed mb-6 max-w-md">
              Daftarkan diri Anda untuk mengakses sistem helpdesk dan mendapatkan bantuan teknis dengan cepat.
            </p>

            <!-- Enhanced Benefits -->
            <div class="space-y-3 animate-fadeInUp animation-delay-200">
              <div class="flex items-center space-x-3 group">
                <div class="w-8 h-8 bg-white/20 backdrop-blur-lg rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                  </svg>
                </div>
                <span class="text-white text-sm group-hover:text-indigo-100 transition-colors duration-300">Buat tiket dengan mudah</span>
              </div>
              <div class="flex items-center space-x-3 group">
                <div class="w-8 h-8 bg-white/20 backdrop-blur-lg rounded-lg flex items-center justify-center group-hover:scale-110 transition-transform duration-300">
                  <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </div>
                <span class="text-white text-sm group-hover:text-indigo-100 transition-colors duration-300">Tracking status real-time</span>
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
                <span class="text-white text-sm group-hover:text-indigo-100 transition-colors duration-300">Dashboard analytics</span>
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

    <!-- Right Side - Enhanced Register Form -->
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

        <!-- Enhanced Register Card -->
        <div class="bg-white/95 backdrop-blur-xl rounded-xl shadow-xl p-4 sm:p-6 border border-white/20 hover-lift animate-fadeIn">
          <div class="mb-4 sm:mb-6 text-center">
            <h2 class="text-xl sm:text-2xl font-bold text-gray-900 mb-2 animate-slideInDown">Daftar Akun Baru</h2>
            <p class="text-gray-600 text-xs sm:text-sm animate-slideInUp animation-delay-200">Registrasi untuk Pegawai Kemlu</p>
          </div>

          <!-- Enhanced Success Message -->
          <transition name="slide-fade" appear>
            <div v-if="flashMessages.success" class="mb-4 p-3 bg-green-50/80 backdrop-blur border border-green-200/50 rounded-lg animate-slideInDown">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-green-600 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="text-green-800 text-xs font-medium">{{ flashMessages.success }}</p>
                </div>
              </div>
            </div>
          </transition>

          <!-- Enhanced Error Message -->
          <transition name="slide-fade" appear>
            <div v-if="flashMessages.error" class="mb-4 p-3 bg-red-50/80 backdrop-blur border border-red-200/50 rounded-lg animate-slideInDown">
              <div class="flex items-center">
                <div class="flex-shrink-0">
                  <svg class="w-5 h-5 text-red-600 mr-2 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                  </svg>
                </div>
                <div class="flex-1">
                  <p class="text-red-800 text-xs font-medium">{{ flashMessages.error }}</p>
                </div>
              </div>
            </div>
          </transition>

          <!-- Enhanced Register Form -->
          <form @submit.prevent="submit" class="space-y-4">
            <!-- Enhanced NIP Input -->
            <div class="relative group">
              <label for="nip" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-1.5 flex items-center">
                <svg class="w-3 h-3 sm:w-4 sm:h-4 mr-1.5 sm:mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                NIP (Nomor Induk Pegawai) <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  id="nip"
                  v-model="form.nip"
                  type="text"
                  required
                  maxlength="18"
                  inputmode="numeric"
                  pattern="[0-9]*"
                  autocomplete="username"
                  class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 pr-10 border-2 rounded-lg focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 text-xs sm:text-sm"
                  :class="getInputClass('nip')"
                  placeholder="Masukkan 18 digit NIP Anda"
                  @input="validateNIPBasic()"
                  @blur="validateNIP"
                />
                <!-- Enhanced Validation Icons -->
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                  <transition name="bounce" appear>
                    <svg v-if="validationStatus.nip === 'valid'" class="h-5 w-5 text-green-500 animate-pulse" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <svg v-else-if="validationStatus.nip === 'invalid'" class="h-5 w-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                      <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div v-else-if="validationStatus.nip === 'checking'" class="animate-spin h-5 w-5 border-2 border-gray-300 border-t-indigo-600 rounded-full"></div>
                  </transition>
                </div>
              </div>
              <!-- Enhanced Validation Feedback -->
              <transition name="slide-fade" appear>
                <div v-if="errors.nip" class="mt-2 text-sm text-red-600 animate-fade-in flex items-start">
                  <svg class="w-4 h-4 mr-1 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <span>{{ errors.nip }}</span>
                </div>
                <div v-else-if="validationMessages.nip" class="mt-2 text-sm animate-fade-in" :class="validationStatus.nip === 'valid' ? 'text-green-600 flex items-center' : 'text-yellow-600 flex items-center'">
                  <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path v-if="validationStatus.nip === 'valid'" fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    <path v-else fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                  </svg>
                  <span>{{ validationMessages.nip }}</span>
                </div>
              </transition>
              <div v-if="validationSuggestions.nip.length > 0" class="mt-1 text-xs text-gray-500 bg-blue-50 rounded-lg p-2 animate-fade-in">
                <span class="font-medium text-blue-700">Saran:</span>
                <ul class="list-disc list-inside mt-1 space-y-1">
                  <li v-for="suggestion in validationSuggestions.nip" :key="suggestion" class="text-blue-600">{{ suggestion }}</li>
                </ul>
              </div>
              <p v-else class="mt-1 text-xs text-gray-500 flex items-center">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                Masukkan 18 digit NIP Anda
              </p>
            </div>

            <!-- Nama Lengkap -->
            <div>
              <label for="nama_lengkap" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                Nama Lengkap <span class="text-red-500">*</span>
              </label>
              <input
                id="nama_lengkap"
                v-model="form.nama_lengkap"
                type="text"
                required
                maxlength="100"
                autocomplete="name"
                class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                :class="errors.nama_lengkap ? 'border-red-500' : 'border-gray-300'"
                placeholder="Nama lengkap sesuai KTP"
                @input="validateNamaLengkap()"
              />
              <p v-if="errors.nama_lengkap" class="mt-1 text-xs text-red-600">{{ errors.nama_lengkap }}</p>
            </div>

            <!-- Email -->
            <div class="relative">
              <label for="email" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                Email <span class="text-red-500">*</span>
              </label>
              <input
                id="email"
                v-model="form.email"
                type="email"
                required
                maxlength="100"
                autocomplete="email"
                class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 pr-8 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                :class="getInputClass('email')"
                placeholder="email@kemlu.go.id"
                @input="validateEmailBasic()"
                @blur="validateEmail"
              />
              <div class="absolute inset-y-0 right-0 pr-2 flex items-center">
                <svg v-if="validationStatus.email === 'valid'" class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <svg v-else-if="validationStatus.email === 'invalid'" class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <div v-else-if="validationStatus.email === 'checking'" class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-indigo-600 rounded-full"></div>
              </div>
              <p v-if="errors.email" class="mt-1 text-xs text-red-600">{{ errors.email }}</p>
              <p v-else-if="validationMessages.email" class="mt-1 text-xs" :class="validationStatus.email === 'valid' ? 'text-green-600' : 'text-yellow-600'">
                {{ validationMessages.email }}
              </p>
              <div v-if="validationSuggestions.email.length > 0" class="mt-1 text-xs text-gray-500">
                <span class="font-medium">Saran:</span>
                <ul class="list-disc list-inside mt-1">
                  <li v-for="suggestion in validationSuggestions.email" :key="suggestion">{{ suggestion }}</li>
                </ul>
              </div>
            </div>

            <!-- Jabatan -->
            <div>
              <label for="jabatan" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                Jabatan <span class="text-red-500">*</span>
              </label>
              <input
                id="jabatan"
                v-model="form.jabatan"
                type="text"
                required
                maxlength="100"
                autocomplete="organization-title"
                class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                :class="errors.jabatan ? 'border-red-500' : 'border-gray-300'"
                placeholder="Contoh: Staff IT"
                @input="validateJabatan()"
              />
              <p v-if="errors.jabatan" class="mt-1 text-xs text-red-600">{{ errors.jabatan }}</p>
            </div>

            <!-- Unit Kerja -->
            <div>
              <label for="unit_kerja" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                Unit Kerja <span class="text-red-500">*</span>
              </label>
              <input
                id="unit_kerja"
                v-model="form.unit_kerja"
                type="text"
                required
                maxlength="100"
                autocomplete="organization"
                class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                :class="errors.unit_kerja ? 'border-red-500' : 'border-gray-300'"
                placeholder="Contoh: Direktorat TI"
                @input="validateUnitKerja()"
              />
              <p v-if="errors.unit_kerja" class="mt-1 text-xs text-red-600">{{ errors.unit_kerja }}</p>
            </div>

            <!-- No Telepon -->
            <div>
              <label for="no_telepon" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                No. Telepon
              </label>
              <input
                id="no_telepon"
                v-model="form.no_telepon"
                type="tel"
                maxlength="20"
                autocomplete="tel"
                class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                :class="errors.no_telepon ? 'border-red-500' : 'border-gray-300'"
                placeholder="08123456789"
                @input="validateNoTelepon()"
              />
              <p v-if="errors.no_telepon" class="mt-1 text-xs text-red-600">{{ errors.no_telepon }}</p>
            </div>

            <!-- Password -->
            <div>
              <label for="password" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  required
                  autocomplete="new-password"
                  class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 pr-16 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                  :class="getInputClass('password')"
                  placeholder="Minimal 8 karakter"
                  @input="validatePasswordWithStrength()"
                />
                <div class="absolute inset-y-0 right-0 flex items-center pr-1 space-x-1">
                  <button
                    type="button"
                    @click="showPassword = !showPassword"
                    class="p-1 text-gray-400 hover:text-gray-600"
                  >
                    <svg v-if="!showPassword" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                  </button>
                  <div v-if="validationStatus.password === 'checking'" class="animate-spin h-4 w-4 border-2 border-gray-300 border-t-indigo-600 rounded-full"></div>
                  <svg v-else-if="validationStatus.password === 'valid'" class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <svg v-else-if="validationStatus.password === 'invalid'" class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>

              <!-- Password Strength Indicator -->
              <div v-if="form.password" class="mt-1.5">
                <div class="flex items-center justify-between mb-1">
                  <span class="text-xs text-gray-600">Kekuatan:</span>
                  <span class="text-xs font-medium" :class="getPasswordStrengthColor()">
                    {{ getPasswordStrengthText() }}
                  </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-1.5">
                  <div
                    class="h-1.5 rounded-full transition-all duration-300 ease-out"
                    :class="getPasswordStrengthBarColor()"
                    :style="{ width: getPasswordStrengthPercentage() + '%' }"
                  ></div>
                </div>
              </div>

              <!-- Password Requirements -->
              <div v-if="form.password" class="mt-1.5 grid grid-cols-1 sm:grid-cols-2 gap-1 text-xs">
                <div class="flex items-center" :class="passwordChecks.length ? 'text-green-600' : 'text-gray-400'">
                  <svg v-if="passwordChecks.length" class="h-2.5 w-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  <span>Min 8 karakter</span>
                </div>
                <div class="flex items-center" :class="passwordChecks.lowercase ? 'text-green-600' : 'text-gray-400'">
                  <svg v-if="passwordChecks.lowercase" class="h-2.5 w-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  <span>Huruf kecil</span>
                </div>
                <div class="flex items-center" :class="passwordChecks.uppercase ? 'text-green-600' : 'text-gray-400'">
                  <svg v-if="passwordChecks.uppercase" class="h-2.5 w-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  <span>Huruf besar</span>
                </div>
                <div class="flex items-center" :class="passwordChecks.number ? 'text-green-600' : 'text-gray-400'">
                  <svg v-if="passwordChecks.number" class="h-2.5 w-2.5 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  <span>Angka</span>
                </div>
              </div>

              <p v-if="errors.password" class="mt-1 text-xs text-red-600">{{ errors.password }}</p>
              <p v-else-if="validationMessages.password" class="mt-1 text-xs" :class="validationStatus.password === 'valid' ? 'text-green-600' : 'text-yellow-600'">
                {{ validationMessages.password }}
              </p>
            </div>

            <!-- Password Confirmation -->
            <div>
              <label for="password_confirmation" class="block text-xs sm:text-sm font-medium text-gray-700 mb-1.5">
                Konfirmasi Password <span class="text-red-500">*</span>
              </label>
              <div class="relative">
                <input
                  id="password_confirmation"
                  v-model="form.password_confirmation"
                  :type="showPasswordConfirmation ? 'text' : 'password'"
                  required
                  autocomplete="new-password"
                  class="block w-full px-2.5 sm:px-3 py-2 sm:py-2.5 pr-8 border rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-xs sm:text-sm"
                  :class="getInputClass('password_confirmation')"
                  placeholder="Ulangi password"
                  @input="validatePasswordConfirmation()"
                />
                <div class="absolute inset-y-0 right-0 pr-2 flex items-center">
                  <button
                    type="button"
                    @click="showPasswordConfirmation = !showPasswordConfirmation"
                    class="p-1 text-gray-400 hover:text-gray-600"
                  >
                    <svg v-if="!showPasswordConfirmation" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                    </svg>
                  </button>
                  <svg v-if="form.password_confirmation && passwordChecks.match" class="h-4 w-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                  </svg>
                  <svg v-else-if="form.password_confirmation && !passwordChecks.match" class="h-4 w-4 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                  </svg>
                </div>
              </div>
              <p v-if="errors.password_confirmation" class="mt-1 text-xs text-red-600">{{ errors.password_confirmation }}</p>
              <p v-else-if="form.password_confirmation && passwordChecks.match" class="mt-1 text-xs text-green-600">Password cocok</p>
              <p v-else-if="form.password_confirmation && !passwordChecks.match" class="mt-1 text-xs text-red-600">Password tidak cocok</p>
            </div>

  
            <!-- Enhanced Submit Button -->
            <button
              type="submit"
              :disabled="form.processing || !isFormValid"
              class="w-full flex justify-center items-center py-2.5 sm:py-3 px-4 border-2 border-transparent rounded-lg text-xs sm:text-sm font-semibold text-white transition-all duration-200 hover-lift group relative overflow-hidden"
              :class="[
                form.processing || !isFormValid
                  ? 'bg-gray-400 cursor-not-allowed'
                  : 'bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5'
              ]"
            >
              <!-- Button background animation -->
              <div v-if="!form.processing && isFormValid" class="absolute inset-0 bg-gradient-to-r from-white/20 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

              <svg v-if="form.processing" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white relative z-10" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>

              <span v-if="!form.processing && isFormValid" class="relative z-10 flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                </svg>
                Daftar Sekarang
              </span>
              <span v-else-if="form.processing" class="relative z-10">Memproses...</span>
              <span v-else class="relative z-10 flex items-center text-gray-300">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                Lengkapi Form
              </span>
            </button>
          </form>

          <!-- Enhanced Login Link -->
          <div class="mt-4 text-center animate-fadeInUp animation-delay-200">
            <div class="bg-gray-50 rounded-lg p-3 border border-gray-200">
              <p class="text-xs text-gray-600 flex items-center justify-center">
                <svg class="w-3 h-3 mr-1.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
                </svg>
                Sudah punya akun?
                <Link
                  href="/login"
                  class="font-semibold text-indigo-600 hover:text-indigo-800 ml-1 px-2 py-1 rounded-lg hover:bg-indigo-50 transition-all duration-200 inline-flex items-center group"
                >
                  Login 
                  <svg class="w-3 h-3 ml-1 group-hover:translate-x-0.5 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                  </svg>
                </Link>
              </p>
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

    <!-- Custom Modal -->
    <Teleport to="body">
      <div v-if="showModal" class="fixed inset-0 z-[9999] flex items-center justify-center" style="z-index: 9999 !important;">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="closeModal" style="z-index: 9998 !important;"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 p-6" style="z-index: 9999 !important;">
          <!-- Modal Icon -->
          <div class="flex items-center justify-center mb-4">
            <div v-if="modalType === 'success'" class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
              </svg>
            </div>
            <div v-else-if="modalType === 'error'" class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </div>
            <div v-else-if="modalType === 'info'" class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center">
              <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
              </svg>
            </div>
          </div>

          <!-- Modal Content -->
          <div class="text-center">
            <h3 class="text-xl font-semibold text-gray-900 mb-2">
              {{ modalType === 'success' ? 'Pendaftaran Berhasil!' : modalType === 'error' ? 'Terjadi Kesalahan' : 'Informasi' }}
            </h3>
            <p class="text-gray-600 whitespace-pre-line">{{ modalMessage }}</p>
          </div>

          <!-- Modal Actions -->
          <div class="mt-6 flex justify-center space-x-3">
            <button
              v-if="modalType === 'success'"
              @click="redirectToLogin"
              class="px-6 py-2.5 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            >
              Lanjut ke Login
            </button>
            <button
              v-else
              @click="closeModal"
              class="px-6 py-2.5 bg-gray-600 text-white font-medium rounded-lg hover:bg-gray-700 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2"
            >
              Mengerti
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, reactive } from 'vue';
import { useForm, Link, usePage } from '@inertiajs/vue3';

defineProps({
  errors: {
    type: Object,
    default: () => ({})
  }
});

const page = usePage();
const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const showModal = ref(false);
const modalType = ref('success'); // 'success' or 'error'
const modalMessage = ref('');

// Safely access flash messages
const flashMessages = computed(() => {
  return page.props.flash || {};
});

// Validation status tracking
const validationStatus = reactive({
  nip: null,
  email: null,
  password: null
});

// Validation messages and suggestions
const validationMessages = reactive({
  nip: '',
  email: '',
  password: ''
});

const validationSuggestions = reactive({
  nip: [],
  email: []
});

// Password strength tracking
const passwordChecks = reactive({
  length: false,
  lowercase: false,
  uppercase: false,
  number: false,
  symbol: false,
  match: false
});

const form = useForm({
  nip: '',
  nama_lengkap: '',
  email: '',
  jabatan: '',
  unit_kerja: '',
  no_telepon: '',
  password: '',
  password_confirmation: ''
});

// Simplified form validation with backend requirements
const isFormValid = computed(() => {
  // Check all required fields have content
  const hasNIP = form.nip && form.nip.trim().length > 0;
  const hasNama = form.nama_lengkap && form.nama_lengkap.trim().length > 0;
  const hasEmail = form.email && form.email.trim().length > 0;
  const hasJabatan = form.jabatan && form.jabatan.trim().length > 0;
  const hasUnitKerja = form.unit_kerja && form.unit_kerja.trim().length > 0;
  const hasPassword = form.password && form.password.trim().length > 0;
  const hasPasswordConfirmation = form.password_confirmation && form.password_confirmation.trim().length > 0;

  // Check NIP format (18 digits)
  const nipDigits = form.nip.replace(/\D/g, '');
  const isNipValid = nipDigits.length === 18;

  // Check email format
  const emailRegex = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
  const isEmailValid = emailRegex.test(form.email.toLowerCase().trim());

  // Check simplified password requirements
  const hasMinLength = form.password.length >= 8;
  const hasLetter = /[a-zA-Z]/.test(form.password);
  const hasNumber = /\d/.test(form.password);
  const passwordsMatch = form.password === form.password_confirmation;

  const isPasswordValid = hasMinLength && hasLetter && hasNumber && passwordsMatch;

  console.log('Form validation check:', {
    hasNIP,
    hasNama,
    hasEmail,
    hasJabatan,
    hasUnitKerja,
    hasPassword,
    hasPasswordConfirmation,
    isNipValid,
    isEmailValid,
    isPasswordValid,
    passwordChecks: {
      hasMinLength,
      hasLetter,
      hasNumber,
      passwordsMatch
    }
  });

  return hasNIP && hasNama && hasEmail && hasJabatan && hasUnitKerja &&
         hasPassword && hasPasswordConfirmation &&
         isNipValid && isEmailValid && isPasswordValid;
});

// Enhanced helper function to get input class based on validation status
const getInputClass = (field) => {
  const baseClass = 'block w-full px-3 sm:px-4 py-3 sm:py-3.5 border-2 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 text-sm sm:text-base';

  if (form.errors[field]) {
    return `${baseClass} border-red-500 bg-red-50/30 focus:ring-red-500/50 focus:border-red-500`;
  }

  if (validationStatus[field] === 'valid') {
    return `${baseClass} border-green-500 bg-green-50/30 focus:ring-green-500/50 focus:border-green-500`;
  }

  if (validationStatus[field] === 'invalid') {
    return `${baseClass} border-red-500 bg-red-50/30 focus:ring-red-500/50 focus:border-red-500`;
  }

  if (validationStatus[field] === 'checking') {
    return `${baseClass} border-yellow-500 bg-yellow-50/30 focus:ring-yellow-500/50 focus:border-yellow-500`;
  }

  return `${baseClass} border-gray-300 hover:border-gray-400`;
};

// Password strength helper functions
const getPasswordStrengthPercentage = () => {
  const checkedCount = Object.values(passwordChecks).filter(Boolean).length;
  const totalCount = Object.keys(passwordChecks).length;
  return (checkedCount / totalCount) * 100;
};

const getPasswordStrengthText = () => {
  const percentage = getPasswordStrengthPercentage();
  if (percentage === 0) return 'Sangat Lemah';
  if (percentage <= 20) return 'Sangat Lemah';
  if (percentage <= 40) return 'Lemah';
  if (percentage <= 60) return 'Sedang';
  if (percentage <= 80) return 'Kuat';
  return 'Sangat Kuat';
};

const getPasswordStrengthColor = () => {
  const percentage = getPasswordStrengthPercentage();
  if (percentage <= 40) return 'text-red-600';
  if (percentage <= 60) return 'text-yellow-600';
  if (percentage <= 80) return 'text-blue-600';
  return 'text-green-600';
};

const getPasswordStrengthBarColor = () => {
  const percentage = getPasswordStrengthPercentage();
  if (percentage <= 40) return 'bg-red-500';
  if (percentage <= 60) return 'bg-yellow-500';
  if (percentage <= 80) return 'bg-blue-500';
  return 'bg-green-500';
};

// Debounce function to prevent too many API calls
const debounce = (func, wait) => {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
};

// Enhanced validation functions
const validateNamaLengkap = () => {
  form.clearErrors('nama_lengkap');
  if (!form.nama_lengkap.trim()) {
    form.setError('nama_lengkap', 'Nama lengkap wajib diisi');
  } else if (!/^[a-zA-Z\s.\',\-]+$/.test(form.nama_lengkap)) {
    form.setError('nama_lengkap', 'Nama hanya boleh berisi huruf, spasi, titik, koma, apostrof, dan tanda hubung');
  }
};

const validateJabatan = () => {
  form.clearErrors('jabatan');
  if (!form.jabatan.trim()) {
    form.setError('jabatan', 'Jabatan wajib diisi');
  } else if (!/^[a-zA-Z0-9\s.\',\-]+$/.test(form.jabatan)) {
    form.setError('jabatan', 'Jabatan hanya boleh berisi huruf, angka, spasi, titik, koma, apostrof, dan tanda hubung');
  }
};

const validateUnitKerja = () => {
  form.clearErrors('unit_kerja');
  if (!form.unit_kerja.trim()) {
    form.setError('unit_kerja', 'Unit kerja wajib diisi');
  } else if (!/^[a-zA-Z0-9\s.\',\-]+$/.test(form.unit_kerja)) {
    form.setError('unit_kerja', 'Unit kerja hanya boleh berisi huruf, angka, spasi, titik, koma, apostrof, dan tanda hubung');
  }
};

// Custom modal functions
const showSuccessModal = (message) => {
  modalType.value = 'success';
  modalMessage.value = message;
  showModal.value = true;
};

const showErrorModal = (message) => {
  modalType.value = 'error';
  modalMessage.value = message;
  showModal.value = true;
};

const closeModal = () => {
  showModal.value = false;
  if (modalType.value === 'success') {
    // Redirect to login page on successful modal close
    window.location.href = '/login';
  }
};

const redirectToLogin = () => {
  // Close modal and redirect to login page
  showModal.value = false;
  window.location.href = '/login';
};

const validateNoTelepon = () => {
  form.clearErrors('no_telepon');
  if (form.no_telepon && !/^(\+62|62|0)[0-9]{8,15}$/.test(form.no_telepon.replace(/[\s\-\(\)]/g, ''))) {
    form.setError('no_telepon', 'Format nomor telepon tidak valid. Gunakan format: 08xx, +628xx, atau 62xx');
  }
};

// Enhanced NIP validation with real-time feedback
const validateNIPBasic = () => {
  form.clearErrors('nip');
  validationStatus.nip = null;
  validationMessages.nip = '';
  validationSuggestions.nip = [];

  const nip = form.nip.replace(/\D/g, ''); // Remove non-digits

  if (!nip) {
    validationStatus.nip = 'invalid';
    validationMessages.nip = 'NIP wajib diisi';
    return false;
  }

  if (nip.length < 18) {
    validationStatus.nip = 'invalid';
    validationMessages.nip = `NIP harus 18 digit (${nip.length}/18)`;
    return false;
  }

  if (nip.length > 18) {
    validationStatus.nip = 'invalid';
    validationMessages.nip = 'NIP melebihi 18 digit';
    return false;
  }

  if (!/^[0-9]+$/.test(nip)) {
    validationStatus.nip = 'invalid';
    validationMessages.nip = 'NIP hanya boleh berisi angka';
    return false;
  }

  validationMessages.nip = 'Format NIP valid';
  return true;
};

// Enhanced email validation with real-time feedback
const validateEmailBasic = () => {
  form.clearErrors('email');
  validationStatus.email = null;
  validationMessages.email = '';
  validationSuggestions.email = [];

  const email = form.email.toLowerCase().trim();

  if (!email) {
    validationStatus.email = 'invalid';
    validationMessages.email = 'Email wajib diisi';
    return false;
  }

  if (!/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email)) {
    validationStatus.email = 'invalid';
    validationMessages.email = 'Format email tidak valid';
    validationSuggestions.email = [
      'Periksa format email yang dimasukkan',
      'Gunakan email yang valid (contoh: nama@domain.com)'
    ];
    return false;
  }

  validationMessages.email = 'Format email valid';
  return true;
};

// Real-time NIP validation with API check
const validateNIP = debounce(async () => {
  if (!validateNIPBasic()) return;

  if (form.nip.replace(/\D/g, '').length === 18) {
    validationStatus.nip = 'checking';

    try {
      const response = await fetch('/check-nip', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ nip: form.nip })
      });

      const data = await response.json();

      if (data.valid && data.available) {
        validationStatus.nip = 'valid';
        validationMessages.nip = data.message;
        validationSuggestions.nip = [];
      } else {
        validationStatus.nip = 'invalid';
        validationMessages.nip = data.message;
        validationSuggestions.nip = data.suggestions;
        form.setError('nip', data.message);
      }
    } catch (error) {
      console.error('NIP validation error:', error);
      validationStatus.nip = 'invalid';
      validationMessages.nip = 'Terjadi kesalahan saat memvalidasi NIP';
    }
  }
}, 500);

// Real-time email validation with API check
const validateEmail = debounce(async () => {
  if (!validateEmailBasic()) return;

  if (form.email && form.email.includes('@')) {
    validationStatus.email = 'checking';

    try {
      const response = await fetch('/check-email', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ email: form.email })
      });

      const data = await response.json();

      if (data.valid && data.available) {
        validationStatus.email = 'valid';
        validationMessages.email = data.message;
        validationSuggestions.email = [];
      } else {
        validationStatus.email = 'invalid';
        validationMessages.email = data.message;
        validationSuggestions.email = data.suggestions;
        form.setError('email', data.message);
      }
    } catch (error) {
      console.error('Email validation error:', error);
      validationStatus.email = 'invalid';
      validationMessages.email = 'Terjadi kesalahan saat memvalidasi email';
    }
  }
}, 500);

// Enhanced password validation with real-time strength check
const validatePasswordWithStrength = debounce(async () => {
  const password = form.password;
  const confirmation = form.password_confirmation;

  form.clearErrors('password');
  validationStatus.password = null;
  validationMessages.password = '';

  if (!password) {
    validationStatus.password = 'invalid';
    validationMessages.password = 'Password wajib diisi';
    updatePasswordChecks(password, confirmation);
    return false;
  }

  validationStatus.password = 'checking';

  try {
    const response = await fetch('/check-password', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
      },
      body: JSON.stringify({
        password: password,
        password_confirmation: confirmation
      })
    });

    const data = await response.json();

    if (data.valid) {
      validationStatus.password = 'valid';
      validationMessages.password = data.message;
      updatePasswordChecks(password, confirmation, data.checks);
    } else {
      validationStatus.password = 'invalid';
      validationMessages.password = data.message;
      updatePasswordChecks(password, confirmation, data.checks);
      form.setError('password', data.message);
    }
  } catch (error) {
    console.error('Password validation error:', error);
    validationStatus.password = 'invalid';
    validationMessages.password = 'Terjadi kesalahan saat memvalidasi password';
    updatePasswordChecks(password, confirmation);
  }
}, 300);

// Update password checks
const updatePasswordChecks = (password, confirmation, checks = null) => {
  if (checks) {
    Object.assign(passwordChecks, checks);
  } else {
    passwordChecks.length = password.length >= 8;
    passwordChecks.lowercase = /[a-z]/.test(password);
    passwordChecks.uppercase = /[A-Z]/.test(password);
    passwordChecks.number = /\d/.test(password);
    passwordChecks.symbol = /[^a-zA-Z0-9]/.test(password);;
    passwordChecks.match = confirmation && password === confirmation;
  }
};

const validatePasswordConfirmation = () => {
  form.clearErrors('password_confirmation');

  if (!form.password_confirmation) {
    form.setError('password_confirmation', 'Konfirmasi password wajib diisi');
    passwordChecks.match = false;
    return false;
  }

  if (form.password !== form.password_confirmation) {
    form.setError('password_confirmation', 'Konfirmasi password tidak cocok');
    passwordChecks.match = false;
    return false;
  }

  passwordChecks.match = true;

  // Update password checks when confirmation is valid
  updatePasswordChecks(form.password, form.password_confirmation);
  return true;
};

const validatePasswordBasic = () => {
  validatePasswordWithStrength();
};

// Comprehensive form validation
const validateAllFields = () => {
  let isValid = true;

  // Validate each field
  if (!validateNIPBasic()) isValid = false;
  if (!validateEmailBasic()) isValid = false;
  if (!validatePasswordWithStrength()) isValid = false;
  if (!validatePasswordConfirmation()) isValid = false;

  // Validate other required fields
  if (!form.nama_lengkap.trim()) {
    form.setError('nama_lengkap', 'Nama lengkap wajib diisi');
    isValid = false;
  } else {
    validateNamaLengkap();
  }

  if (!form.jabatan.trim()) {
    form.setError('jabatan', 'Jabatan wajib diisi');
    isValid = false;
  } else {
    validateJabatan();
  }

  if (!form.unit_kerja.trim()) {
    form.setError('unit_kerja', 'Unit kerja wajib diisi');
    isValid = false;
  } else {
    validateUnitKerja();
  }

  // Validate phone number format if provided
  if (form.no_telepon) {
    validateNoTelepon();
  }

  return isValid;
};

const submit = () => {
  console.log('Submit button clicked!');

  // Clear any previous errors
  form.clearErrors();

  console.log('Form data being submitted:', {
    nip: form.nip,
    nama_lengkap: form.nama_lengkap,
    email: form.email,
    jabatan: form.jabatan,
    unit_kerja: form.unit_kerja,
    no_telepon: form.no_telepon,
    password_length: form.password.length,
    password_confirmation_length: form.password_confirmation.length,
    passwords_match: form.password === form.password_confirmation
  });

  // Submit the form directly - let backend handle validation
  form.post('/register', {
    onSuccess: (page) => {
      console.log('Registration successful!', page.props.flash);

      // Clear form data on success
      form.reset();
      // Reset validation states
      Object.keys(validationStatus).forEach(key => {
        validationStatus[key] = null;
      });
      Object.keys(validationMessages).forEach(key => {
        validationMessages[key] = '';
      });
      Object.keys(passwordChecks).forEach(key => {
        passwordChecks[key] = false;
      });

      // Show success modal
      showSuccessModal('Registrasi berhasil! Akun Anda telah dibuat. Silakan login dengan NIP dan password yang telah Anda daftarkan.');
    },
    onError: (errors) => {
      console.log('Registration failed with errors:', errors);
      // Don't clear form on error, let user fix the issues

      // Show error modal with formatted message
      const errorMessage = Object.entries(errors)
        .map(([field, message]) => `${field}: ${message}`)
        .join('\n');
      showErrorModal('Registrasi gagal:\n\n' + errorMessage);
    },
    onFinish: () => {
      console.log('Request finished');
      // Only clear passwords if there are errors (for security)
      if (form.hasErrors) {
        form.password = '';
        form.password_confirmation = '';
        // Reset password checks
        Object.keys(passwordChecks).forEach(key => {
          passwordChecks[key] = false;
        });
        validationStatus.password = null;
        validationMessages.password = '';
      }
    }
  });
};
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

/* Glass morphism effect enhancement */
.bg-white\/95 {
  background: rgba(255, 255, 255, 0.95);
  backdrop-filter: blur(20px);
  -webkit-backdrop-filter: blur(20px);
}

/* Enhanced responsive improvements */
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
  .space-y-4 > * + * {
    margin-top: 0.75rem;
  }

  /* More compact mobile layout */
  .max-w-md {
    max-width: 20rem; /* 320px */
  }
}

@media (max-width: 380px) {
  /* Extra small screens */
  .px-6, .px-4, .px-3 {
    padding-left: 0.75rem;
    padding-right: 0.75rem;
  }

  .py-8, .py-4, .py-2 {
    padding-top: 1rem;
    padding-bottom: 1rem;
  }

  .max-w-md {
    max-width: 18rem; /* 288px */
  }

  /* Ultra compact spacing */
  .space-y-4 > * + * {
    margin-top: 0.5rem;
  }

  .mt-4 {
    margin-top: 0.75rem;
  }

  .mb-4 {
    margin-bottom: 0.75rem;
  }

  .mt-6 {
    margin-top: 1rem;
  }

  .mb-6 {
    margin-bottom: 1rem;
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

/* Focus management for accessibility */
input:focus-visible,
button:focus-visible {
  outline: 2px solid #4F46E5;
  outline-offset: 2px;
}

/* Custom scrollbar for better aesthetics */
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
</style>

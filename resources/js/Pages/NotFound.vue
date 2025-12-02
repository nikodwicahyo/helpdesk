<template>
  <AppLayout title="Halaman Tidak Ditemukan">
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        404 - Halaman Tidak Ditemukan
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 bg-white border-b border-gray-200 text-center">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Maaf, halaman tidak ditemukan</h3>
            <p class="text-gray-600 mb-6">Halaman yang Anda cari mungkin telah dipindahkan atau dihapus.</p>
            <Link 
              :href="route('dashboard')" 
              class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-white hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150"
            >
              Kembali ke Dashboard
            </Link>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
</script>
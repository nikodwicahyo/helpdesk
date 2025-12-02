<template>
    <nav class="bg-white shadow-md border-b border-gray-200 sticky top-0 z-40">
        <div class="mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <!-- Left Side: Logo and Title -->
                <div class="flex items-center">
                    <button
                        v-if="showMenuButton"
                        @click="$emit('toggle-sidebar')"
                        class="mr-3 sm:mr-4 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200 lg:hidden"
                    >
                        <svg
                            class="w-5 h-5 sm:w-6 sm:h-6"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M4 6h16M4 12h16M4 18h16"
                            />
                        </svg>
                    </button>

                    <Link href="/" class="flex items-center space-x-3">
                        <div
                            class="w-10 h-10 flex items-center justify-center"
                        >
                            <img src="/logo.png" :alt="systemName" class="w-10 h-10">
                        </div>
                        <div class="hidden sm:block">
                            <h1 class="text-xl font-bold text-gray-900">
                                {{ systemName }}
                            </h1>
                            <p class="text-xs text-gray-500">{{ roleLabel }}</p>
                        </div>
                    </Link>
                </div>

                <!-- Right Side: Search, Language, Notifications, Profile -->
                <div class="flex items-center space-x-2 sm:space-x-4">
                    <!-- Search Bar (Desktop) -->
                    <div v-if="showSearch && role !== 'admin_aplikasi'" class="hidden md:block">
                        <div class="relative">
                            <input
                                v-model="searchQuery"
                                type="text"
                                :placeholder="t('search.searchTickets')"
                                class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                @keyup.enter="handleSearch"
                            />
                            <svg
                                class="absolute left-3 top-2.5 w-5 h-5 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                                />
                            </svg>
                        </div>
                    </div>

                    <!-- Language Switcher -->
                    <LanguageSwitcher />
                    
                    <!-- Notification Bell -->
                    <NotificationBell
                        :notifications="notifications"
                        :unread-count="unreadCount"
                        @mark-read="handleMarkRead"
                        @view-all="handleViewAll"
                        @notification-clicked="handleNotificationClicked"
                    />

                    <!-- Profile Dropdown -->
                    <div class="relative" ref="profileDropdown">
                        <button
                            @click="showProfileMenu = !showProfileMenu"
                            class="flex items-center space-x-3 focus:outline-none"
                        >
                            <div class="hidden md:block text-right">
                                <p class="text-sm font-medium text-gray-900">
                                    {{ user?.name || "User" }}
                                </p>
                                <p class="text-xs text-gray-500">
                                    {{ user?.nip || "" }}
                                </p>
                            </div>
                            <UserInitials
                                :user="user"
                                size="md"
                            />
                            <svg
                                class="w-4 h-4 text-gray-600"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"
                                />
                            </svg>
                        </button>

                        <!-- Dropdown Menu -->
                        <Transition name="dropdown">
                            <div
                                v-if="showProfileMenu && user"
                                class="absolute right-0 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-200 py-2"
                            >
                                <div class="px-4 py-3 border-b border-gray-200">
                                    <p
                                        class="text-sm font-medium text-gray-900"
                                    >
                                        {{ user?.name }}
                                    </p>
                                    <p class="text-xs text-gray-500">
                                        {{ user?.email }}
                                    </p>
                                </div>

                                <button
                                    @click="handleProfileClick"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition w-full text-left"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                                        />
                                    </svg>
                                    {{ t('nav.myProfile') }}
                                </button>

                                <Link
                                    v-if="role === 'admin_helpdesk'"
                                    href="/admin/system-settings"
                                    class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3 text-gray-400"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                        />
                                    </svg>
                                    {{ t('nav.settings') }}
                                </Link>

                                <div
                                    class="border-t border-gray-200 my-2"
                                ></div>

                                <button
                                    @click="handleLogout"
                                    class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition"
                                >
                                    <svg
                                        class="w-5 h-5 mr-3"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
                                        />
                                    </svg>
                                    {{ t('nav.logout') }}
                                </button>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";
import { Link, router, usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import NotificationBell from "./NotificationBell.vue";
import UserInitials from "@/Components/UI/UserInitials.vue";
import LanguageSwitcher from "@/Components/LanguageSwitcher.vue";
import { useAuth } from "@/composables/useAuth.js";

const page = usePage();
const { t } = useI18n();

const props = defineProps({
    user: {
        type: Object,
        default: null,
    },
    role: {
        type: String,
        required: true,
    },
    notifications: {
        type: Array,
        default: () => [],
    },
    unreadCount: {
        type: Number,
        default: 0,
    },
    showSearch: {
        type: Boolean,
        default: true,
    },
    showMenuButton: {
        type: Boolean,
        default: true,
    },
});

// Convert role to URL prefix
const getRolePrefix = (role) => {
    const roleMap = {
        'admin_helpdesk': 'admin',
        'admin_aplikasi': 'admin-aplikasi',
        'teknisi': 'teknisi',
        'user': 'user'
    };

    // Debug logging
    console.log('Navbar - Role:', role);
    console.log('Navbar - Role Map Result:', roleMap[role]);

    const prefix = roleMap[role] || 'user';
    console.log('Navbar - Final Role Prefix:', prefix);

    return prefix;
};

const rolePrefix = computed(() => getRolePrefix(props.role));

defineEmits(["toggle-sidebar"]);

const searchQuery = ref("");
const showProfileMenu = ref(false);
const profileDropdown = ref(null);

// Get system name from global settings
const systemName = computed(() => {
    return page.props.systemSettings?.system_name || 'HelpDesk Kemlu';
});

const roleLabel = computed(() => {
    const labels = {
        user: t('dashboard.userDashboard'),
        "admin_helpdesk": t('dashboard.adminHelpdeskDashboard'),
        "admin_aplikasi": t('dashboard.adminAplikasiDashboard'),
        teknisi: t('dashboard.teknisiDashboard'),
    };
    return labels[props.role] || t('nav.dashboard');
});


const handleSearch = () => {
    if (searchQuery.value.trim()) {
        // Use rolePrefix to get the correct URL path (e.g., 'admin' for 'admin_helpdesk')
        const prefix = rolePrefix.value;
        
        // Admin Aplikasi doesn't have a tickets route
        if (props.role === 'admin_aplikasi') {
            return;
        }

        router.visit(`/${prefix}/tickets?search=${searchQuery.value}`);
    }
};

const handleProfileClick = () => {
    // Only navigate to profile if user is authenticated
    if (props.user) {
        const profileUrl = `/${rolePrefix.value}/profile`;
        console.log('Profile URL:', profileUrl);
        console.log('Current role:', props.role);
        console.log('Computed rolePrefix:', rolePrefix.value);

        // Use Inertia router for navigation
        router.visit(profileUrl);
    } else {
        console.log('Cannot navigate to profile - user not authenticated');
    }
};

const handleMarkRead = (notificationId) => {
    router.post(
        `/notifications/${notificationId}/mark-read`,
        {},
        {
            preserveScroll: true,
        }
    );
};

const handleViewAll = () => {
    // Map role to correct notification route path
    const notificationRoutes = {
        'admin_helpdesk': '/admin/notifications',
        'admin_aplikasi': '/admin-aplikasi/notifications',
        'teknisi': '/teknisi/notifications',
        'user': '/user/notifications'
    };

    const routePath = notificationRoutes[props.role] || '/notifications';
    router.visit(routePath);
};

const handleNotificationClicked = (notificationId) => {
    // Emit event to parent to open notification modal
    // We'll use a global event or emit to parent layout
    const event = new CustomEvent('open-notification-modal', { detail: { notificationId } });
    window.dispatchEvent(event);
};

const { logout } = useAuth();

const handleLogout = async () => {
    if (confirm(t('message.confirmLogout'))) {
        await logout();
    }
};

const handleClickOutside = (event) => {
    if (
        profileDropdown.value &&
        !profileDropdown.value.contains(event.target)
    ) {
        showProfileMenu.value = false;
    }
};

onMounted(() => {
    document.addEventListener("click", handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener("click", handleClickOutside);
});
</script>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition: all 0.2s ease;
}

.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-10px);
}
</style>
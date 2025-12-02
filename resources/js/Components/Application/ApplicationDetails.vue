<template>
    <div class="space-y-6">
        <!-- Basic Information -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Application Information</h4>
                <dl class="space-y-2">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">Name:</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ application.name }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">Code:</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ application.code }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">Version:</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ application.version || 'N/A' }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">Status:</dt>
                        <dd>
                            <span :class="getStatusBadgeClass(application.status)">
                                {{ application.status }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-600">Created:</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ application.formatted_created_at }}</dd>
                    </div>
                </dl>
            </div>

            <div class="bg-gray-50 rounded-lg p-4">
                <h4 class="text-sm font-semibold text-gray-900 mb-3">Administrative Information</h4>
                <dl class="space-y-2">
                    <div>
                        <dt class="text-sm text-gray-600">Admin Aplikasi:</dt>
                        <dd v-if="application.admin_aplikasi" class="text-sm font-medium text-gray-900">
                            {{ application.admin_aplikasi.name }} ({{ application.admin_aplikasi.nip }})
                        </dd>
                        <dd v-else class="text-sm text-gray-400">Not assigned</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-gray-600">Backup Admin:</dt>
                        <dd v-if="application.backup_admin" class="text-sm font-medium text-gray-900">
                            {{ application.backup_admin.name }} ({{ application.backup_admin.nip }})
                        </dd>
                        <dd v-else class="text-sm text-gray-400">Not assigned</dd>
                    </div>
                    <div v-if="application.admin_aplikasi" class="flex justify-between">
                        <dt class="text-sm text-gray-600">Admin Email:</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ application.admin_aplikasi.email }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Ticket Statistics -->
        <div v-if="ticketStats" class="bg-blue-50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Ticket Statistics</h4>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ ticketStats.total_tickets }}</div>
                    <div class="text-xs text-gray-600">Total Tickets</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600">{{ ticketStats.open_tickets }}</div>
                    <div class="text-xs text-gray-600">Open</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600">{{ ticketStats.in_progress_tickets }}</div>
                    <div class="text-xs text-gray-600">In Progress</div>
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600">{{ ticketStats.resolved_tickets }}</div>
                    <div class="text-xs text-gray-600">Resolved</div>
                </div>
            </div>

            <div v-if="ticketStats.avg_resolution_time_hours" class="mt-4 pt-4 border-t border-blue-200">
                <div class="flex justify-between items-center">
                    <span class="text-sm text-gray-600">Average Resolution Time:</span>
                    <span class="text-sm font-medium text-gray-900">{{ ticketStats.avg_resolution_time_hours }} hours</span>
                </div>
            </div>
        </div>

        <!-- Description -->
        <div v-if="application.description" class="bg-gray-50 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-gray-900 mb-3">Description</h4>
            <p class="text-sm text-gray-700">{{ application.description }}</p>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    application: {
        type: Object,
        required: true,
    },
    ticketStats: {
        type: Object,
        default: null,
    },
});

const getStatusBadgeClass = (status) => {
    const classes = {
        active: 'px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800',
        inactive: 'px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800',
        maintenance: 'px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800',
        deprecated: 'px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800',
    };
    return classes[status] || classes.inactive;
};
</script>
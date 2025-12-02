<template>
  <div class="p-6 hover:bg-gray-50 cursor-pointer transition-colors duration-150" @click="$emit('view-ticket', ticket.id)">
    <div class="flex items-start justify-between">
      <!-- Main Content -->
      <div class="flex-1 min-w-0">
        <!-- Ticket Header -->
        <div class="flex items-center space-x-3 mb-2">
          <span class="text-sm font-mono text-gray-500">{{ ticket.ticket_number }}</span>
          <span
            :class="`px-2 py-1 text-xs font-medium rounded-full bg-${ticket.priority_badge_color}-100 text-${ticket.priority_badge_color}-800`"
          >
            {{ ticket.priority_label }}
          </span>
          <span
            :class="`px-2 py-1 text-xs font-medium rounded-full bg-${ticket.status_badge_color}-100 text-${ticket.status_badge_color}-800`"
          >
            {{ ticket.status_label }}
          </span>
          <span v-if="ticket.is_escalated" class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
            <ExclamationTriangleIcon class="h-3 w-3 inline mr-1" />
            Escalated
          </span>
          <span v-if="ticket.is_overdue" class="px-2 py-1 text-xs font-medium rounded-full bg-orange-100 text-orange-800">
            <ClockIcon class="h-3 w-3 inline mr-1" />
            Overdue
          </span>
        </div>

        <!-- Title -->
        <h3 class="text-lg font-medium text-gray-900 mb-2" v-html="highlightText(ticket.title)"></h3>

        <!-- Description Preview -->
        <p class="text-sm text-gray-600 mb-3 line-clamp-2" v-html="highlightText(ticket.description)"></p>

        <!-- Metadata -->
        <div class="flex flex-wrap items-center gap-4 text-xs text-gray-500">
          <div class="flex items-center">
            <UserIcon class="h-4 w-4 mr-1" />
            <span v-if="ticket.user">{{ ticket.user.name }}</span>
            <span v-else>Unknown User</span>
          </div>
          <div class="flex items-center" v-if="ticket.aplikasi">
            <ComputerDesktopIcon class="h-4 w-4 mr-1" />
            {{ ticket.aplikasi.name }}
          </div>
          <div class="flex items-center" v-if="ticket.kategori_masalah">
            <TagIcon class="h-4 w-4 mr-1" />
            {{ ticket.kategori_masalah.nama_kategori }}
          </div>
          <div class="flex items-center" v-if="ticket.assigned_teknisi">
            <WrenchScrewdriverIcon class="h-4 w-4 mr-1" />
            {{ ticket.assigned_teknisi.name }}
          </div>
          <div class="flex items-center">
            <CalendarIcon class="h-4 w-4 mr-1" />
            Created {{ formatTimeAgo(ticket.created_at) }}
          </div>
          <div class="flex items-center" v-if="ticket.due_date !== 'No due date'">
            <CalendarDaysIcon class="h-4 w-4 mr-1" />
            Due {{ formatDate(ticket.due_date) }}
          </div>
          <div class="flex items-center" v-if="ticket.attachments_count > 0">
            <PaperClipIcon class="h-4 w-4 mr-1" />
            {{ ticket.attachments_count }} attachment{{ ticket.attachments_count > 1 ? 's' : '' }}
          </div>
          <div class="flex items-center">
            <EyeIcon class="h-4 w-4 mr-1" />
            {{ ticket.view_count }} views
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="flex flex-col items-end space-y-2 ml-4">
        <div class="flex items-center space-x-1">
          <!-- SLA Status Indicator -->
          <div class="flex items-center">
            <div
              :class="[
                'w-2 h-2 rounded-full mr-1',
                ticket.sla_status === 'within_sla' ? 'bg-green-500' : 'bg-red-500'
              ]"
            ></div>
            <span class="text-xs text-gray-500">
              {{ ticket.sla_status === 'within_sla' ? 'Within SLA' : 'SLA Breached' }}
            </span>
          </div>
        </div>

        <ChevronRightIcon class="h-5 w-5 text-gray-400" />
      </div>
    </div>

    <!-- Quick Actions (visible on hover) -->
    <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
      <div class="flex items-center space-x-2">
        <span class="text-xs text-gray-500">Last updated {{ formatTimeAgo(ticket.updated_at) }}</span>
      </div>
      <div class="flex items-center space-x-2">
        <button
          @click.stop="$emit('view-ticket', ticket.id)"
          class="inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
        >
          <EyeIcon class="h-3 w-3 mr-1" />
          View Details
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import {
  UserIcon,
  ComputerDesktopIcon,
  TagIcon,
  WrenchScrewdriverIcon,
  CalendarIcon,
  CalendarDaysIcon,
  PaperClipIcon,
  EyeIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  ChevronRightIcon
} from '@heroicons/vue/24/outline'
import { useDateFormatter } from '@/composables/useDateFormatter'

const props = defineProps({
  ticket: {
    type: Object,
    required: true
  },
  searchQuery: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['view-ticket'])

// Date formatter
const { formatTimeAgo, formatDate } = useDateFormatter()

// Highlight search terms in text
const highlightText = (text) => {
  if (!props.searchQuery || props.searchQuery.length < 2) {
    return text
  }

  const regex = new RegExp(`(${escapeRegExp(props.searchQuery)})`, 'gi')
  return text.replace(regex, '<mark class="bg-yellow-200 px-1 rounded">$1</mark>')
}

// Escape special regex characters
const escapeRegExp = (string) => {
  return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')
}
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

:deep(mark) {
  background-color: #fef08a;
  padding: 1px 2px;
  border-radius: 2px;
  font-weight: 500;
}
</style>
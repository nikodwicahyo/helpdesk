<template>
    <AppLayout role="user" :breadcrumbs="breadcrumbs">
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center space-x-3">
                        <h1 class="text-3xl font-bold text-gray-900">
                            {{ ticket.ticket_number }}
                        </h1>
                        <span
                            :class="[
                                'px-3 py-1 text-sm font-medium rounded-full',
                                getStatusColor(ticket.status),
                            ]"
                        >
                            {{ ticket.status_label }}
                        </span>
                        <span
                            :class="[
                                'px-3 py-1 text-sm font-medium rounded-full',
                                getPriorityColor(ticket.priority),
                            ]"
                        >
                            {{ ticket.priority_label }}
                        </span>
                    </div>
                    <p class="text-gray-600 mt-1">{{ ticket.title }}</p>
                </div>
                <div class="flex items-center space-x-4">
                    <Link
                        :href="route('user.tickets.index')"
                        class="text-indigo-600 hover:text-indigo-800 font-medium"
                    >
                        ← {{ t("common.back") }} {{ t("nav.tickets") }}
                    </Link>
                    <!-- Edit Button - Only show if ticket is editable -->
                    <Link
                        v-if="canEditTicket"
                        :href="route('user.tickets.edit', ticket.id)"
                        class="bg-indigo-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-indigo-700 transition flex items-center"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Edit Ticket
                    </Link>
                    <button
                        v-if="ticket.status === 'resolved' && !ticket.rating"
                        @click="showRatingModal = true"
                        class="bg-green-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-green-700 transition"
                    >
                        {{ t("ticket.rateTicket") }} &
                        {{ t("ticket.closeTicket") }}
                    </button>
                    <button
                        v-else-if="
                            ticket.status === 'resolved' && ticket.rating
                        "
                        @click="closeTicket"
                        :disabled="isClosingTicket"
                        class="bg-gray-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-gray-700 transition disabled:opacity-50"
                    >
                        {{ t("ticket.closeTicket") }}
                    </button>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Ticket Details -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            Ticket Details
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="prose max-w-none">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                {{ ticket.title }}
                            </h3>
                            <div class="text-gray-700 whitespace-pre-wrap">
                                {{ ticket.description }}
                            </div>
                        </div>

                        <!-- Attachments -->
                        <div
                            v-if="
                                ticket.attachments &&
                                ticket.attachments.length > 0
                            "
                            class="mt-6"
                        >
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-md font-medium text-gray-900">
                                    {{ t("ticket.attachments") }}
                                </h4>
                                <a
                                    :href="
                                        route(
                                            'user.tickets.download-all',
                                            ticket.id
                                        )
                                    "
                                    class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition"
                                >
                                    <svg
                                        class="w-4 h-4 mr-1.5"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            stroke-width="2"
                                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                                        />
                                    </svg>
                                    Download All
                                </a>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div
                                    v-for="(attachment, index) in ticket.attachments"
                                    :key="attachment.name"
                                    class="flex items-center p-3 border border-gray-200 rounded-lg hover:border-indigo-300 transition"
                                    :class="isImage(attachment.name) ? 'cursor-pointer' : ''"
                                    @click="isImage(attachment.name) && openImagePreview(imageAttachments.findIndex(img => img.name === attachment.name))"
                                >
                                    <div class="flex-shrink-0 mr-3">
                                        <svg
                                            v-if="isImage(attachment.name)"
                                            class="w-8 h-8 text-blue-500"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="w-8 h-8 text-gray-500"
                                            fill="currentColor"
                                            viewBox="0 0 20 20"
                                        >
                                            <path
                                                fill-rule="evenodd"
                                                d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z"
                                                clip-rule="evenodd"
                                            />
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p
                                            class="text-sm font-medium text-gray-900 truncate"
                                        >
                                            {{ attachment.name }}
                                            <span
                                                v-if="isImage(attachment.name)"
                                                class="text-xs text-indigo-600 ml-1"
                                            >
                                                (Click to preview)
                                            </span>
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{
                                                formatFileSize(attachment.size)
                                            }}
                                        </p>
                                    </div>
                                    <a
                                        :href="
                                            route('user.tickets.download', {
                                                ticket: ticket.id,
                                                filename: attachment.name,
                                            })
                                        "
                                        class="ml-3 text-indigo-600 hover:text-indigo-800"
                                        title="Download"
                                        @click.stop
                                    >
                                        <svg
                                            class="w-5 h-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                            />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Comments Section -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{ t("ticket.comments") }} & Updates
                        </h2>
                    </div>

                    <!-- Comments List -->
                    <div class="divide-y divide-gray-200">
                        <div
                            v-for="comment in ticket.comments"
                            :key="comment.id"
                            class="p-6"
                        >
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div
                                        class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center"
                                    >
                                        <span
                                            class="text-indigo-600 font-semibold text-sm"
                                        >
                                            {{
                                                getInitials(
                                                    comment.commenter_name
                                                )
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1">
                                    <div
                                        class="flex items-center space-x-2 mb-2"
                                    >
                                        <p class="font-medium text-gray-900">
                                            {{ comment.commenter_name }}
                                        </p>
                                        <span
                                            :class="[
                                                'px-2 py-1 text-xs font-medium rounded-full',
                                                getRoleColor(
                                                    comment.commenter_type
                                                ),
                                            ]"
                                        >
                                            {{
                                                getRoleLabel(
                                                    comment.commenter_type
                                                )
                                            }}
                                        </span>
                                        <span class="text-sm text-gray-500">{{
                                            comment.formatted_created_at
                                        }}</span>
                                    </div>
                                    <div
                                        v-if="editingComment === comment.id"
                                        class="mt-2"
                                    >
                                        <textarea
                                            v-model="editForm.comment"
                                            rows="3"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        ></textarea>
                                        <div
                                            class="flex justify-end space-x-2 mt-2"
                                        >
                                            <button
                                                @click="cancelEdit"
                                                class="px-3 py-1 text-xs text-gray-600 hover:bg-gray-100 rounded"
                                            >
                                                {{ t("common.cancel") }}
                                            </button>
                                            <button
                                                @click="
                                                    updateComment(comment.id)
                                                "
                                                :disabled="editForm.processing"
                                                class="px-3 py-1 text-xs bg-indigo-600 text-white rounded hover:bg-indigo-700 disabled:opacity-50"
                                            >
                                                {{ t("common.save") }}
                                            </button>
                                        </div>
                                    </div>
                                    <div
                                        v-else
                                        class="text-gray-700 whitespace-pre-wrap"
                                    >
                                        {{ comment.comment }}
                                    </div>

                                    <!-- Comment Actions -->
                                    <div
                                        v-if="
                                            comment.user.nip ===
                                                $page.props.auth.user.nip &&
                                            editingComment !== comment.id
                                        "
                                        class="flex space-x-3 mt-2 text-xs"
                                    >
                                        <button
                                            @click="startEdit(comment)"
                                            class="text-indigo-600 hover:text-indigo-800 font-medium"
                                        >
                                            {{ t("common.edit") }}
                                        </button>
                                        <button
                                            @click="deleteComment(comment.id)"
                                            class="text-red-600 hover:text-red-800 font-medium"
                                        >
                                            {{ t("common.delete") }}
                                        </button>
                                    </div>

                                    <!-- Comment Attachments -->
                                    <div
                                        v-if="
                                            comment.attachments &&
                                            comment.attachments.length > 0
                                        "
                                        class="mt-3"
                                    >
                                        <div class="flex flex-wrap gap-2">
                                            <div
                                                v-for="attachment in comment.attachments"
                                                :key="attachment.name"
                                                class="flex items-center p-2 bg-gray-50 rounded border text-xs"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-gray-500 mr-1"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M8 4a3 3 0 00-3 3v4a5 5 0 0010 0V7a1 1 0 112 0v4a7 7 0 11-14 0V7a5 5 0 0110 0v4a3 3 0 11-6 0V7a1 1 0 012 0v4a1 1 0 102 0V7a3 3 0 00-3-3z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                                <span
                                                    class="truncate max-w-32"
                                                    >{{ attachment.name }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Comment Form -->
                    <div
                        v-if="ticket.status !== 'closed'"
                        class="p-6 border-t border-gray-200 bg-gray-50"
                    >
                        <form @submit.prevent="addComment">
                            <div class="space-y-4">
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 mb-2"
                                        >{{ t("common.addComment") }}</label
                                    >
                                    <textarea
                                        v-model="commentForm.comment"
                                        rows="4"
                                        required
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                        placeholder="Add additional information, ask questions, or provide updates..."
                                    ></textarea>
                                </div>

                                <div>
                                    <FileUpload
                                        v-model="commentForm.attachments"
                                        label="Attach Files (Optional)"
                                        :multiple="true"
                                        accept="image/*,.pdf,.doc,.docx,.txt,.log"
                                        :max-size="5 * 1024 * 1024"
                                        :max-files="3"
                                    />
                                </div>

                                <div class="flex justify-end">
                                    <button
                                        type="submit"
                                        :disabled="
                                            commentForm.processing ||
                                            isLoadingComment ||
                                            !commentForm.comment.trim()
                                        "
                                        class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-medium hover:bg-indigo-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                                    >
                                        <svg
                                            v-if="commentForm.processing || isLoadingComment"
                                            class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                                            fill="none"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span v-if="!commentForm.processing && !isLoadingComment">{{
                                            t("common.addComment")
                                        }}</span>
                                        <span v-else
                                            >{{ t("common.adding") }}...</span
                                        >
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Ticket Information -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t("ticket.ticketInformation") }}
                        </h2>
                    </div>

                    <div class="p-6 space-y-4">
                        <div>
                            <label class="text-sm font-medium text-gray-500">{{
                                t("ticket.application")
                            }}</label>
                            <p class="text-sm text-gray-900 mt-1">
                                {{ ticket.aplikasi?.name || "N/A" }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">{{
                                t("ticket.category")
                            }}</label>
                            <p class="text-sm text-gray-900 mt-1">
                                {{
                                    ticket.kategori_masalah?.name ||
                                    "N/A"
                                }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">{{
                                t("ticket.priority")
                            }}</label>
                            <p class="text-sm text-gray-900 mt-1">
                                {{ ticket.priority_label }}
                            </p>
                        </div>

                        <div v-if="ticket.lokasi">
                            <label class="text-sm font-medium text-gray-500">{{
                                t("ticket.location")
                            }}</label>
                            <p class="text-sm text-gray-900 mt-1">
                                {{ ticket.lokasi }}
                            </p>
                        </div>

                        <div>
                            <label class="text-sm font-medium text-gray-500">{{
                                t("ticket.createdAt")
                            }}</label>
                            <p class="text-sm text-gray-900 mt-1">
                                {{ ticket.formatted_created_at }}
                            </p>
                        </div>

                        <div v-if="ticket.assigned_teknisi">
                            <label class="text-sm font-medium text-gray-500">{{
                                t("ticket.assignedTo")
                            }}</label>
                            <div class="flex items-center space-x-2 mt-1">
                                <div
                                    class="w-6 h-6 bg-indigo-100 rounded-full flex items-center justify-center"
                                >
                                    <span
                                        class="text-indigo-600 font-semibold text-xs"
                                    >
                                        {{
                                            getInitials(
                                                ticket.assigned_teknisi.name
                                            )
                                        }}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-900">
                                    {{ ticket.assigned_teknisi.name }}
                                </p>
                            </div>
                        </div>

                        <div v-if="ticket.resolved_at">
                            <label class="text-sm font-medium text-gray-500"
                                >Resolved</label
                            >
                            <p class="text-sm text-gray-900 mt-1">
                                {{ ticket.formatted_resolved_at }}
                            </p>
                        </div>

                        <div v-if="ticket.closed_at">
                            <label class="text-sm font-medium text-gray-500"
                                >Closed</label
                            >
                            <p class="text-sm text-gray-900 mt-1">
                                {{ ticket.formatted_closed_at }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Ticket Timeline -->
                <div class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            {{ t("ticket.timeline") }}
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="flow-root">
                            <ul class="-mb-8">
                                <li
                                    v-for="(event, index) in ticket.history"
                                    :key="event.id"
                                    class="relative pb-8"
                                >
                                    <div
                                        v-if="
                                            index !== ticket.history.length - 1
                                        "
                                        class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-gray-200"
                                    ></div>
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span
                                                :class="[
                                                    'h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white',
                                                    getTimelineColor(
                                                        event.action
                                                    ),
                                                ]"
                                            >
                                                <svg
                                                    class="w-4 h-4 text-white"
                                                    fill="currentColor"
                                                    viewBox="0 0 20 20"
                                                >
                                                    <path
                                                        fill-rule="evenodd"
                                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                                        clip-rule="evenodd"
                                                    />
                                                </svg>
                                            </span>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div>
                                                <p
                                                    class="text-sm text-gray-900"
                                                >
                                                    {{ event.description }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-500 mt-1"
                                                >
                                                    {{
                                                        event.formatted_created_at
                                                    }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>

                <!-- Rating (if ticket is closed) -->
                <div v-if="ticket.rating" class="bg-white rounded-lg shadow-md">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            Your Rating
                        </h2>
                    </div>

                    <div class="p-6">
                        <div class="flex items-center space-x-2 mb-3">
                            <div class="flex text-yellow-400">
                                <svg
                                    v-for="star in 5"
                                    :key="star"
                                    :class="[
                                        'w-5 h-5',
                                        star <= ticket.rating
                                            ? 'text-yellow-400'
                                            : 'text-gray-300',
                                    ]"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                            </div>
                            <span class="text-sm text-gray-600"
                                >{{ ticket.rating }}/5 stars</span
                            >
                        </div>
                        <p v-if="ticket.feedback" class="text-sm text-gray-700">
                            {{ ticket.feedback }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Rating Modal -->
        <Modal
            v-model:show="showRatingModal"
            title="Rate This Ticket"
            size="md"
        >
            <form @submit.prevent="submitRating">
                <div class="space-y-4">
                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >How satisfied are you with the resolution?</label
                        >
                        <div class="flex items-center space-x-2">
                            <button
                                v-for="star in 5"
                                :key="star"
                                type="button"
                                @click="ratingForm.rating = star"
                                :class="[
                                    'w-8 h-8',
                                    star <= ratingForm.rating
                                        ? 'text-yellow-400'
                                        : 'text-gray-300',
                                ]"
                            >
                                <svg fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
                                    />
                                </svg>
                            </button>
                            <span class="ml-2 text-sm text-gray-600"
                                >{{ ratingForm.rating }}/5</span
                            >
                        </div>
                    </div>

                    <div>
                        <label
                            class="block text-sm font-medium text-gray-700 mb-2"
                            >Additional Feedback (Optional)</label
                        >
                        <textarea
                            v-model="ratingForm.feedback"
                            rows="3"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            placeholder="Share your experience or suggestions for improvement..."
                        ></textarea>
                    </div>
                </div>
            </form>

            <template #footer>
                <button
                    @click="showRatingModal = false"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition mr-3"
                >
                    Cancel
                </button>
                <button
                    @click="submitRating"
                    :disabled="!ratingForm.rating || ratingForm.processing || isLoadingRating"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed flex items-center"
                >
                    <svg
                        v-if="ratingForm.processing || isLoadingRating"
                        class="animate-spin -ml-1 mr-2 h-4 w-4 text-white"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span v-if="!ratingForm.processing && !isLoadingRating">Submit Rating & Close</span>
                    <span v-else>Submitting...</span>
                </button>
            </template>
        </Modal>

        <!-- Image Preview Modal -->
        <ImagePreviewModal
            v-model:show="showImagePreview"
            :images="imageAttachments"
            :initial-index="selectedImageIndex"
        />
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from "vue";
import { useForm, Link, router, usePage } from "@inertiajs/vue3";
import { useI18n } from "vue-i18n";
import { route } from "ziggy-js";
import AppLayout from "@/Layouts/AppLayout.vue";
import FileUpload from "@/Components/Common/FileUpload.vue";
import Modal from "@/Components/Common/Modal.vue";
import ImagePreviewModal from "@/Components/Common/ImagePreviewModal.vue";
import { useToasts } from "@/composables/useToasts";

const { t } = useI18n();
const { success, error, warning, info } = useToasts();

const props = defineProps({
    ticket: {
        type: Object,
        required: true,
    },
    focus: {
        type: String,
        default: null,
    },
});

const breadcrumbs = [
    { label: "Dashboard", href: route("user.dashboard") },
    { label: "Tickets", href: route("user.tickets.index") },
    { label: props.ticket.ticket_number },
];

const showRatingModal = ref(false);
const showImagePreview = ref(false);
const selectedImageIndex = ref(0);
const isLoadingComment = ref(false);
const isLoadingRating = ref(false);
const isClosingTicket = ref(false);

// Check if user can edit ticket (only if status is open or assigned)
const canEditTicket = computed(() => {
    return props.ticket.status === 'open' || props.ticket.status === 'assigned';
});

const commentForm = useForm({
    comment: "",
    attachments: [],
});

const ratingForm = useForm({
    rating: 5,
    feedback: "",
});

const editingComment = ref(null);
const editForm = useForm({
    comment: "",
});

// Auto-open rating modal if focus parameter is set to 'rating'
onMounted(() => {
    if (props.focus === 'rating' && props.ticket.status === 'resolved' && !props.ticket.rating) {
        showRatingModal.value = true;
    }
});

// Computed property for image attachments
const imageAttachments = computed(() => {
    if (!props.ticket.attachments) return [];
    
    return props.ticket.attachments
        .filter(attachment => isImage(attachment.name))
        .map(attachment => ({
            url: route('user.tickets.download', {
                ticket: props.ticket.id,
                filename: attachment.name,
            }),
            name: attachment.name,
            size: attachment.size,
            downloadUrl: route('user.tickets.download', {
                ticket: props.ticket.id,
                filename: attachment.name,
            }),
        }));
});

const addComment = () => {
    isLoadingComment.value = true;
    commentForm.post(route("user.tickets.comments.store", props.ticket.id), {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset();
            commentForm.attachments = [];
            success({
                title: 'Comment Added',
                message: 'Your comment has been added successfully.',
            });
            isLoadingComment.value = false;
        },
        onError: () => {
            error({
                title: 'Error',
                message: 'Failed to add comment. Please try again.',
            });
            isLoadingComment.value = false;
        },
    });
};

const startEdit = (comment) => {
    editingComment.value = comment.id;
    editForm.comment = comment.comment;
};

const cancelEdit = () => {
    editingComment.value = null;
    editForm.reset();
};

const updateComment = (commentId) => {
    editForm.put(
        route("user.tickets.comments.update", {
            ticket: props.ticket.id,
            comment: commentId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editingComment.value = null;
                editForm.reset();
                success({
                    title: 'Comment Updated',
                    message: 'Your comment has been updated successfully.',
                });
            },
            onError: () => {
                error({
                    title: 'Error',
                    message: 'Failed to update comment. Please try again.',
                });
            },
        }
    );
};

const deleteComment = (commentId) => {
    if (confirm(t("common.confirmDeleteComment"))) {
        router.delete(
            route("user.tickets.comments.destroy", {
                ticket: props.ticket.id,
                comment: commentId,
            }),
            {
                preserveScroll: true,
                onSuccess: () => {
                    success({
                        title: 'Comment Deleted',
                        message: 'Your comment has been deleted successfully.',
                    });
                },
                onError: () => {
                    error({
                        title: 'Error',
                        message: 'Failed to delete comment. Please try again.',
                    });
                },
            }
        );
    }
};

const submitRating = () => {
    isLoadingRating.value = true;
    ratingForm.post(route("user.tickets.rate", props.ticket.id), {
        onSuccess: () => {
            showRatingModal.value = false;
            isLoadingRating.value = false;
            success({
                title: 'Rating Submitted',
                message: 'Thank you for your feedback! The ticket has been closed.',
            });
        },
        onError: () => {
            isLoadingRating.value = false;
            error({
                title: 'Error',
                message: 'Failed to submit rating. Please try again.',
            });
        },
    });
};

const closeTicket = () => {
    if (confirm("Are you sure you want to close this ticket?")) {
        isClosingTicket.value = true;
        router.post(route("user.tickets.close", props.ticket.id), {
            onSuccess: () => {
                isClosingTicket.value = false;
                success({
                    title: 'Ticket Closed',
                    message: 'The ticket has been closed successfully.',
                });
            },
            onError: () => {
                isClosingTicket.value = false;
                error({
                    title: 'Error',
                    message: 'Failed to close ticket. Please try again.',
                });
            },
        });
    }
};

const openImagePreview = (index) => {
    selectedImageIndex.value = index;
    showImagePreview.value = true;
};

const getStatusColor = (status) => {
    const colors = {
        open: "bg-yellow-100 text-yellow-800",
        assigned: "bg-blue-100 text-blue-800",
        in_progress: "bg-indigo-100 text-indigo-800",
        waiting_response: "bg-orange-100 text-orange-800",
        resolved: "bg-green-100 text-green-800",
        closed: "bg-gray-100 text-gray-800",
        cancelled: "bg-red-100 text-red-800",
    };
    return colors[status] || "bg-gray-100 text-gray-800";
};

const getPriorityColor = (priority) => {
    const colors = {
        low: "bg-gray-100 text-gray-800",
        medium: "bg-blue-100 text-blue-800",
        high: "bg-orange-100 text-orange-800",
        urgent: "bg-red-100 text-red-800",
    };
    return colors[priority] || "bg-gray-100 text-gray-800";
};

const getRoleColor = (role) => {
    const colors = {
        user: "bg-blue-100 text-blue-800",
        teknisi: "bg-green-100 text-green-800",
        admin_helpdesk: "bg-purple-100 text-purple-800",
        admin_aplikasi: "bg-indigo-100 text-indigo-800",
    };
    return colors[role] || "bg-gray-100 text-gray-800";
};

const getRoleLabel = (role) => {
    const labels = {
        user: "User",
        teknisi: "Teknisi",
        admin_helpdesk: "Admin Helpdesk",
        admin_aplikasi: "Admin Aplikasi",
    };
    return labels[role] || "Unknown";
};

const getTimelineColor = (action) => {
    const colors = {
        created: "bg-blue-500",
        assigned: "bg-indigo-500",
        status_changed: "bg-yellow-500",
        resolved: "bg-green-500",
        closed: "bg-gray-500",
    };
    return colors[action] || "bg-gray-500";
};

const getInitials = (name) => {
    if (!name) return "U";
    const names = name.split(" ");
    if (names.length >= 2) {
        return (names[0][0] + names[1][0]).toUpperCase();
    }
    return names[0][0].toUpperCase();
};

const isImage = (filename) => {
    const imageExtensions = [".jpg", ".jpeg", ".png", ".gif", ".bmp", ".webp"];
    const extension = filename
        .toLowerCase()
        .substring(filename.lastIndexOf("."));
    return imageExtensions.includes(extension);
};

const formatFileSize = (bytes) => {
    if (bytes === 0) return "0 Bytes";
    const k = 1024;
    const sizes = ["Bytes", "KB", "MB", "GB"];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + " " + sizes[i];
};
</script>

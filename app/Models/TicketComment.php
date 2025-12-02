<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class TicketComment extends Model
{
    use HasFactory;

    // Comment types
    const TYPE_GENERAL = 'general';
    const TYPE_TECHNICAL = 'technical';
    const TYPE_STATUS_UPDATE = 'status_update';
    const TYPE_RESOLUTION = 'resolution';
    const TYPE_ESCALATION = 'escalation';
    const TYPE_REASSIGNMENT_REQUEST = 'reassignment_request';
    const TYPE_SOLUTION_DOC = 'solution_doc';

    // Comment visibility
    const VISIBILITY_PUBLIC = 'public';
    const VISIBILITY_INTERNAL = 'internal';
    const VISIBILITY_PRIVATE = 'private';

    // Moderation status
    const MODERATION_PENDING = 'pending';
    const MODERATION_APPROVED = 'approved';
    const MODERATION_REJECTED = 'rejected';
    const MODERATION_FLAGGED = 'flagged';

    protected $fillable = [
        'ticket_id',
        'commenter_nip',
        'commenter_type',
        'comment',
        'type',
        'attachments',
        'is_internal',
        'requires_response',
        'responded_at',
        'technical_details',
        'metadata',
        'parent_id',
        'visibility',
        'moderation_status',
        'moderated_by',
        'moderated_at',
        'edited_at',
        'edited_by',
        'is_pinned',
        'pinned_at',
        'pinned_by',
        'content_warnings',
        'rich_content',
        'mentioned_users',
        'read_by',
        'helpful_votes',
        'total_votes',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_internal' => 'boolean',
        'requires_response' => 'boolean',
        'responded_at' => 'datetime',
        'metadata' => 'array',
        'parent_id' => 'integer',
        'moderated_at' => 'datetime',
        'edited_at' => 'datetime',
        'is_pinned' => 'boolean',
        'pinned_at' => 'datetime',
        'content_warnings' => 'array',
        'rich_content' => 'array',
        'mentioned_users' => 'array',
        'read_by' => 'array',
        'helpful_votes' => 'integer',
        'total_votes' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the ticket this comment belongs to
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'ticket_id');
    }

    /**
     * Get the commenter (polymorphic relationship)
     * Using safer morphTo with null handling
     */
    public function commenter(): MorphTo
    {
        return $this->morphTo('commenter', 'commenter_type', 'commenter_nip', 'nip')
                    ->withDefault(function () {
                        // Return a default "system" user if commenter not found
                        return (object) [
                            'nip' => 'system',
                            'name' => 'System',
                            'email' => 'system@helpdesk.local'
                        ];
                    });
    }

    /**
     * Get the parent comment (for threading)
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class, 'parent_id');
    }

    /**
     * Get child comments (replies)
     */
    public function replies()
    {
        return $this->hasMany(TicketComment::class, 'parent_id');
    }

    /**
     * Get moderator who moderated this comment
     */
    public function moderator(): MorphTo
    {
        return $this->morphTo('moderator', null, 'moderated_by');
    }

    /**
     * Get user who edited this comment
     */
    public function editor(): MorphTo
    {
        return $this->morphTo('editor', null, 'edited_by');
    }

    /**
     * Get user who pinned this comment
     */
    public function pinner(): MorphTo
    {
        return $this->morphTo('pinner', null, 'pinned_by');
    }

    // ==================== COMMENT MANAGEMENT ====================

    /**
     * Check if comment is a reply (has parent)
     */
    public function isReply(): bool
    {
        return !is_null($this->parent_id);
    }

    /**
     * Check if comment has replies
     */
    public function hasReplies(): bool
    {
        return $this->replies()->count() > 0;
    }

    /**
     * Get all descendant comments (recursive)
     */
    public function getAllReplies(): \Illuminate\Database\Eloquent\Collection
    {
        $replies = collect();

        foreach ($this->replies as $reply) {
            $replies->push($reply);
            $replies = $replies->merge($reply->getAllReplies());
        }

        return $replies;
    }

    /**
     * Get comment thread (parent + all replies)
     */
    public function getThread(): \Illuminate\Database\Eloquent\Collection
    {
        $thread = collect([$this]);

        if ($this->hasReplies()) {
            $thread = $thread->merge($this->getAllReplies());
        }

        return $thread;
    }

    /**
     * Check if comment is internal (only visible to staff)
     */
    public function isInternal(): bool
    {
        return $this->is_internal || $this->visibility === self::VISIBILITY_INTERNAL;
    }

    /**
     * Check if comment is public (visible to users)
     */
    public function isPublic(): bool
    {
        return $this->visibility === self::VISIBILITY_PUBLIC;
    }

    /**
     * Check if comment is private (only visible to specific users)
     */
    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    /**
     * Check if comment requires response
     */
    public function needsResponse(): bool
    {
        return $this->requires_response && !$this->responded_at;
    }

    /**
     * Mark comment as responded
     */
    public function markAsResponded(): bool
    {
        $this->responded_at = Carbon::now();
        return $this->save();
    }

    /**
     * Check if comment is approved
     */
    public function isApproved(): bool
    {
        return $this->moderation_status === self::MODERATION_APPROVED;
    }

    /**
     * Check if comment is pending moderation
     */
    public function isPendingModeration(): bool
    {
        return $this->moderation_status === self::MODERATION_PENDING;
    }

    /**
     * Check if comment is flagged for moderation
     */
    public function isFlagged(): bool
    {
        return $this->moderation_status === self::MODERATION_FLAGGED;
    }

    /**
     * Approve comment
     */
    public function approve($moderator = null): bool
    {
        $this->moderation_status = self::MODERATION_APPROVED;
        $this->moderated_at = Carbon::now();
        $this->moderated_by = $moderator?->nip ?? $moderator?->id;

        return $this->save();
    }

    /**
     * Reject comment
     */
    public function reject(?string $reason = null, ?object $moderator = null): bool
    {
        $this->moderation_status = self::MODERATION_REJECTED;
        $this->moderated_at = Carbon::now();
        $this->moderated_by = $moderator?->nip ?? $moderator?->id;

        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['rejection_reason'] = $reason;
            $this->metadata = $metadata;
        }

        return $this->save();
    }

    /**
     * Flag comment for moderation
     */
    public function flag(?string $reason = null, ?object $flaggedBy = null): bool
    {
        $this->moderation_status = self::MODERATION_FLAGGED;
        $this->moderated_at = Carbon::now();
        $this->moderated_by = $flaggedBy?->nip ?? $flaggedBy?->id;

        if ($reason) {
            $metadata = $this->metadata ?? [];
            $metadata['flag_reason'] = $reason;
            $this->metadata = $metadata;
        }

        return $this->save();
    }

    /**
     * Pin comment
     */
    public function pin($pinnedBy = null): bool
    {
        $this->is_pinned = true;
        $this->pinned_at = Carbon::now();
        $this->pinned_by = $pinnedBy?->nip ?? $pinnedBy?->id;

        return $this->save();
    }

    /**
     * Unpin comment
     */
    public function unpin(): bool
    {
        $this->is_pinned = false;
        $this->pinned_at = null;
        $this->pinned_by = null;

        return $this->save();
    }

    /**
     * Edit comment content
     */
    public function edit(string $newContent, $editedBy = null): bool
    {
        $this->comment = $newContent;
        $this->edited_at = Carbon::now();
        $this->edited_by = $editedBy?->nip ?? $editedBy?->id;

        return $this->save();
    }

    /**
     * Add attachment to comment
     */
    public function addAttachment(string $filePath, ?string $originalName = null): bool
    {
        $attachments = $this->attachments ?? [];
        $attachments[] = [
            'path' => $filePath,
            'original_name' => $originalName ?? basename($filePath),
            'uploaded_at' => Carbon::now()->toISOString(),
        ];

        $this->attachments = $attachments;
        return $this->save();
    }

    /**
     * Remove attachment from comment
     */
    public function removeAttachment(string $filePath): bool
    {
        $attachments = $this->attachments ?? [];
        $attachments = array_filter($attachments, fn($att) => $att['path'] !== $filePath);

        $this->attachments = array_values($attachments);
        return $this->save();
    }

    /**
     * Mark comment as read by user
     */
    public function markAsRead($user): bool
    {
        $readBy = $this->read_by ?? [];
        $userKey = $user->nip ?? $user->id;

        if (!in_array($userKey, $readBy)) {
            $readBy[] = $userKey;
            $this->read_by = $readBy;
            return $this->save();
        }

        return true;
    }

    /**
     * Check if comment is read by user
     */
    public function isReadBy($user): bool
    {
        $readBy = $this->read_by ?? [];
        $userKey = $user->nip ?? $user->id;

        return in_array($userKey, $readBy);
    }

    /**
     * Vote comment as helpful
     */
    public function voteHelpful($user): bool
    {
        $mentionedUsers = $this->mentioned_users ?? [];
        $userKey = $user->nip ?? $user->id;

        if (!in_array($userKey, $mentionedUsers)) {
            $mentionedUsers[] = $userKey;
            $this->mentioned_users = $mentionedUsers;
            $this->helpful_votes = ($this->helpful_votes ?? 0) + 1;
            $this->total_votes = ($this->total_votes ?? 0) + 1;

            return $this->save();
        }

        return true;
    }

    /**
     * Get comment depth in thread (0 for root, 1 for reply, etc.)
     */
    public function getDepth(): int
    {
        if (!$this->isReply()) {
            return 0;
        }

        $depth = 1;
        $parent = $this->parent;

        while ($parent && $parent->isReply()) {
            $depth++;
            $parent = $parent->parent;
        }

        return $depth;
    }

    /**
     * Check if comment can be replied to
     */
    public function canBeRepliedTo(): bool
    {
        return $this->isApproved() && !$this->ticket->isClosed();
    }

    /**
     * Check if comment can be edited by user
     */
    public function canBeEditedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        $userKey = $user->nip ?? $user->id;
        return $this->commenter_nip === $userKey && $this->isApproved();
    }

    /**
     * Check if comment can be moderated by user
     */
    public function canBeModeratedBy($user): bool
    {
        if (!$user) {
            return false;
        }

        // AdminHelpdesk and AdminAplikasi can moderate comments
        if ($user instanceof AdminHelpdesk || $user instanceof AdminAplikasi) {
            return true;
        }

        // Teknisi can moderate technical comments
        if ($user instanceof Teknisi && $this->type === self::TYPE_TECHNICAL) {
            return true;
        }

        return false;
    }

    // ==================== ACCESSORS & MUTATORS ====================

    /**
     * Get comment type badge color for UI
     */
    public function getTypeBadgeColorAttribute(): string
    {
        return match($this->type) {
            self::TYPE_GENERAL => 'primary',
            self::TYPE_TECHNICAL => 'info',
            self::TYPE_STATUS_UPDATE => 'warning',
            self::TYPE_RESOLUTION => 'success',
            self::TYPE_ESCALATION => 'danger',
            default => 'light',
        };
    }

    /**
     * Get comment type label for UI
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            self::TYPE_GENERAL => 'General',
            self::TYPE_TECHNICAL => 'Technical',
            self::TYPE_STATUS_UPDATE => 'Status Update',
            self::TYPE_RESOLUTION => 'Resolution',
            self::TYPE_ESCALATION => 'Escalation',
            self::TYPE_REASSIGNMENT_REQUEST => 'Reassignment Request',
            self::TYPE_SOLUTION_DOC => 'Solution Documentation',
            default => 'Unknown',
        };
    }

    /**
     * Get visibility badge color for UI
     */
    public function getVisibilityBadgeColorAttribute(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_PUBLIC => 'success',
            self::VISIBILITY_INTERNAL => 'warning',
            self::VISIBILITY_PRIVATE => 'danger',
            default => 'light',
        };
    }

    /**
     * Get visibility label for UI
     */
    public function getVisibilityLabelAttribute(): string
    {
        return match($this->visibility) {
            self::VISIBILITY_PUBLIC => 'Public',
            self::VISIBILITY_INTERNAL => 'Internal',
            self::VISIBILITY_PRIVATE => 'Private',
            default => 'Unknown',
        };
    }

    /**
     * Get moderation status badge color for UI
     */
    public function getModerationStatusBadgeColorAttribute(): string
    {
        return match($this->moderation_status) {
            self::MODERATION_PENDING => 'warning',
            self::MODERATION_APPROVED => 'success',
            self::MODERATION_REJECTED => 'danger',
            self::MODERATION_FLAGGED => 'info',
            default => 'light',
        };
    }

    /**
     * Get moderation status label for UI
     */
    public function getModerationStatusLabelAttribute(): string
    {
        return match($this->moderation_status) {
            self::MODERATION_PENDING => 'Pending',
            self::MODERATION_APPROVED => 'Approved',
            self::MODERATION_REJECTED => 'Rejected',
            self::MODERATION_FLAGGED => 'Flagged',
            default => 'Unknown',
        };
    }

    /**
     * Get formatted created date for UI
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i');
    }

    /**
     * Get time elapsed since comment creation
     */
    public function getTimeElapsedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get formatted edited date for UI
     */
    public function getFormattedEditedAtAttribute(): string
    {
        if (!$this->edited_at) {
            return '';
        }
        return $this->edited_at->format('d M Y, H:i') . ' (edited)';
    }

    /**
     * Get commenter's display name
     */
    public function getCommenterNameAttribute(): string
    {
        if ($this->commenter) {
            return $this->commenter->name;
        }

        return 'Unknown User';
    }

    /**
     * Get commenter's role/department for UI
     */
    public function getCommenterRoleAttribute(): string
    {
        if (!$this->commenter) {
            return '';
        }

        return match($this->commenter_type) {
            'App\\Models\\User' => 'User',
            'App\\Models\\Teknisi' => 'Teknisi',
            'App\\Models\\AdminHelpdesk' => 'Helpdesk Admin',
            'App\\Models\\AdminAplikasi' => 'Application Admin',
            default => 'Unknown',
        };
    }

    /**
     * Get commenter's avatar/profile photo URL
     */
    public function getCommenterAvatarAttribute(): string
    {
        if ($this->commenter && method_exists($this->commenter, 'profile_photo_url')) {
            return $this->commenter->profile_photo_url;
        }

        return asset('images/avatars/default-user.png');
    }

    /**
     * Get formatted comment content with basic HTML support
     */
    public function getFormattedCommentAttribute(): string
    {
        $content = $this->comment;

        if (!$content) {
            return '';
        }

        // Basic formatting for URLs, emails, mentions
        $content = preg_replace('/https?:\/\/[^\s]+/', '<a href="$0" target="_blank">$0</a>', $content);
        $content = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '<a href="mailto:$0">$0</a>', $content);

        // Convert line breaks to HTML
        $content = nl2br(e($content));

        return $content;
    }

    /**
     * Get comment excerpt (first 100 characters)
     */
    public function getExcerptAttribute(): string
    {
        if (!$this->comment) {
            return '';
        }

        $excerpt = Str::limit(strip_tags($this->comment), 100);
        return $excerpt . (strlen($this->comment) > 100 ? '...' : '');
    }

    /**
     * Get attachments count
     */
    public function getAttachmentsCountAttribute(): int
    {
        return count($this->attachments ?? []);
    }

    /**
     * Get formatted attachments for UI
     */
    public function getFormattedAttachmentsAttribute(): array
    {
        $attachments = $this->attachments ?? [];

        return array_map(function ($attachment) {
            return [
                'path' => $attachment['path'],
                'original_name' => $attachment['original_name'] ?? basename($attachment['path']),
                'url' => asset('storage/' . $attachment['path']),
                'size' => $this->getFileSize($attachment['path']),
                'extension' => strtolower(pathinfo($attachment['path'], PATHINFO_EXTENSION)),
                'uploaded_at' => $attachment['uploaded_at'] ?? $this->created_at->format('Y-m-d H:i:s'),
            ];
        }, $attachments);
    }

    /**
     * Get file size for attachment
     */
    private function getFileSize(string $filePath): string
    {
        $fullPath = storage_path('app/public/' . $filePath);

        if (file_exists($fullPath)) {
            $bytes = filesize($fullPath);

            if ($bytes >= 1073741824) {
                return number_format($bytes / 1073741824, 2) . ' GB';
            } elseif ($bytes >= 1048576) {
                return number_format($bytes / 1048576, 2) . ' MB';
            } elseif ($bytes >= 1024) {
                return number_format($bytes / 1024, 2) . ' KB';
            } else {
                return $bytes . ' bytes';
            }
        }

        return 'Unknown';
    }

    /**
     * Get comment status for UI display
     */
    public function getStatusAttribute(): array
    {
        return [
            'type' => [
                'value' => $this->type,
                'label' => $this->type_label,
                'badge_color' => $this->type_badge_color,
            ],
            'visibility' => [
                'value' => $this->visibility,
                'label' => $this->visibility_label,
                'badge_color' => $this->visibility_badge_color,
            ],
            'moderation' => [
                'status' => $this->moderation_status,
                'label' => $this->moderation_status_label,
                'badge_color' => $this->moderation_status_badge_color,
            ],
            'is_internal' => $this->is_internal,
            'requires_response' => $this->requires_response,
            'is_pinned' => $this->is_pinned,
            'is_edited' => !is_null($this->edited_at),
        ];
    }

    /**
     * Get comment metadata for UI
     */
    public function getCommentMetadataAttribute(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at,
            'formatted_created_at' => $this->formatted_created_at,
            'time_elapsed' => $this->time_elapsed,
            'edited_at' => $this->edited_at,
            'formatted_edited_at' => $this->formatted_edited_at,
            'commenter' => [
                'name' => $this->commenter_name,
                'role' => $this->commenter_role,
                'avatar' => $this->commenter_avatar,
            ],
            'attachments_count' => $this->attachments_count,
            'replies_count' => $this->replies()->count(),
            'helpful_votes' => $this->helpful_votes ?? 0,
            'total_votes' => $this->total_votes ?? 0,
            'depth' => $this->getDepth(),
            'is_reply' => $this->isReply(),
            'has_replies' => $this->hasReplies(),
        ];
    }

    /**
     * Get comment for API response
     */
    public function getApiResponseAttribute(): array
    {
        return [
            'id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'comment' => $this->comment,
            'formatted_comment' => $this->formatted_comment,
            'excerpt' => $this->excerpt,
            'type' => $this->type,
            'type_label' => $this->type_label,
            'visibility' => $this->visibility,
            'visibility_label' => $this->visibility_label,
            'is_internal' => $this->is_internal,
            'requires_response' => $this->requires_response,
            'responded_at' => $this->responded_at,
            'technical_details' => $this->technical_details,
            'metadata' => $this->metadata,
            'status' => $this->status,
            'comment_metadata' => $this->comment_metadata,
            'attachments' => $this->formatted_attachments,
            'parent_id' => $this->parent_id,
            'replies' => $this->replies()->approved()->get()->map->api_response,
            'can_edit' => false, // Will be set by controller based on user
            'can_moderate' => false, // Will be set by controller based on user
            'can_reply' => $this->canBeRepliedTo(),
        ];
    }

    // ==================== BUSINESS LOGIC ====================

    /**
     * Validate comment data
     */
    public static function validateComment(array $data): array
    {
        $rules = [
            'comment' => 'required|string|max:5000',
            'type' => 'required|in:' . implode(',', [
                self::TYPE_GENERAL,
                self::TYPE_TECHNICAL,
                self::TYPE_STATUS_UPDATE,
                self::TYPE_RESOLUTION,
                self::TYPE_ESCALATION,
                self::TYPE_REASSIGNMENT_REQUEST,
                self::TYPE_SOLUTION_DOC
            ]),
            'visibility' => 'required|in:' . implode(',', [
                self::VISIBILITY_PUBLIC,
                self::VISIBILITY_INTERNAL,
                self::VISIBILITY_PRIVATE
            ]),
            'is_internal' => 'boolean',
            'requires_response' => 'boolean',
            'technical_details' => 'nullable|string|max:2000',
            'parent_id' => 'nullable|exists:ticket_comments,id',
        ];

        $messages = [
            'comment.required' => 'Comment content is required.',
            'comment.max' => 'Comment cannot exceed 5000 characters.',
            'type.required' => 'Comment type is required.',
            'type.in' => 'Invalid comment type.',
            'visibility.required' => 'Comment visibility is required.',
            'visibility.in' => 'Invalid visibility setting.',
            'technical_details.max' => 'Technical details cannot exceed 2000 characters.',
            'parent_id.exists' => 'Parent comment does not exist.',
        ];

        $validator = \Illuminate\Support\Facades\Validator::make($data, $rules, $messages);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true];
    }

    /**
     * Create new comment with validation
     */
    public static function createComment(array $data): array
    {
        $validation = self::validateComment($data);

        if (!$validation['valid']) {
            return $validation;
        }

        try {
            $comment = self::create($data);

            // Trigger notifications
            $comment->triggerNotifications();

            // Log activity
            $comment->logActivity('comment_created');

            return [
                'valid' => true,
                'comment' => $comment,
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['general' => 'Failed to create comment: ' . $e->getMessage()],
            ];
        }
    }

    /**
     * Update comment with validation
     */
    public function updateComment(array $data, $updatedBy = null): array
    {
        $validation = self::validateComment($data);

        if (!$validation['valid']) {
            return $validation;
        }

        try {
            $oldContent = $this->comment;
            $this->fill($data);

            if ($this->save()) {
                // Track edit if content changed
                if ($oldContent !== $data['comment']) {
                    $this->edit($data['comment'], $updatedBy);
                    $this->logActivity('comment_edited', [
                        'old_content' => $oldContent,
                        'new_content' => $data['comment'],
                        'edited_by' => $updatedBy?->nip ?? $updatedBy?->id,
                    ]);
                }

                // Trigger notifications for updates
                $this->triggerNotifications('updated');

                return [
                    'valid' => true,
                    'comment' => $this,
                ];
            }
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'errors' => ['general' => 'Failed to update comment: ' . $e->getMessage()],
            ];
        }

        return [
            'valid' => false,
            'errors' => ['general' => 'Failed to update comment.'],
        ];
    }

    /**
     * Handle file attachments
     */
    public function handleAttachments(array $files): array
    {
        $uploadedFiles = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                // Validate file
                $validation = $this->validateFile($file);

                if (!$validation['valid']) {
                    $errors[] = "File {$file->getClientOriginalName()}: " . implode(', ', $validation['errors']);
                    continue;
                }

                // Store file
                $path = $file->store('ticket-comments', 'public');

                if ($path) {
                    $this->addAttachment($path, $file->getClientOriginalName());
                    $uploadedFiles[] = [
                        'path' => $path,
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                    ];
                } else {
                    $errors[] = "Failed to store file: {$file->getClientOriginalName()}";
                }
            } catch (\Exception $e) {
                $errors[] = "Error uploading {$file->getClientOriginalName()}: " . $e->getMessage();
            }
        }

        return [
            'uploaded' => $uploadedFiles,
            'errors' => $errors,
        ];
    }

    /**
     * Validate uploaded file
     */
    private function validateFile($file): array
    {
        $maxSize = 10 * 1024 * 1024; // 10MB
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
            'application/zip',
            'application/x-rar-compressed',
        ];

        $errors = [];

        if (!$file->isValid()) {
            $errors[] = 'Invalid file upload.';
        }

        if ($file->getSize() > $maxSize) {
            $errors[] = 'File size cannot exceed 10MB.';
        }

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'File type not allowed.';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Trigger notifications for comment events
     */
    public function triggerNotifications(string $event = 'created'): void
    {
        try {
            $ticket = $this->ticket;
            $commenter = $this->commenter;

            // Notify ticket owner if not the commenter
            if ($ticket->user && $ticket->user->nip !== $this->commenter_nip) {
                $this->notifyUser($ticket->user, $event);
            }

            // Notify assigned teknisi if not the commenter
            if ($ticket->assignedTeknisi && $ticket->assignedTeknisi->nip !== $this->commenter_nip) {
                $this->notifyTeknisi($ticket->assignedTeknisi, $event);
            }

            // Notify mentioned users
            $this->notifyMentionedUsers();

            // Notify admins based on comment type and visibility
            $this->notifyAdmins($event);

            // REMOVED: Real-time broadcasting event for the comment
            // This was: event(new TicketCommentAddedEvent($this, $commenter));

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to trigger comment notifications: ' . $e->getMessage());
        }
    }

    /**
     * Notify ticket owner
     */
    private function notifyUser($user, string $event): void
    {
        if (!$user) return;

        $message = $this->generateNotificationMessage($event, 'user');

        // Create notification record
        \App\Models\Notification::create([
            'user_nip' => $user->nip,
            'type' => 'ticket_comment',
            'title' => "New comment on ticket #{$this->ticket->ticket_number}",
            'message' => $message,
            'data' => [
                'ticket_id' => $this->ticket_id,
                'comment_id' => $this->id,
                'commenter_name' => $this->commenter_name,
                'comment_type' => $this->type,
                'is_internal' => $this->is_internal,
            ],
            'is_read' => false,
        ]);
    }

    /**
     * Notify assigned teknisi
     */
    private function notifyTeknisi($teknisi, string $event): void
    {
        if (!$teknisi) return;

        $message = $this->generateNotificationMessage($event, 'teknisi');

        \App\Models\Notification::create([
            'teknisi_nip' => $teknisi->nip,
            'type' => 'ticket_comment',
            'title' => "New comment on assigned ticket #{$this->ticket->ticket_number}",
            'message' => $message,
            'data' => [
                'ticket_id' => $this->ticket_id,
                'comment_id' => $this->id,
                'commenter_name' => $this->commenter_name,
                'comment_type' => $this->type,
                'is_internal' => $this->is_internal,
            ],
            'is_read' => false,
        ]);
    }

    /**
     * Notify mentioned users
     */
    private function notifyMentionedUsers(): void
    {
        $mentionedUsers = $this->mentioned_users ?? [];

        foreach ($mentionedUsers as $userKey) {
            // This would need to be implemented based on how user keys are stored
            // For now, it's a placeholder for the logic
        }
    }

    /**
     * Notify relevant admins
     */
    private function notifyAdmins(string $event): void
    {
        // Notify helpdesk admins for internal comments or technical issues
        if ($this->is_internal || $this->type === self::TYPE_TECHNICAL) {
            $admins = \App\Models\AdminHelpdesk::active()->get();

            foreach ($admins as $admin) {
                $message = $this->generateNotificationMessage($event, 'admin');

                \App\Models\Notification::create([
                    'admin_nip' => $admin->nip,
                    'type' => 'ticket_comment',
                    'title' => "Internal comment on ticket #{$this->ticket->ticket_number}",
                    'message' => $message,
                    'data' => [
                        'ticket_id' => $this->ticket_id,
                        'comment_id' => $this->id,
                        'commenter_name' => $this->commenter_name,
                        'comment_type' => $this->type,
                        'visibility' => $this->visibility,
                    ],
                    'is_read' => false,
                ]);
            }
        }
    }

    /**
     * Generate notification message based on event and recipient type
     */
    private function generateNotificationMessage(string $event, string $recipientType): string
    {
        $baseMessage = "A new comment has been added";

        if ($event === 'updated') {
            $baseMessage = "A comment has been updated";
        }

        $commenterInfo = " by {$this->commenter_name}";

        $typeInfo = "";
        if ($this->type !== self::TYPE_GENERAL) {
            $typeInfo = " ({$this->type_label})";
        }

        $visibilityInfo = "";
        if ($this->is_internal) {
            $visibilityInfo = " [Internal]";
        }

        return $baseMessage . $commenterInfo . $typeInfo . $visibilityInfo . ". " . $this->excerpt;
    }

    /**
     * Log comment activity
     */
    private function logActivity(string $action, array $metadata = []): void
    {
        \Illuminate\Support\Facades\Log::info("Ticket comment activity: {$action}", [
            'ticket_id' => $this->ticket_id,
            'comment_id' => $this->id,
            'commenter_nip' => $this->commenter_nip,
            'commenter_type' => $this->commenter_type,
            'action' => $action,
            'metadata' => $metadata,
            'timestamp' => Carbon::now(),
        ]);
    }

    /**
     * Check if comment contains sensitive content
     */
    public function containsSensitiveContent(): bool
    {
        $sensitivePatterns = [
            '/password[:=]\s*\S+/i',
            '/\b\d{16}\b/', // Credit card numbers
            '/\b\d{3}-\d{2}-\d{4}\b/', // SSN pattern
            '/confidential/i',
            '/secret/i',
            '/classified/i',
        ];

        $content = strtolower($this->comment . ' ' . ($this->technical_details ?? ''));

        foreach ($sensitivePatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Auto-moderate comment content
     */
    public function autoModerate(): string
    {
        // Check for sensitive content
        if ($this->containsSensitiveContent()) {
            return self::MODERATION_FLAGGED;
        }

        // Check comment length
        if (strlen($this->comment) > 4000) {
            return self::MODERATION_FLAGGED;
        }

        // Check for excessive caps
        if (preg_match('/[A-Z]{20,}/', $this->comment)) {
            return self::MODERATION_FLAGGED;
        }

        // Check for spam patterns
        $spamIndicators = ['http', 'www.', 'click here', 'buy now'];
        $content = strtolower($this->comment);

        foreach ($spamIndicators as $indicator) {
            if (substr_count($content, $indicator) > 2) {
                return self::MODERATION_FLAGGED;
            }
        }

        return self::MODERATION_APPROVED;
    }

    /**
     * Get comment statistics for analytics
     */
    public static function getCommentStatistics(?int $ticketId = null): array
    {
        $query = self::query();

        if ($ticketId) {
            $query->where('ticket_id', $ticketId);
        }

        $comments = $query->get();

        return [
            'total_comments' => $comments->count(),
            'approved_comments' => $comments->where('moderation_status', self::MODERATION_APPROVED)->count(),
            'pending_comments' => $comments->where('moderation_status', self::MODERATION_PENDING)->count(),
            'flagged_comments' => $comments->where('moderation_status', self::MODERATION_FLAGGED)->count(),
            'internal_comments' => $comments->where('is_internal', true)->count(),
            'comments_with_attachments' => $comments->whereNotNull('attachments')->count(),
            'comments_requiring_response' => $comments->where('requires_response', true)->count(),
            'pinned_comments' => $comments->where('is_pinned', true)->count(),
            'average_comment_length' => $comments->avg(fn($c) => strlen($c->comment ?? '')),
            'comments_by_type' => $comments->groupBy('type')->map->count(),
        ];
    }

    // ==================== QUERY SCOPES ====================

    /**
     * Scope for approved comments
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('moderation_status', self::MODERATION_APPROVED);
    }

    /**
     * Scope for pending comments
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('moderation_status', self::MODERATION_PENDING);
    }

    /**
     * Scope for flagged comments
     */
    public function scopeFlagged(Builder $query): Builder
    {
        return $query->where('moderation_status', self::MODERATION_FLAGGED);
    }

    /**
     * Scope for rejected comments
     */
    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('moderation_status', self::MODERATION_REJECTED);
    }

    /**
     * Scope for internal comments
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true);
    }

    /**
     * Scope for public comments
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    /**
     * Scope for private comments
     */
    public function scopePrivate(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_PRIVATE);
    }

    /**
     * Scope for comments requiring response
     */
    public function scopeRequiringResponse(Builder $query): Builder
    {
        return $query->where('requires_response', true)
                    ->whereNull('responded_at');
    }

    /**
     * Scope for comments that have been responded to
     */
    public function scopeResponded(Builder $query): Builder
    {
        return $query->where('requires_response', true)
                    ->whereNotNull('responded_at');
    }

    /**
     * Scope for pinned comments
     */
    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for comments with attachments
     */
    public function scopeWithAttachments(Builder $query): Builder
    {
        return $query->whereNotNull('attachments')
                    ->where('attachments', '!=', '[]');
    }

    /**
     * Scope for comments without attachments
     */
    public function scopeWithoutAttachments(Builder $query): Builder
    {
        return $query->whereNull('attachments')
                    ->orWhere('attachments', '[]');
    }

    /**
     * Scope for comments by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for general comments
     */
    public function scopeGeneral(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_GENERAL);
    }

    /**
     * Scope for technical comments
     */
    public function scopeTechnical(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_TECHNICAL);
    }

    /**
     * Scope for status update comments
     */
    public function scopeStatusUpdates(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_STATUS_UPDATE);
    }

    /**
     * Scope for resolution comments
     */
    public function scopeResolutions(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_RESOLUTION);
    }

    /**
     * Scope for escalation comments
     */
    public function scopeEscalations(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ESCALATION);
    }

    /**
     * Scope for comments by commenter
     */
    public function scopeByCommenter(Builder $query, string $commenterNip, ?string $commenterType = null): Builder
    {
        $query->where('commenter_nip', $commenterNip);

        if ($commenterType) {
            $query->where('commenter_type', $commenterType);
        }

        return $query;
    }

    /**
     * Scope for comments by users
     */
    public function scopeByUsers(Builder $query): Builder
    {
        return $query->where('commenter_type', 'App\\Models\\User');
    }

    /**
     * Scope for comments by teknisi
     */
    public function scopeByTeknisi(Builder $query): Builder
    {
        return $query->where('commenter_type', 'App\\Models\\Teknisi');
    }

    /**
     * Scope for comments by admin helpdesk
     */
    public function scopeByAdminHelpdesk(Builder $query): Builder
    {
        return $query->where('commenter_type', 'App\\Models\\AdminHelpdesk');
    }

    /**
     * Scope for comments by admin aplikasi
     */
    public function scopeByAdminAplikasi(Builder $query): Builder
    {
        return $query->where('commenter_type', 'App\\Models\\AdminAplikasi');
    }

    /**
     * Scope for root comments (not replies)
     */
    public function scopeRootComments(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Scope for replies
     */
    public function scopeReplies(Builder $query): Builder
    {
        return $query->whereNotNull('parent_id');
    }

    /**
     * Scope for comments by parent
     */
    public function scopeByParent(Builder $query, int $parentId): Builder
    {
        return $query->where('parent_id', $parentId);
    }

    /**
     * Scope for recent comments
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', Carbon::now()->subHours($hours));
    }

    /**
     * Scope for comments created today
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', Carbon::today());
    }

    /**
     * Scope for comments created this week
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek()
        ]);
    }

    /**
     * Scope for comments created this month
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', Carbon::now()->month)
                    ->whereYear('created_at', Carbon::now()->year);
    }

    /**
     * Scope for edited comments
     */
    public function scopeEdited(Builder $query): Builder
    {
        return $query->whereNotNull('edited_at');
    }

    /**
     * Scope for unedited comments
     */
    public function scopeUnedited(Builder $query): Builder
    {
        return $query->whereNull('edited_at');
    }

    /**
     * Scope for comments with technical details
     */
    public function scopeWithTechnicalDetails(Builder $query): Builder
    {
        return $query->whereNotNull('technical_details')
                    ->where('technical_details', '!=', '');
    }

    /**
     * Scope for comments with content warnings
     */
    public function scopeWithContentWarnings(Builder $query): Builder
    {
        return $query->whereNotNull('content_warnings')
                    ->where('content_warnings', '!=', '[]');
    }

    /**
     * Scope for comments with rich content
     */
    public function scopeWithRichContent(Builder $query): Builder
    {
        return $query->whereNotNull('rich_content')
                    ->where('rich_content', '!=', '[]');
    }

    /**
     * Scope for comments mentioning users
     */
    public function scopeMentioningUsers(Builder $query): Builder
    {
        return $query->whereNotNull('mentioned_users')
                    ->where('mentioned_users', '!=', '[]');
    }

    /**
     * Scope for comments with helpful votes
     */
    public function scopeWithHelpfulVotes(Builder $query): Builder
    {
        return $query->where('helpful_votes', '>', 0);
    }

    /**
     * Scope for comments by visibility level
     */
    public function scopeByVisibility(Builder $query, string $visibility): Builder
    {
        return $query->where('visibility', $visibility);
    }

    /**
     * Scope for comments by moderation status
     */
    public function scopeByModerationStatus(Builder $query, string $status): Builder
    {
        return $query->where('moderation_status', $status);
    }

    /**
     * Scope for search in comment content
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('comment', 'like', "%{$search}%")
              ->orWhere('technical_details', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for comments containing specific text
     */
    public function scopeContaining(Builder $query, string $text): Builder
    {
        return $query->where(function ($q) use ($text) {
            $q->where('comment', 'like', "%{$text}%")
              ->orWhere('technical_details', 'like', "%{$text}%");
        });
    }

    /**
     * Scope for comments by date range
     */
    public function scopeDateRange(Builder $query, Carbon $startDate, Carbon $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for comments before date
     */
    public function scopeBeforeDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '<', $date);
    }

    /**
     * Scope for comments after date
     */
    public function scopeAfterDate(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '>', $date);
    }

    /**
     * Scope for ordering by creation date (newest first)
     */
    public function scopeOrderByNewest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope for ordering by creation date (oldest first)
     */
    public function scopeOrderByOldest(Builder $query): Builder
    {
        return $query->orderBy('created_at', 'asc');
    }

    /**
     * Scope for ordering by pinned status (pinned first)
     */
    public function scopeOrderByPinned(Builder $query): Builder
    {
        return $query->orderBy('is_pinned', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for ordering by helpful votes
     */
    public function scopeOrderByHelpfulVotes(Builder $query): Builder
    {
        return $query->orderBy('helpful_votes', 'desc')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for comments visible to user
     */
    public function scopeVisibleTo(Builder $query, $user): Builder
    {
        if (!$user) {
            return $query->where('visibility', self::VISIBILITY_PUBLIC)
                        ->where('is_internal', false);
        }

        // System admins can see all comments
        if ($user instanceof AdminHelpdesk && $user->isSystemAdmin()) {
            return $query;
        }

        if ($user instanceof AdminAplikasi && $user->isSystemAdmin()) {
            return $query;
        }

        // Regular users can only see public comments
        if ($user instanceof User) {
            return $query->where(function ($q) {
                $q->where('visibility', self::VISIBILITY_PUBLIC)
                  ->orWhere('is_internal', false);
            });
        }

        // Staff can see internal comments
        if ($user instanceof Teknisi || $user instanceof AdminHelpdesk || $user instanceof AdminAplikasi) {
            return $query->where(function ($q) {
                $q->where('visibility', self::VISIBILITY_PUBLIC)
                  ->orWhere('visibility', self::VISIBILITY_INTERNAL)
                  ->orWhere('is_internal', true);
            });
        }

        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    /**
     * Scope for comments editable by user
     */
    public function scopeEditableBy(Builder $query, $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No results
        }

        $userKey = $user->nip ?? $user->id;
        return $query->where('commenter_nip', $userKey)
                    ->where('moderation_status', self::MODERATION_APPROVED);
    }

    /**
     * Scope for comments moderatable by user
     */
    public function scopeModeratableBy(Builder $query, $user): Builder
    {
        if (!$user) {
            return $query->whereRaw('1 = 0'); // No results
        }

        // AdminHelpdesk and AdminAplikasi can moderate all comments
        if ($user instanceof AdminHelpdesk || $user instanceof AdminAplikasi) {
            return $query;
        }

        // Teknisi can moderate technical comments
        if ($user instanceof Teknisi) {
            return $query->where('type', self::TYPE_TECHNICAL);
        }

        return $query->whereRaw('1 = 0'); // No results for regular users
    }

    /**
     * Scope for comments in ticket thread
     */
    public function scopeInTicketThread(Builder $query, int $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId)
                    ->orderByPinned()
                    ->orderByOldest();
    }

    /**
     * Scope for top-level comments in ticket
     */
    public function scopeTopLevelInTicket(Builder $query, int $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId)
                    ->rootComments()
                    ->orderByPinned()
                    ->orderByOldest();
    }

    /**
     * Scope for active comments (approved and not rejected)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('moderation_status', [
            self::MODERATION_APPROVED,
            self::MODERATION_PENDING
        ]);
    }

    /**
     * Scope for comments needing attention (flagged or pending)
     */
    public function scopeNeedingAttention(Builder $query): Builder
    {
        return $query->whereIn('moderation_status', [
            self::MODERATION_PENDING,
            self::MODERATION_FLAGGED
        ]);
    }

    /**
     * Scope for comments with high engagement (replies or votes)
     */
    public function scopeHighEngagement(Builder $query, int $minReplies = 3, int $minVotes = 5): Builder
    {
        return $query->where(function ($q) use ($minReplies, $minVotes) {
            $q->where('helpful_votes', '>=', $minVotes)
              ->orWhereRaw("(
                  SELECT COUNT(*)
                  FROM ticket_comments rc
                  WHERE rc.parent_id = ticket_comments.id
              ) >= ?", [$minReplies]);
        });
    }

    /**
     * Scope for long comments
     */
    public function scopeLongComments(Builder $query, int $minLength = 500): Builder
    {
        return $query->whereRaw('LENGTH(comment) >= ?', [$minLength]);
    }

    /**
     * Scope for short comments
     */
    public function scopeShortComments(Builder $query, int $maxLength = 100): Builder
    {
        return $query->whereRaw('LENGTH(comment) <= ?', [$maxLength]);
    }

    // ==================== CONTENT MANAGEMENT ====================

    /**
     * Filter content for display (remove sensitive data, format, etc.)
     */
    public function filterContentForDisplay($user = null): string
    {
        $content = $this->comment;

        if (!$content) {
            return '';
        }

        // Remove sensitive information if user shouldn't see it
        if (!$this->canUserSeeSensitiveContent($user)) {
            $content = $this->removeSensitiveContent($content);
        }

        // Apply content warnings if needed
        if ($this->shouldShowContentWarning()) {
            $content = $this->addContentWarning($content);
        }

        return $content;
    }

    /**
     * Check if user can see sensitive content
     */
    private function canUserSeeSensitiveContent($user): bool
    {
        if (!$user) {
            return false;
        }

        // System admins can see all content
        if (($user instanceof AdminHelpdesk || $user instanceof AdminAplikasi) && $user->isSystemAdmin()) {
            return true;
        }

        // Teknisi can see technical details
        if ($user instanceof Teknisi) {
            return true;
        }

        // Helpdesk admins can see internal content
        if ($user instanceof AdminHelpdesk) {
            return true;
        }

        return false;
    }

    /**
     * Remove sensitive content from text
     */
    private function removeSensitiveContent(string $content): string
    {
        $sensitivePatterns = [
            '/password[:=]\s*\S+/i' => '[PASSWORD REDACTED]',
            '/\b\d{16}\b/' => '[CREDIT CARD REDACTED]',
            '/\b\d{3}-\d{2}-\d{4}\b/' => '[SSN REDACTED]',
        ];

        foreach ($sensitivePatterns as $pattern => $replacement) {
            $content = preg_replace($pattern, $replacement, $content);
        }

        return $content;
    }

    /**
     * Check if content should show warning
     */
    private function shouldShowContentWarning(): bool
    {
        return !empty($this->content_warnings);
    }

    /**
     * Add content warning to text
     */
    private function addContentWarning(string $content): string
    {
        $warning = "\n\n⚠️ Content Warning: " . implode(', ', $this->content_warnings);
        return $content . $warning;
    }

    /**
     * Process rich text content
     */
    public function processRichContent(array $richContent): array
    {
        $processed = [];

        foreach ($richContent as $element) {
            switch ($element['type']) {
                case 'text':
                    $processed[] = $this->processTextElement($element);
                    break;
                case 'mention':
                    $processed[] = $this->processMentionElement($element);
                    break;
                case 'link':
                    $processed[] = $this->processLinkElement($element);
                    break;
                case 'code':
                    $processed[] = $this->processCodeElement($element);
                    break;
                default:
                    $processed[] = $element;
            }
        }

        return $processed;
    }

    /**
     * Process text element in rich content
     */
    private function processTextElement(array $element): array
    {
        $text = $element['content'] ?? '';

        // Filter sensitive content (user parameter will be passed from controller)
        $user = null; // This will be set by the calling controller
        if ($user && !$this->canUserSeeSensitiveContent($user)) {
            $text = $this->removeSensitiveContent($text);
        }

        return array_merge($element, ['content' => $text]);
    }

    /**
     * Process mention element in rich content
     */
    private function processMentionElement(array $element): array
    {
        $userKey = $element['user_key'] ?? '';

        // Verify mentioned user exists and is valid
        if (!$this->isValidMention($userKey)) {
            return [
                'type' => 'text',
                'content' => '@' . ($element['display_name'] ?? 'unknown'),
                'style' => ['color' => '#999']
            ];
        }

        return $element;
    }

    /**
     * Process link element in rich content
     */
    private function processLinkElement(array $element): array
    {
        $url = $element['url'] ?? '';

        // Validate URL
        if (!$this->isValidUrl($url)) {
            return [
                'type' => 'text',
                'content' => $element['display_text'] ?? $url,
                'style' => ['color' => '#999']
            ];
        }

        return $element;
    }

    /**
     * Process code element in rich content
     */
    private function processCodeElement(array $element): array
    {
        $code = $element['content'] ?? '';

        // Basic syntax highlighting for common languages
        if (isset($element['language'])) {
            $code = $this->highlightCode($code, $element['language']);
        }

        return array_merge($element, ['content' => $code]);
    }

    /**
     * Check if mention is valid
     */
    private function isValidMention(string $userKey): bool
    {
        // Check if user exists in any of the user models
        $models = [
            'App\\Models\\User',
            'App\\Models\\Teknisi',
            'App\\Models\\AdminHelpdesk',
            'App\\Models\\AdminAplikasi'
        ];

        foreach ($models as $model) {
            if (class_exists($model)) {
                $user = $model::where('nip', $userKey)->first();
                if ($user) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if URL is valid
     */
    private function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Basic syntax highlighting for code
     */
    private function highlightCode(string $code, string $language): string
    {
        switch ($language) {
            case 'php':
                return $this->highlightPhpCode($code);
            case 'javascript':
                return $this->highlightJavaScriptCode($code);
            case 'sql':
                return $this->highlightSqlCode($code);
            default:
                return htmlspecialchars($code);
        }
    }

    /**
     * Highlight PHP code
     */
    private function highlightPhpCode(string $code): string
    {
        $keywords = ['function', 'class', 'public', 'private', 'protected', 'static', 'return', 'if', 'else', 'foreach', 'while', 'echo', 'print'];
        $highlighted = htmlspecialchars($code);

        foreach ($keywords as $keyword) {
            $highlighted = preg_replace("/\b{$keyword}\b/", '<span style="color: #0000FF;">$0</span>', $highlighted);
        }

        return $highlighted;
    }

    /**
     * Highlight JavaScript code
     */
    private function highlightJavaScriptCode(string $code): string
    {
        $keywords = ['function', 'var', 'let', 'const', 'return', 'if', 'else', 'for', 'while', 'console', 'document'];
        $highlighted = htmlspecialchars($code);

        foreach ($keywords as $keyword) {
            $highlighted = preg_replace("/\b{$keyword}\b/", '<span style="color: #0000FF;">$0</span>', $highlighted);
        }

        return $highlighted;
    }

    /**
     * Highlight SQL code
     */
    private function highlightSqlCode(string $code): string
    {
        $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'INNER', 'LEFT', 'RIGHT', 'GROUP BY', 'ORDER BY', 'INSERT', 'UPDATE', 'DELETE'];
        $highlighted = htmlspecialchars($code);

        foreach ($keywords as $keyword) {
            $highlighted = preg_replace("/\b{$keyword}\b/i", '<span style="color: #0000FF;">$0</span>', $highlighted);
        }

        return $highlighted;
    }

    /**
     * Extract mentions from content
     */
    public static function extractMentions(string $content): array
    {
        $mentions = [];

        // Pattern for @username mentions
        if (preg_match_all('/@(\w+)/', $content, $matches)) {
            $mentions = array_unique($matches[1]);
        }

        return $mentions;
    }

    /**
     * Extract URLs from content
     */
    public static function extractUrls(string $content): array
    {
        $urls = [];

        // Pattern for URLs
        if (preg_match_all('/https?:\/\/[^\s]+/', $content, $matches)) {
            $urls = array_unique($matches[0]);
        }

        return $urls;
    }

    /**
     * Check content for spam
     */
    public function containsSpam(): bool
    {
        $spamIndicators = [
            'excessive_caps' => preg_match('/[A-Z]{20,}/', $this->comment),
            'repeated_text' => preg_match('/(.)\1{10,}/', $this->comment),
            'spam_keywords' => preg_match('/(viagra|casino|lottery|winner|congratulations)/i', $this->comment),
            'excessive_links' => substr_count(strtolower($this->comment), 'http') > 3,
            'excessive_mentions' => substr_count($this->comment, '@') > 5,
        ];

        $spamScore = array_sum($spamIndicators);

        return $spamScore >= 2;
    }

    /**
     * Check content for inappropriate language
     */
    public function containsInappropriateLanguage(): bool
    {
        $inappropriateWords = [
            'spam', 'inappropriate', 'offensive' // Add actual inappropriate words as needed
        ];

        $content = strtolower($this->comment . ' ' . ($this->technical_details ?? ''));

        foreach ($inappropriateWords as $word) {
            if (strpos($content, $word) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze content sentiment
     */
    public function analyzeSentiment(): string
    {
        $positiveWords = ['good', 'excellent', 'great', 'thank', 'resolved', 'working', 'fixed'];
        $negativeWords = ['bad', 'terrible', 'awful', 'broken', 'not working', 'issue', 'problem', 'error'];

        $content = strtolower($this->comment);
        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($positiveWords as $word) {
            $positiveCount += substr_count($content, $word);
        }

        foreach ($negativeWords as $word) {
            $negativeCount += substr_count($content, $word);
        }

        if ($positiveCount > $negativeCount) {
            return 'positive';
        } elseif ($negativeCount > $positiveCount) {
            return 'negative';
        }

        return 'neutral';
    }

    /**
     * Generate content summary
     */
    public function generateSummary(int $maxLength = 150): string
    {
        $content = strip_tags($this->comment);

        if (strlen($content) <= $maxLength) {
            return $content;
        }

        $summary = substr($content, 0, $maxLength);
        $lastSpace = strrpos($summary, ' ');

        if ($lastSpace !== false) {
            $summary = substr($summary, 0, $lastSpace);
        }

        return $summary . '...';
    }

    /**
     * Check if content needs moderation
     */
    public function needsModeration(): bool
    {
        return $this->containsSensitiveContent() ||
               $this->containsSpam() ||
               $this->containsInappropriateLanguage() ||
               strlen($this->comment) > 4000;
    }

    /**
     * Get content quality score (0-100)
     */
    public function getContentQualityScore(): float
    {
        $score = 100;

        // Length check
        $length = strlen($this->comment);
        if ($length < 10) {
            $score -= 30;
        } elseif ($length > 4000) {
            $score -= 20;
        }

        // Spam check
        if ($this->containsSpam()) {
            $score -= 50;
        }

        // Inappropriate content check
        if ($this->containsInappropriateLanguage()) {
            $score -= 40;
        }

        // Sensitive content check
        if ($this->containsSensitiveContent()) {
            $score -= 10;
        }

        // Check for structure (paragraphs, formatting)
        if (strpos($this->comment, "\n") === false) {
            $score -= 5; // No paragraphs
        }

        return max(0, $score);
    }

    /**
     * Format content for different output formats
     */
    public function formatForOutput(string $format = 'html'): string
    {
        $content = $this->comment;

        switch ($format) {
            case 'html':
                return $this->formatAsHtml($content);
            case 'markdown':
                return $this->formatAsMarkdown($content);
            case 'text':
                return $this->formatAsText($content);
            default:
                return $content;
        }
    }

    /**
     * Format content as HTML
     */
    private function formatAsHtml(string $content): string
    {
        // Convert URLs to links
        $content = preg_replace('/https?:\/\/[^\s]+/', '<a href="$0" target="_blank">$0</a>', $content);

        // Convert emails to mailto links
        $content = preg_replace('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', '<a href="mailto:$0">$0</a>', $content);

        // Convert line breaks
        $content = nl2br(e($content));

        // Convert mentions
        $content = preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', $content);

        return $content;
    }

    /**
     * Format content as Markdown
     */
    private function formatAsMarkdown(string $content): string
    {
        // Basic markdown conversion
        $content = preg_replace('/\n/', "\n\n", $content); // Ensure double line breaks
        return $content;
    }

    /**
     * Format content as plain text
     */
    private function formatAsText(string $content): string
    {
        // Strip all HTML and formatting
        return strip_tags($content);
    }

    /**
     * Extract keywords from content
     */
    public function extractKeywords(int $maxKeywords = 10): array
    {
        $content = strip_tags(strtolower($this->comment . ' ' . ($this->technical_details ?? '')));

        // Remove common stop words
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were'];
        $words = str_word_count($content, 1);

        $filteredWords = array_filter($words, function($word) use ($stopWords) {
            return strlen($word) > 3 && !in_array($word, $stopWords);
        });

        // Count word frequency
        $wordCount = array_count_values($filteredWords);

        // Sort by frequency and return top keywords
        arsort($wordCount);

        return array_slice(array_keys($wordCount), 0, $maxKeywords);
    }

    /**
     * Check if content is duplicate
     */
    public static function isDuplicateContent(string $content, ?int $ticketId = null): bool
    {
        $query = self::where('comment', $content);

        if ($ticketId) {
            $query->where('ticket_id', $ticketId);
        }

        return $query->exists();
    }

    /**
     * Get content statistics
     */
    public function getContentStatistics(): array
    {
        $content = $this->comment ?? '';

        return [
            'character_count' => strlen($content),
            'word_count' => str_word_count($content),
            'line_count' => substr_count($content, "\n") + 1,
            'paragraph_count' => substr_count($content, "\n\n") + 1,
            'url_count' => preg_match_all('/https?:\/\/[^\s]+/', $content),
            'mention_count' => preg_match_all('/@\w+/', $content),
            'has_code_blocks' => strpos($content, '```') !== false,
            'has_lists' => preg_match('/^\s*[\*\-\+]\s|^\s*\d+\.\s/', $content),
            'sentiment' => $this->analyzeSentiment(),
            'quality_score' => $this->getContentQualityScore(),
            'keywords' => $this->extractKeywords(),
        ];
    }

    // ==================== AUDIT TRAIL ====================

    /**
     * Create audit trail entry for comment action
     */
    private function createAuditTrail(string $action, $performedBy = null, array $metadata = []): void
    {
        try {
            // Create audit trail entry
            $auditData = [
                'ticket_id' => $this->ticket_id,
                'comment_id' => $this->id,
                'action' => $action,
                'performed_by_nip' => $performedBy?->nip ?? $performedBy?->id ?? null,
                'performed_by_type' => $performedBy ? get_class($performedBy) : null,
                'old_values' => $metadata['old_values'] ?? null,
                'new_values' => $metadata['new_values'] ?? null,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => array_merge($metadata, [
                    'comment_type' => $this->type,
                    'visibility' => $this->visibility,
                    'is_internal' => $this->is_internal,
                ]),
                'created_at' => Carbon::now(),
            ];

            // Store in comment history or log file
            \Illuminate\Support\Facades\Log::info("Comment audit: {$action}", $auditData);

            // You could also store in a dedicated audit table:
            // CommentAudit::create($auditData);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to create comment audit trail: ' . $e->getMessage());
        }
    }

    /**
     * Track comment creation
     */
    protected static function boot()
    {
        parent::boot();

        // Track creation
        static::created(function ($comment) {
            $comment->createAuditTrail('created', $comment->commenter, [
                'new_values' => [
                    'comment' => $comment->comment,
                    'type' => $comment->type,
                    'visibility' => $comment->visibility,
                    'is_internal' => $comment->is_internal,
                ]
            ]);
        });

        // Track updates
        static::updating(function ($comment) {
            $changes = [];

            // Track specific field changes
            $trackedFields = [
                'comment', 'type', 'visibility', 'is_internal',
                'moderation_status', 'requires_response', 'is_pinned'
            ];

            foreach ($trackedFields as $field) {
                if ($comment->isDirty($field)) {
                    $changes[$field] = [
                        'old' => $comment->getOriginal($field),
                        'new' => $comment->$field,
                    ];
                }
            }

            if (!empty($changes)) {
                // Get current user from request if available
                $currentUser = request()->user();
                $comment->createAuditTrail('updated', $currentUser, [
                    'old_values' => array_column($changes, 'old'),
                    'new_values' => array_column($changes, 'new'),
                    'changed_fields' => array_keys($changes),
                ]);
            }
        });

        // Track deletion
        static::deleting(function ($comment) {
            // Get current user from request if available
            $currentUser = request()->user();
            $comment->createAuditTrail('deleted', $currentUser, [
                'old_values' => [
                    'comment' => $comment->comment,
                    'type' => $comment->type,
                    'visibility' => $comment->visibility,
                    'is_internal' => $comment->is_internal,
                ]
            ]);
        });
    }

    /**
     * Override save method to track specific actions
     */
    public function save(array $options = [])
    {
        $isUpdate = $this->exists;
        $wasApproved = $this->getOriginal('moderation_status') === self::MODERATION_APPROVED;
        $nowApproved = $this->moderation_status === self::MODERATION_APPROVED;

        $result = parent::save($options);

        // Track approval action
        if ($isUpdate && !$wasApproved && $nowApproved) {
            $this->createAuditTrail('approved', $this->moderator, [
                'new_values' => ['moderation_status' => self::MODERATION_APPROVED]
            ]);
        }

        // Track rejection action
        if ($isUpdate && $wasApproved && $this->moderation_status === self::MODERATION_REJECTED) {
            $this->createAuditTrail('rejected', $this->moderator, [
                'old_values' => ['moderation_status' => self::MODERATION_APPROVED],
                'new_values' => ['moderation_status' => self::MODERATION_REJECTED],
                'rejection_reason' => $this->metadata['rejection_reason'] ?? null,
            ]);
        }

        // Track flag action
        if ($isUpdate && $this->getOriginal('moderation_status') !== self::MODERATION_FLAGGED &&
            $this->moderation_status === self::MODERATION_FLAGGED) {
            $this->createAuditTrail('flagged', $this->moderator, [
                'new_values' => ['moderation_status' => self::MODERATION_FLAGGED],
                'flag_reason' => $this->metadata['flag_reason'] ?? null,
            ]);
        }

        // Track pin action
        if ($isUpdate && $this->isDirty('is_pinned')) {
            $action = $this->is_pinned ? 'pinned' : 'unpinned';
            $this->createAuditTrail($action, $this->pinner);
        }

        // Track edit action
        if ($isUpdate && $this->isDirty('comment') && $this->edited_at) {
            $this->createAuditTrail('edited', $this->editor, [
                'old_values' => ['comment' => $this->getOriginal('comment')],
                'new_values' => ['comment' => $this->comment],
            ]);
        }

        return $result;
    }

    /**
     * Get comment history/audit trail
     */
    public function getAuditTrail(): array
    {
        // This would typically query a dedicated audit table
        // For now, return a simulated audit trail based on comment data

        $auditTrail = [];

        // Creation event
        $auditTrail[] = [
            'id' => 1,
            'action' => 'created',
            'performed_by' => $this->commenter_name,
            'performed_by_type' => $this->commenter_type,
            'timestamp' => $this->created_at,
            'metadata' => [
                'comment_type' => $this->type,
                'visibility' => $this->visibility,
                'is_internal' => $this->is_internal,
            ]
        ];

        // Edit events
        if ($this->edited_at) {
            $auditTrail[] = [
                'id' => 2,
                'action' => 'edited',
                'performed_by' => $this->editor ? $this->editor->name : 'Unknown',
                'performed_by_type' => $this->editor ? get_class($this->editor) : null,
                'timestamp' => $this->edited_at,
                'metadata' => [
                    'changes' => 'Comment content was modified',
                ]
            ];
        }

        // Moderation events
        if ($this->moderated_at) {
            $auditTrail[] = [
                'id' => 3,
                'action' => 'moderated',
                'performed_by' => $this->moderator ? $this->moderator->name : 'Unknown',
                'performed_by_type' => $this->moderator ? get_class($this->moderator) : null,
                'timestamp' => $this->moderated_at,
                'metadata' => [
                    'moderation_status' => $this->moderation_status,
                    'reason' => $this->metadata['rejection_reason'] ?? $this->metadata['flag_reason'] ?? null,
                ]
            ];
        }

        // Pin events
        if ($this->pinned_at) {
            $auditTrail[] = [
                'id' => 4,
                'action' => $this->is_pinned ? 'pinned' : 'unpinned',
                'performed_by' => $this->pinner ? $this->pinner->name : 'Unknown',
                'performed_by_type' => $this->pinner ? get_class($this->pinner) : null,
                'timestamp' => $this->pinned_at,
                'metadata' => []
            ];
        }

        // Sort by timestamp
        usort($auditTrail, function($a, $b) {
            return $a['timestamp']->timestamp <=> $b['timestamp']->timestamp;
        });

        return $auditTrail;
    }

    /**
     * Get comment changes over time
     */
    public function getChangeHistory(): array
    {
        $changes = [];

        // Comment content changes
        if ($this->edited_at) {
            $changes[] = [
                'field' => 'comment',
                'type' => 'content',
                'timestamp' => $this->edited_at,
                'description' => 'Comment content was edited',
            ];
        }

        // Status changes
        if ($this->responded_at) {
            $changes[] = [
                'field' => 'status',
                'type' => 'response',
                'timestamp' => $this->responded_at,
                'description' => 'Comment was marked as responded',
            ];
        }

        // Moderation changes
        if ($this->moderated_at) {
            $changes[] = [
                'field' => 'moderation',
                'type' => 'moderation',
                'timestamp' => $this->moderated_at,
                'description' => "Comment was {$this->moderation_status}",
            ];
        }

        // Pin changes
        if ($this->pinned_at) {
            $changes[] = [
                'field' => 'visibility',
                'type' => 'pin',
                'timestamp' => $this->pinned_at,
                'description' => $this->is_pinned ? 'Comment was pinned' : 'Comment was unpinned',
            ];
        }

        // Sort by timestamp
        usort($changes, function($a, $b) {
            return $a['timestamp']->timestamp <=> $b['timestamp']->timestamp;
        });

        return $changes;
    }

    /**
     * Get moderation history
     */
    public function getModerationHistory(): array
    {
        $history = [];

        if (!$this->moderated_at) {
            return $history;
        }

        $history[] = [
            'moderator' => $this->moderator ? $this->moderator->name : 'Unknown',
            'moderator_type' => $this->moderator ? get_class($this->moderator) : null,
            'action' => $this->moderation_status,
            'timestamp' => $this->moderated_at,
            'reason' => $this->metadata['rejection_reason'] ?? $this->metadata['flag_reason'] ?? null,
            'ip_address' => $this->metadata['ip_address'] ?? null,
        ];

        return $history;
    }

    /**
     * Get edit history with content diff
     */
    public function getEditHistory(): array
    {
        $history = [];

        if (!$this->edited_at) {
            return $history;
        }

        // This would typically come from a version history table
        // For now, simulate based on available data
        $history[] = [
            'editor' => $this->editor ? $this->editor->name : 'Unknown',
            'editor_type' => $this->editor ? get_class($this->editor) : null,
            'timestamp' => $this->edited_at,
            'changes' => 'Comment content was modified',
            'content_diff' => [
                'type' => 'modified',
                'description' => 'Comment text was changed',
            ]
        ];

        return $history;
    }

    /**
     * Track comment view/read
     */
    public function trackView($user): void
    {
        if (!$user) {
            return;
        }

        $userKey = $user->nip ?? $user->id;

        // Update read_by tracking
        $readBy = $this->read_by ?? [];
        if (!in_array($userKey, $readBy)) {
            $readBy[] = $userKey;
            $this->read_by = $readBy;
            $this->save();

            // Create audit entry for view
            $this->createAuditTrail('viewed', $user, [
                'metadata' => ['tracking' => 'comment_view']
            ]);
        }
    }

    /**
     * Get comment statistics for audit purposes
     */
    public function getAuditStatistics(): array
    {
        return [
            'comment_id' => $this->id,
            'ticket_id' => $this->ticket_id,
            'created_at' => $this->created_at,
            'last_edited' => $this->edited_at,
            'last_moderated' => $this->moderated_at,
            'edit_count' => $this->edited_at ? 1 : 0,
            'moderation_count' => $this->moderated_at ? 1 : 0,
            'view_count' => count($this->read_by ?? []),
            'reply_count' => $this->replies()->count(),
            'attachment_count' => $this->attachments_count,
            'helpful_votes' => $this->helpful_votes ?? 0,
            'content_quality_score' => $this->getContentQualityScore(),
            'sentiment' => $this->analyzeSentiment(),
        ];
    }

    /**
     * Generate compliance report for comment
     */
    public function generateComplianceReport(): array
    {
        return [
            'comment_id' => $this->id,
            'content_compliance' => [
                'contains_sensitive_content' => $this->containsSensitiveContent(),
                'contains_spam' => $this->containsSpam(),
                'contains_inappropriate_language' => $this->containsInappropriateLanguage(),
                'quality_score' => $this->getContentQualityScore(),
                'needs_moderation' => $this->needsModeration(),
            ],
            'access_compliance' => [
                'visibility_level' => $this->visibility,
                'is_internal' => $this->is_internal,
                'moderation_status' => $this->moderation_status,
                'read_count' => count($this->read_by ?? []),
            ],
            'audit_compliance' => [
                'has_edit_history' => !is_null($this->edited_at),
                'has_moderation_history' => !is_null($this->moderated_at),
                'has_audit_trail' => true,
                'retention_compliant' => true,
            ],
            'generated_at' => Carbon::now(),
        ];
    }

    /**
     * Archive comment for compliance
     */
    public function archiveForCompliance(): bool
    {
        try {
            // Create comprehensive archive record
            $archiveData = [
                'comment_data' => $this->toArray(),
                'audit_trail' => $this->getAuditTrail(),
                'content_statistics' => $this->getContentStatistics(),
                'compliance_report' => $this->generateComplianceReport(),
                'archived_at' => Carbon::now(),
                'archived_by' => request()->user()?->nip ?? request()->user()?->id ?? null,
            ];

            // Store archive (could be in a separate table or file)
            \Illuminate\Support\Facades\Storage::put(
                "comment-archives/comment_{$this->id}_archive.json",
                json_encode($archiveData, JSON_PRETTY_PRINT)
            );

            // Get current user from request if available
            $currentUser = request()->user();
            $this->createAuditTrail('archived', $currentUser, [
                'metadata' => ['compliance_archive' => true]
            ]);

            return true;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to archive comment: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get data retention information
     */
    public function getRetentionInfo(): array
    {
        $created = $this->created_at;
        $retentionDays = 2555; // 7 years default
        $expiryDate = $created->copy()->addDays($retentionDays);
        $daysUntilExpiry = max(0, Carbon::now()->diffInDays($expiryDate, false));

        return [
            'created_at' => $created,
            'retention_period_days' => $retentionDays,
            'expiry_date' => $expiryDate,
            'days_until_expiry' => $daysUntilExpiry,
            'is_expired' => $expiryDate->isPast(),
            'archive_required' => $daysUntilExpiry <= 30, // Archive when 30 days left
        ];
    }
}
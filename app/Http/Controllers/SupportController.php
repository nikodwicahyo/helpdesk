<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Ticket;
use App\Models\Aplikasi;
use App\Models\KategoriMasalah;
use App\Models\AdminHelpdesk;

class SupportController extends Controller
{
    /**
     * Display the FAQ page.
     */
    public function faq(Request $request)
    {
        $user = Auth::user();

        // Get filter parameters
        $filters = $request->only(['aplikasi_id', 'kategori_masalah_id', 'search']);

        // Get frequently asked questions from resolved tickets
        $faqs = $this->getFrequentlyAskedQuestions($filters);

        // Get quick solutions for common issues
        $quickSolutions = $this->getQuickSolutions();

        // Get application-specific help
        $applicationHelp = $this->getApplicationHelp();

        // Get contact information
        $contactInfo = $this->getContactInformation();

        return Inertia::render('Support/FAQ', [
            'faqs' => $faqs,
            'quickSolutions' => $quickSolutions,
            'applicationHelp' => $applicationHelp,
            'contactInfo' => $contactInfo,
            'filters' => $filters,
        ]);
    }

    /**
     * Display the contact support page.
     */
    public function contact(Request $request)
    {
        /** @var User $user */
        $user = Auth::user();

        // Get user's recent tickets for context
        $recentTickets = $user->tickets()
            ->with(['aplikasi', 'kategoriMasalah'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'aplikasi' => $ticket->aplikasi ? $ticket->aplikasi->name : 'Unknown',
                    'created_at' => $ticket->created_at,
                    'formatted_created_at' => $ticket->created_at->diffForHumans(),
                ];
            });

        // Get available applications for contact form
        $applications = Aplikasi::active()
            ->orderBy('name')
            ->get(['id', 'name', 'code'])
            ->map(function ($app) {
                return [
                    'value' => $app->id,
                    'label' => $app->name . ' (' . $app->code . ')',
                ];
            });

        // Get available categories for contact form
        $categories = KategoriMasalah::active()
            ->with('aplikasi:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'aplikasi_id'])
            ->map(function ($cat) {
                return [
                    'value' => $cat->id,
                    'label' => $cat->name . ($cat->aplikasi ? ' - ' . $cat->aplikasi->name : ''),
                ];
            });

        // Get support contact information
        $supportContacts = $this->getSupportContacts();

        return Inertia::render('Support/Contact', [
            'recentTickets' => $recentTickets,
            'applications' => $applications,
            'categories' => $categories,
            'supportContacts' => $supportContacts,
        ]);
    }

    /**
     * Submit a support request.
     */
    public function submitRequest(Request $request)
    {
        $user = Auth::user();

        // Validate request data
        $validator = Validator::make($request->all(), [
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
            'priority' => 'required|in:low,medium,high,urgent',
            'aplikasi_id' => 'nullable|exists:aplikasis,id',
            'kategori_masalah_id' => 'nullable|exists:kategori_masalahs,id',
            'urgency_reason' => 'required_if:priority,urgent|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()->all(),
            ], 422);
        }

        try {
            // Create support ticket
            $ticket = Ticket::create([
                'ticket_number' => $this->generateTicketNumber(),
                'title' => $request->subject,
                'description' => $request->message,
                'priority' => $request->priority,
                'status' => Ticket::STATUS_OPEN,
                'user_nip' => $user->nip,
                'aplikasi_id' => $request->aplikasi_id,
                'kategori_masalah_id' => $request->kategori_masalah_id,
                'source' => 'support_form',
            ]);

            // Send notification to support team
            $this->notifySupportTeam($ticket);

            return response()->json([
                'success' => true,
                'message' => 'Support request submitted successfully',
                'ticket' => [
                    'id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'errors' => ['Failed to submit support request: ' . $e->getMessage()],
            ], 500);
        }
    }

    /**
     * Get frequently asked questions from resolved tickets.
     */
    private function getFrequentlyAskedQuestions(array $filters, int $limit = 20): array
    {
        $query = Ticket::where('status', Ticket::STATUS_RESOLVED)
            ->whereNotNull('solution')
            ->where('solution', '!=', '')
            ->with(['aplikasi', 'kategoriMasalah', 'user']);

        // Apply filters
        if (!empty($filters['aplikasi_id'])) {
            $query->where('aplikasi_id', $filters['aplikasi_id']);
        }

        if (!empty($filters['kategori_masalah_id'])) {
            $query->where('kategori_masalah_id', $filters['kategori_masalah_id']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%")
                  ->orWhere('solution', 'like', "%{$filters['search']}%");
            });
        }

        return $query->latest('resolved_at')
            ->limit($limit)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => $ticket->id,
                    'question' => $ticket->title,
                    'answer' => $ticket->solution,
                    'category' => $ticket->kategoriMasalah ? $ticket->kategoriMasalah->name : 'General',
                    'application' => $ticket->aplikasi ? $ticket->aplikasi->name : 'General',
                    'resolved_at' => $ticket->resolved_at,
                    'formatted_resolved_at' => $ticket->resolved_at ? $ticket->resolved_at->diffForHumans() : null,
                    'helpful_count' => rand(5, 50), // Placeholder for voting system
                ];
            })
            ->toArray();
    }

    /**
     * Get quick solutions for common issues.
     */
    private function getQuickSolutions(): array
    {
        return [
            [
                'id' => 1,
                'title' => 'How to reset my password?',
                'solution' => 'You can reset your password by clicking the "Forgot Password" link on the login page. An email will be sent to your registered email address with reset instructions.',
                'category' => 'Account',
                'estimated_time' => '2 minutes',
            ],
            [
                'id' => 2,
                'title' => 'How to create a support ticket?',
                'solution' => 'Navigate to the "Create Ticket" page, select the relevant application and category, provide a detailed description of your issue, and submit. Our support team will respond promptly.',
                'category' => 'Tickets',
                'estimated_time' => '5 minutes',
            ],
            [
                'id' => 3,
                'title' => 'How to check ticket status?',
                'solution' => 'Go to your dashboard and click on "My Tickets" to view all your submitted tickets and their current status. You can also click on individual tickets for detailed information.',
                'category' => 'Tickets',
                'estimated_time' => '1 minute',
            ],
            [
                'id' => 4,
                'title' => 'What applications are supported?',
                'solution' => 'We support various applications including HR systems, financial systems, document management systems, and custom business applications. Check the applications list for complete details.',
                'category' => 'Applications',
                'estimated_time' => '3 minutes',
            ],
        ];
    }

    /**
     * Get application-specific help.
     */
    private function getApplicationHelp(): array
    {
        return Aplikasi::active()
            ->withCount('tickets')
            ->orderBy('tickets_count', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($application) {
                $commonIssues = Ticket::where('aplikasi_id', $application->id)
                    ->where('status', Ticket::STATUS_RESOLVED)
                    ->whereNotNull('solution')
                    ->inRandomOrder()
                    ->limit(3)
                    ->get(['title', 'solution']);

                return [
                    'id' => $application->id,
                    'name' => $application->name,
                    'code' => $application->code,
                    'description' => $application->description,
                    'common_issues' => $commonIssues->map(function ($ticket) {
                        return [
                            'question' => $ticket->title,
                            'solution' => $ticket->solution,
                        ];
                    })->toArray(),
                ];
            })
            ->toArray();
    }

    /**
     * Get contact information.
     */
    private function getContactInformation(): array
    {
        return [
            'phone' => '+62-21-1234-5678',
            'email' => 'helpdesk@kemlu.go.id',
            'hours' => 'Monday - Friday: 8:00 AM - 5:00 PM WIB',
            'emergency' => 'For urgent issues outside business hours, call: +62-21-9876-5432',
            'address' => 'Ministry of Foreign Affairs, Jl. Pejambon No. 6, Jakarta Pusat',
        ];
    }

    /**
     * Get support contacts.
     */
    private function getSupportContacts(): array
    {
        return AdminHelpdesk::active()
            ->orderBy('name')
            ->get(['nip', 'name', 'email', 'phone'])
            ->map(function ($admin) {
                return [
                    'nip' => $admin->nip,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'phone' => $admin->phone,
                    'available' => rand(true, false), // Placeholder for availability status
                ];
            })
            ->toArray();
    }

    /**
     * Generate unique ticket number.
     */
    private function generateTicketNumber(): string
    {
        $date = now()->format('Ymd');
        $random = strtoupper(substr(md5(microtime()), 0, 4));

        return "TK-{$date}-{$random}";
    }

    /**
     * Notify support team about new ticket.
     */
    private function notifySupportTeam(Ticket $ticket): void
    {
        try {
            // Get support team members
            $supportTeam = AdminHelpdesk::active()->get();

            foreach ($supportTeam as $admin) {
                // Send email notification (you would implement this based on your mail configuration)
                // Mail::to($admin->email)->send(new NewSupportTicket($ticket, $admin));

                // Create in-app notification
                $admin->notifications()->create([
                    'type' => 'new_support_ticket',
                    'title' => 'New Support Ticket',
                    'message' => "New ticket #{$ticket->ticket_number}: {$ticket->title}",
                    'data' => [
                        'ticket_id' => $ticket->id,
                        'ticket_number' => $ticket->ticket_number,
                        'user_name' => $ticket->user->name,
                    ],
                ]);
            }
        } catch (\Exception $e) {
            // Log error but don't fail the ticket creation
            \Illuminate\Support\Facades\Log::error('Failed to notify support team', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
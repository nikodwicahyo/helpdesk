<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;
use App\Models\Ticket;

class TicketHandlingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only teknisi can handle tickets
        $authService = app(AuthService::class);
        $user = Auth::user();
        return $user && $authService->getUserRole($user) === 'teknisi';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        // Rules for status update
        if ($this->is('*/tickets/*/update-status')) {
            $rules = array_merge($rules, [
                'status' => 'required|in:' . implode(',', [
                    Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE,
                    Ticket::STATUS_RESOLVED
                ]),
                'notes' => 'nullable|string|max:500',
            ]);
        }

        // Rules for ticket resolution
        if ($this->is('*/tickets/*/resolve')) {
            $rules = array_merge($rules, [
                'resolution_notes' => 'required|string|max:2000|min:10',
                'technical_notes' => 'nullable|string|max:1000',
                'solution_summary' => 'required|string|max:500|min:10',
                'files.*' => 'nullable|file|max:2048|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
            ]);
        }

        // Rules for ticket reassignment
        if ($this->is('*/tickets/*/reassign')) {
            $rules = array_merge($rules, [
                'new_teknisi_nip' => 'required|string|exists:teknisis,nip|different:' . Auth::user()->nip,
                'reason' => 'required|string|max:500|min:10',
            ]);
        }

        // Rules for adding technical notes
        if ($this->is('*/tickets/*/technical-notes')) {
            $rules = array_merge($rules, [
                'technical_note' => 'required|string|max:1000|min:5',
                'is_internal' => 'boolean',
                'files.*' => 'nullable|file|max:2048|mimes:jpeg,png,gif,webp,pdf,doc,docx,txt',
            ]);
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status selected',
            'resolution_notes.required' => 'Resolution notes are required',
            'resolution_notes.min' => 'Resolution notes must be at least 10 characters long',
            'resolution_notes.max' => 'Resolution notes cannot exceed 2000 characters',
            'technical_notes.max' => 'Technical notes cannot exceed 1000 characters',
            'solution_summary.required' => 'Solution summary is required',
            'solution_summary.min' => 'Solution summary must be at least 10 characters long',
            'solution_summary.max' => 'Solution summary cannot exceed 500 characters',
            'new_teknisi_nip.required' => 'New technician selection is required',
            'new_teknisi_nip.exists' => 'Selected technician does not exist',
            'new_teknisi_nip.different' => 'Cannot reassign to the same technician',
            'reason.required' => 'Reason for reassignment is required',
            'reason.min' => 'Reason must be at least 10 characters long',
            'reason.max' => 'Reason cannot exceed 500 characters',
            'technical_note.required' => 'Technical note is required',
            'technical_note.min' => 'Technical note must be at least 5 characters long',
            'technical_note.max' => 'Technical note cannot exceed 1000 characters',
            'files.*.file' => 'Each attachment must be a valid file',
            'files.*.max' => 'Each file cannot exceed 2MB',
            'files.*.mimes' => 'Files must be of type: jpeg, png, gif, webp, pdf, doc, docx, txt',
            'notes.max' => 'Notes cannot exceed 500 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'ticket status',
            'resolution_notes' => 'resolution notes',
            'technical_notes' => 'technical notes',
            'solution_summary' => 'solution summary',
            'new_teknisi_nip' => 'new technician',
            'reason' => 'reason for reassignment',
            'technical_note' => 'technical note',
            'is_internal' => 'internal note flag',
            'files' => 'attachments',
            'files.*' => 'attachment',
            'notes' => 'additional notes',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Clean and format input data
        $this->merge([
            'new_teknisi_nip' => trim(strtoupper($this->new_teknisi_nip)),
            'reason' => trim($this->reason),
            'technical_note' => trim($this->technical_note),
            'resolution_notes' => trim($this->resolution_notes),
            'technical_notes' => trim($this->technical_notes),
            'solution_summary' => trim($this->solution_summary),
            'notes' => trim($this->notes),
            'is_internal' => $this->boolean('is_internal', true),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic

            // Check if teknisi is active for reassignment
            if ($this->filled('new_teknisi_nip')) {
                $teknisi = \App\Models\Teknisi::where('nip', $this->new_teknisi_nip)->first();
                if ($teknisi && $teknisi->status !== 'active') {
                    $validator->errors()->add('new_teknisi_nip', 'Selected technician is not active');
                }
            }

            // Check if ticket exists and is assigned to current teknisi for status updates
            if ($this->is('*/tickets/*/update-status') && $this->route('ticket')) {
                $ticket = Ticket::find($this->route('ticket'));
                if ($ticket && $ticket->assigned_teknisi_nip !== Auth::user()->nip) {
                    $validator->errors()->add('ticket', 'You are not assigned to this ticket');
                }
            }

            // Check if ticket exists and is assigned to current teknisi for resolution
            if ($this->is('*/tickets/*/resolve') && $this->route('ticket')) {
                $ticket = Ticket::find($this->route('ticket'));
                if ($ticket && $ticket->assigned_teknisi_nip !== Auth::user()->nip) {
                    $validator->errors()->add('ticket', 'You are not assigned to this ticket');
                }
                if ($ticket && !in_array($ticket->status, [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_WAITING_RESPONSE])) {
                    $validator->errors()->add('ticket', 'Only tickets in progress or waiting response can be resolved');
                }
            }

            // Check if ticket exists and is assigned to current teknisi for reassignment
            if ($this->is('*/tickets/*/reassign') && $this->route('ticket')) {
                $ticket = Ticket::find($this->route('ticket'));
                if ($ticket && $ticket->assigned_teknisi_nip !== Auth::user()->nip) {
                    $validator->errors()->add('ticket', 'You are not assigned to this ticket');
                }
            }
        });
    }
}
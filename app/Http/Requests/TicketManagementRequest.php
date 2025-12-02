<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\AuthService;
use App\Models\Ticket;

class TicketManagementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admin helpdesk can manage tickets
        $authService = app(AuthService::class);
        $user = Auth::user();
        return $user && $authService->getUserRole($user) === 'admin_helpdesk';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [];

        // Common rules for ticket assignment
        if ($this->is('*/tickets/*/assign') || $this->is('*/tickets/bulk-assign')) {
            $rules = array_merge($rules, [
                'teknisi_nip' => 'required|string|exists:teknisis,nip',
                'notes' => 'nullable|string|max:500',
            ]);
        }

        // Rules for priority update
        if ($this->is('*/tickets/*/update-priority')) {
            $rules = array_merge($rules, [
                'priority' => 'required|in:' . implode(',', [
                    Ticket::PRIORITY_LOW, Ticket::PRIORITY_MEDIUM,
                    Ticket::PRIORITY_HIGH, Ticket::PRIORITY_URGENT
                ]),
                'reason' => 'nullable|string|max:500',
            ]);
        }

        // Rules for bulk status update
        if ($this->is('*/tickets/bulk-update-status')) {
            $rules = array_merge($rules, [
                'ticket_ids' => 'required|array|min:1',
                'ticket_ids.*' => 'exists:tickets,id',
                'status' => 'required|in:' . implode(',', [
                    Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS,
                    Ticket::STATUS_WAITING_RESPONSE, Ticket::STATUS_RESOLVED,
                    Ticket::STATUS_CLOSED, 'waiting_response'
                ]),
                'notes' => 'nullable|string|max:500',
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
            'teknisi_nip.required' => 'Technician selection is required',
            'teknisi_nip.exists' => 'Selected technician does not exist',
            'priority.required' => 'Priority level is required',
            'priority.in' => 'Invalid priority level selected',
            'reason.string' => 'Reason must be a valid text',
            'ticket_ids.required' => 'No tickets selected',
            'ticket_ids.array' => 'Invalid ticket selection format',
            'ticket_ids.min' => 'At least one ticket must be selected',
            'ticket_ids.*.exists' => 'One or more selected tickets do not exist',
            'status.required' => 'Status is required',
            'status.in' => 'Invalid status selected',
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
            'teknisi_nip' => 'technician',
            'ticket_ids' => 'selected tickets',
            'ticket_ids.*' => 'ticket',
            'priority' => 'priority level',
            'status' => 'ticket status',
            'reason' => 'reason for change',
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
            'teknisi_nip' => trim(strtoupper($this->teknisi_nip)),
            'reason' => trim($this->reason),
            'notes' => trim($this->notes),
        ]);

        // Ensure ticket_ids is an array
        if ($this->has('ticket_ids') && !is_array($this->ticket_ids)) {
            $this->merge([
                'ticket_ids' => array_map('trim', explode(',', $this->ticket_ids))
            ]);
        }

        // Convert frontend alias to backend status
        if ($this->has('status') && $this->status === 'waiting_response') {
            $this->merge(['status' => \App\Models\Ticket::STATUS_WAITING_USER]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Additional validation logic can be added here
            // For example, check if teknisi is active
            if ($this->filled('teknisi_nip')) {
                $teknisi = \App\Models\Teknisi::where('nip', $this->teknisi_nip)->first();
                if ($teknisi && $teknisi->status !== 'active') {
                    $validator->errors()->add('teknisi_nip', 'Selected technician is not active');
                }
            }
        });
    }
}
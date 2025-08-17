<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MembershipPlan;
use App\Models\Member;
use App\Models\Payment;
use App\Models\MembershipPeriod;
use Carbon\Carbon;

class MembershipController extends Controller
{
    /**
     * Display the membership plans configuration page
     */
    public function index()
    {
        $plans = MembershipPlan::all();
        $planTypes = config('membership.plan_types');
        $durationTypes = config('membership.duration_types');
        
        return view('membership.plans.index', compact('plans', 'planTypes', 'durationTypes'));
    }

    /**
     * Display the member plan management page
     */
    public function manageMember()
    {
        $members = Member::all();
        $planTypes = config('membership.plan_types');
        $durationTypes = config('membership.duration_types');
        
        return view('membership.manage-member', compact('members', 'planTypes', 'durationTypes'));
    }

    /**
     * Calculate membership price based on plan type and duration
     */
    public function calculatePrice(Request $request)
    {
        $request->validate([
            'plan_type' => 'required|string',
            'duration_type' => 'required|string',
        ]);

        $planType = $request->plan_type;
        $durationType = $request->duration_type;

        $planTypes = config('membership.plan_types');
        $durationTypes = config('membership.duration_types');

        if (!isset($planTypes[$planType]) || !isset($durationTypes[$durationType])) {
            return response()->json(['error' => 'Invalid plan type or duration'], 400);
        }

        $basePrice = $planTypes[$planType]['base_price'];
        $multiplier = $durationTypes[$durationType]['multiplier'];
        $totalPrice = $basePrice * $multiplier;

        return response()->json([
            'base_price' => $basePrice,
            'multiplier' => $multiplier,
            'total_price' => $totalPrice,
            'duration_days' => $durationTypes[$durationType]['days']
        ]);
    }

    /**
     * Process payment and create membership period
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:members,id',
            'plan_type' => 'required|string',
            'duration_type' => 'required|string',
            'amount' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            // Calculate expiration date
            $startDate = Carbon::parse($request->start_date);
            $durationTypes = config('membership.duration_types');
            $durationDays = $durationTypes[$request->duration_type]['days'];
            $expirationDate = $startDate->copy()->addDays($durationDays);

            // Create payment record
            $payment = Payment::create([
                'member_id' => $request->member_id,
                'amount' => $request->amount,
                'payment_date' => now()->toDateString(),
                'payment_time' => now()->toTimeString(),
                'status' => 'completed',
                'plan_type' => $request->plan_type,
                'duration_type' => $request->duration_type,
                'membership_start_date' => $startDate,
                'membership_expiration_date' => $expirationDate,
                'notes' => $request->notes,
            ]);

            // Create membership period
            $membershipPeriod = MembershipPeriod::create([
                'member_id' => $request->member_id,
                'payment_id' => $payment->id,
                'plan_type' => $request->plan_type,
                'duration_type' => $request->duration_type,
                'start_date' => $startDate,
                'expiration_date' => $expirationDate,
                'status' => 'active',
                'notes' => $request->notes,
            ]);

            // Update member's current membership
            $member = Member::find($request->member_id);
            $member->update([
                'current_membership_period_id' => $membershipPeriod->id,
                'membership_starts_at' => $startDate,
                'current_plan_type' => $request->plan_type,
                'current_duration_type' => $request->duration_type,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Payment processed and membership activated successfully',
                'payment_id' => $payment->id,
                'membership_period_id' => $membershipPeriod->id,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing payment: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display all payments with filtering
     */
    public function payments(Request $request)
    {
        $query = Payment::with('member');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('plan_type')) {
            $query->where('plan_type', $request->plan_type);
        }

        if ($request->filled('date')) {
            $query->where('payment_date', $request->date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('member', function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('member_number', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')->paginate(20);

        return view('membership.payments.index', compact('payments'));
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(Request $request, Payment $payment)
    {
        $request->validate([
            'status' => 'required|in:completed,failed,pending'
        ]);

        try {
            $oldStatus = $payment->status;
            $newStatus = $request->status;

            // Update payment status
            $payment->update(['status' => $newStatus]);

            // If completing a payment, activate the membership
            if ($oldStatus === 'pending' && $newStatus === 'completed') {
                $this->activateMembership($payment);
            }

            // If failing a payment, deactivate the membership
            if ($oldStatus === 'pending' && $newStatus === 'failed') {
                $this->deactivateMembership($payment);
            }

            return response()->json([
                'success' => true,
                'message' => "Payment status updated to {$newStatus} successfully"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating payment status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get payment details for modal
     */
    public function getPaymentDetails(Payment $payment)
    {
        $payment->load(['member', 'membershipPeriod']);
        
        return response()->json([
            'success' => true,
            'payment' => $payment,
            'membership_period' => $payment->membershipPeriod
        ]);
    }

    /**
     * Activate membership for a completed payment
     */
    private function activateMembership(Payment $payment)
    {
        // Find or create membership period
        $membershipPeriod = MembershipPeriod::where('payment_id', $payment->id)->first();
        
        if ($membershipPeriod) {
            $membershipPeriod->update(['status' => 'active']);
            
            // Update member's current membership
            $member = $payment->member;
            $member->update([
                'current_membership_period_id' => $membershipPeriod->id,
                'membership_starts_at' => $payment->membership_start_date,
                'current_plan_type' => $payment->plan_type,
                'current_duration_type' => $payment->duration_type,
                'status' => 'active',
            ]);
        }
    }

    /**
     * Deactivate membership for a failed payment
     */
    private function deactivateMembership(Payment $payment)
    {
        // Find membership period and mark as cancelled
        $membershipPeriod = MembershipPeriod::where('payment_id', $payment->id)->first();
        
        if ($membershipPeriod) {
            $membershipPeriod->update(['status' => 'cancelled']);
            
            // Clear member's current membership if this was their active one
            $member = $payment->member;
            if ($member->current_membership_period_id === $membershipPeriod->id) {
                $member->update([
                    'current_membership_period_id' => null,
                    'membership_starts_at' => null,
                    'current_plan_type' => null,
                    'current_duration_type' => null,
                    'status' => 'inactive',
                ]);
            }
        }
    }
}

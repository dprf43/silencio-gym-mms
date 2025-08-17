<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\MembershipPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $members = Member::orderBy('created_at', 'desc')->get();
        return view('members.list', compact('members'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('members.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'uid' => 'required|string|unique:members,uid',
            'member_number' => 'required|string|unique:members,member_number',
            'membership' => 'required|in:basic,premium,vip',
            'full_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'email' => 'required|email|unique:members,email',
        ]);

        $member = Member::create([
            'uid' => $request->uid,
            'member_number' => $request->member_number,
            'membership' => $request->membership,
            'full_name' => $request->full_name,
            'mobile_number' => $request->mobile_number,
            'email' => $request->email,
        ]);

        return redirect()->route('members.index')
            ->with('success', 'Member created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $member = Member::findOrFail($id);
        return view('members.show', compact('member'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $member = Member::findOrFail($id);
        return view('members.edit', compact('member'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $member = Member::findOrFail($id);

        $request->validate([
            'uid' => 'required|string|unique:members,uid,' . $id,
            'member_number' => 'required|string|unique:members,member_number,' . $id,
            'membership' => 'required|in:basic,premium,vip',
            'full_name' => 'required|string|max:255',
            'mobile_number' => 'required|string|max:20',
            'email' => 'required|email|unique:members,email,' . $id,
        ]);

        $member->update([
            'uid' => $request->uid,
            'member_number' => $request->member_number,
            'membership' => $request->membership,
            'full_name' => $request->full_name,
            'mobile_number' => $request->mobile_number,
            'email' => $request->email,
        ]);

        return redirect()->route('members.index')
            ->with('success', 'Member updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $member = Member::findOrFail($id);
        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Member deleted successfully!');
    }

    /**
     * Display member profile with membership history
     */
    public function profile(string $id)
    {
        $member = Member::with(['membershipPeriods.payment'])->findOrFail($id);
        
        // Get all membership periods (current and historical)
        $membershipPeriods = $member->membershipPeriods()
            ->with('payment')
            ->orderBy('start_date', 'desc')
            ->get();

        // Get current active membership
        $currentMembership = $member->currentMembershipPeriod;

        // Get payment history
        $payments = $member->payments()
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('members.profile', compact('member', 'membershipPeriods', 'currentMembership', 'payments'));
    }

    /**
     * Display membership history for a member
     */
    public function membershipHistory(string $id)
    {
        $member = Member::findOrFail($id);
        
        $membershipPeriods = $member->membershipPeriods()
            ->with('payment')
            ->orderBy('start_date', 'desc')
            ->paginate(10);

        return view('members.membership-history', compact('member', 'membershipPeriods'));
    }
}

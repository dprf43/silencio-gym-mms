<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Attendance;
use App\Models\ActiveSession;
use App\Models\RfidLog;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RfidController extends Controller
{
    /**
     * Handle RFID card tap for check-in/check-out
     */
    public function handleCardTap(Request $request): JsonResponse
    {
        $request->validate([
            'card_uid' => 'required|string',
            'device_id' => 'nullable|string',
        ]);

        $cardUid = $request->input('card_uid');
        $deviceId = $request->input('device_id', 'main_reader');

        try {
            DB::beginTransaction();

            // Find member by UID
            $member = Member::where('uid', $cardUid)->first();

            if (!$member) {
                $this->logRfidEvent($cardUid, 'unknown_card', 'failed', 
                    "Unknown card UID: {$cardUid}", $deviceId);
                
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => 'Unknown card. Please contact staff.',
                    'action' => 'unknown_card'
                ], 404);
            }

            // Check if member is active
            if (!$member->is_active) {
                $this->logRfidEvent($cardUid, 'check_in', 'failed', 
                    "Member {$member->full_name} has inactive status: {$member->status}", $deviceId);
                
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => 'Membership is not active. Please contact staff.',
                    'action' => 'inactive_membership'
                ], 403);
            }

            // Check if membership is expired
            if ($member->is_expired) {
                $this->logRfidEvent($cardUid, 'check_in', 'failed', 
                    "Member {$member->full_name} has expired membership", $deviceId);
                
                DB::commit();
                return response()->json([
                    'success' => false,
                    'message' => 'Membership has expired. Please renew.',
                    'action' => 'expired_membership'
                ], 403);
            }

            // Check if member has an active session
            $activeSession = ActiveSession::where('member_id', $member->id)
                ->where('status', 'active')
                ->first();

            if ($activeSession) {
                // Member is checking out
                return $this->handleCheckOut($member, $activeSession, $deviceId);
            } else {
                // Member is checking in
                return $this->handleCheckIn($member, $deviceId);
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('RFID Error: ' . $e->getMessage(), [
                'card_uid' => $cardUid,
                'device_id' => $deviceId,
                'error' => $e->getMessage()
            ]);

            $this->logRfidEvent($cardUid, 'check_in', 'failed', 
                "System error: " . $e->getMessage(), $deviceId);

            return response()->json([
                'success' => false,
                'message' => 'System error. Please try again.',
                'action' => 'system_error'
            ], 500);
        }
    }

    /**
     * Handle member check-in
     */
    private function handleCheckIn(Member $member, string $deviceId): JsonResponse
    {
        // Check if member has any existing active session and deactivate it
        $existingActiveSession = ActiveSession::where('member_id', $member->id)
            ->where('status', 'active')
            ->first();
            
        if ($existingActiveSession) {
            // Deactivate the existing session
            $existingActiveSession->update([
                'status' => 'inactive',
                'check_out_time' => now(),
                'session_duration' => $existingActiveSession->current_duration,
            ]);
            
            // Update the attendance record
            $existingActiveSession->attendance->update([
                'check_out_time' => now(),
                'status' => 'checked_out',
                'session_duration' => $existingActiveSession->current_duration,
            ]);
        }

        // Create attendance record
        $attendance = Attendance::create([
            'member_id' => $member->id,
            'check_in_time' => now(),
            'status' => 'checked_in',
        ]);

        // Create active session
        ActiveSession::create([
            'member_id' => $member->id,
            'attendance_id' => $attendance->id,
            'check_in_time' => now(),
            'status' => 'active',
        ]);

        // Log successful check-in
        $this->logRfidEvent($member->uid, 'check_in', 'success', 
            "Member {$member->full_name} checked in successfully", $deviceId);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Welcome back, {$member->full_name}!",
            'action' => 'check_in',
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'membership' => $member->membershipPlan?->name ?? 'Unknown',
                'check_in_time' => now()->format('H:i:s'),
            ]
        ]);
    }

    /**
     * Handle member check-out
     */
    private function handleCheckOut(Member $member, ActiveSession $activeSession, string $deviceId): JsonResponse
    {
        // Update attendance record
        $attendance = $activeSession->attendance;
        $attendance->update([
            'check_out_time' => now(),
            'status' => 'checked_out',
            'session_duration' => $activeSession->current_duration,
        ]);

        // Update active session
        $activeSession->update([
            'status' => 'inactive',
            'session_duration' => $activeSession->current_duration,
        ]);

        // Log successful check-out
        $this->logRfidEvent($member->uid, 'check_out', 'success', 
            "Member {$member->full_name} checked out. Session duration: {$activeSession->current_duration}", $deviceId);

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => "Goodbye, {$member->full_name}! Session duration: {$activeSession->current_duration}",
            'action' => 'check_out',
            'member' => [
                'id' => $member->id,
                'name' => $member->full_name,
                'check_out_time' => now()->format('H:i:s'),
                'session_duration' => $activeSession->current_duration,
            ]
        ]);
    }

    /**
     * Log RFID event
     */
    private function logRfidEvent(string $cardUid, string $action, string $status, string $message, string $deviceId): void
    {
        RfidLog::create([
            'card_uid' => $cardUid,
            'action' => $action,
            'status' => $status,
            'message' => $message,
            'timestamp' => now(),
            'device_id' => $deviceId,
        ]);
    }

    /**
     * Get current active members
     */
    public function getActiveMembers(): JsonResponse
    {
        $activeMembers = ActiveSession::with(['member:id,full_name,uid,current_plan_type'])
            ->active()
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->member->id,
                    'name' => $session->member->full_name,
                    'uid' => $session->member->uid,
                    'membership_plan' => $session->member->current_plan_type ?? 'Unknown',
                    'check_in_time' => $session->check_in_time->format('H:i:s'),
                    'session_duration' => $session->current_duration,
                ];
            });

        return response()->json([
            'success' => true,
            'active_members' => $activeMembers,
            'count' => $activeMembers->count(),
        ]);
    }

    /**
     * Get RFID logs
     */
    public function getRfidLogs(Request $request): JsonResponse
    {
        $logs = RfidLog::query()
            ->when($request->input('status'), function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->input('action'), function ($query, $action) {
                return $query->where('action', $action);
            })
            ->when($request->input('date'), function ($query, $date) {
                return $query->whereDate('timestamp', $date);
            })
            ->orderBy('timestamp', 'desc')
            ->paginate(50);

        return response()->json([
            'success' => true,
            'logs' => $logs,
        ]);
    }

    /**
     * Start the RFID reader process
     */
    public function startRfidReader(Request $request): JsonResponse
    {
        try {
            // Check if RFID reader is already running
            $isRunning = $this->isRfidReaderRunning();
            
            if ($isRunning) {
                return response()->json([
                    'success' => true,
                    'message' => 'RFID reader is already running',
                    'status' => 'running'
                ]);
            }
            
            // Start the RFID reader process
            $this->startRfidProcess();
            
            return response()->json([
                'success' => true,
                'message' => 'RFID reader started successfully',
                'status' => 'running'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start RFID reader: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
    
    /**
     * Stop the RFID reader process
     */
    public function stopRfidReader(Request $request): JsonResponse
    {
        try {
            // Stop the RFID reader process
            $this->stopRfidProcess();
            
            return response()->json([
                'success' => true,
                'message' => 'RFID reader stopped successfully',
                'status' => 'stopped'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop RFID reader: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
    
    /**
     * Get RFID reader status
     */
    public function getRfidStatus(Request $request): JsonResponse
    {
        try {
            $isRunning = $this->isRfidReaderRunning();
            
            return response()->json([
                'success' => true,
                'status' => $isRunning ? 'running' : 'stopped',
                'message' => $isRunning ? 'RFID reader is running' : 'RFID reader is stopped'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get RFID status: ' . $e->getMessage(),
                'status' => 'error'
            ], 500);
        }
    }
    
    /**
     * Check if RFID reader process is running
     */
    private function isRfidReaderRunning(): bool
    {
        // Check if Python process is running
        $output = shell_exec('tasklist /FI "IMAGENAME eq python.exe" /FO CSV 2>nul');
        
        if ($output && strpos($output, 'python.exe') !== false) {
            // Check if our specific script is running by looking at command line
            $output = shell_exec('wmic process where "name=\'python.exe\'" get commandline 2>nul');
            
            if ($output && strpos($output, 'rfid_reader.py') !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Start RFID reader process
     */
    private function startRfidProcess(): void
    {
        $pythonPath = 'C:\Users\PC\AppData\Local\Programs\Python\Python313\python.exe';
        $scriptPath = base_path('rfid_reader.py');
        
        // Start the RFID reader in background using PowerShell
        $command = sprintf(
            'powershell -Command "Start-Process -FilePath \'%s\' -ArgumentList \'%s\' -WindowStyle Hidden"',
            $pythonPath,
            $scriptPath
        );
        
        shell_exec($command);
        
        // Wait a moment for process to start
        sleep(3);
    }
    
    /**
     * Stop RFID reader process
     */
    private function stopRfidProcess(): void
    {
        // Kill all Python processes running rfid_reader.py
        shell_exec('taskkill /F /IM python.exe /FI "WINDOWTITLE eq rfid_reader.py" >nul 2>&1');
        
        // Also try to kill by process name
        shell_exec('taskkill /F /IM python.exe >nul 2>&1');
    }
}

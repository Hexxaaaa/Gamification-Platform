<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\DailyCheckIn;
use App\Models\Interaction;
use App\Models\Task;
use App\Models\User;
use App\Models\UserTask;
use App\Models\UserVoucher;
use App\Models\Voucher;
use App\Notifications\BadgeAchieved;
use App\Notifications\TaskCompleted;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class UserController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $tasks = Task::all();

        // Get user's current badge level
        $currentBadge = Badge::where('points_required', '<=', $user->total_points)
            ->orderBy('points_required', 'desc')
            ->first();

        if (!$currentBadge) {
            $currentBadge = Badge::orderBy('points_required', 'asc')->first();
        }

        // Get user's previous badge level
        $previousBadge = Badge::where('points_required', '<', $currentBadge->points_required)
            ->orderBy('points_required', 'desc')
            ->first() ?? Badge::first();

        return view('index', compact('user', 'currentBadge', 'previousBadge'));
    }

    /**
     * Display the user dashboard with aggregated insights.
     *
     * @return \Illuminate\View\View
     */
    public function dashboard()
    {
        $user = Auth::user();
        $totalTasks = Task::count();
        $userTasksCount = $user->userTasks()->count();
// Add null check and fallback to 0 if no tasks exist
        $engagement = $totalTasks > 0
        ? round(($userTasksCount / $totalTasks) * 100)
        : 0;

        // Get real metrics from interactions
        $interactions = $user->interactions()
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        // Default values for the stats table
        $statsData = [
            'likes' => $interactions['like'] ?? 400,
            'comments' => $interactions['comment'] ?? 50,
            'shares' => $interactions['share'] ?? 34,
            'engagement' => '8K',
            'total_score' => number_format($user->total_points ?? 8450),
        ];

        // Get total points and other metrics
        $totalPoints = $user->total_points;
        $totalMissions = Task::where('status', 'active')->count();
        $engagedUsers = User::whereHas('userTasks')->count();

        $missionStats = [
            'total_missions' => $totalMissions,
            'engaged_users' => $engagedUsers,
            'transparency' => 100, // Fixed value since it's a feature claim
        ];

        // Gamification highlights
        $gamificationHighlights = [
            'highly rewarding mission',
            'engaged users worldwide',
            'Transparent and fair challenges',
        ];

        $cardData = [
            'userEmail' => $user->email ?? 'User543@gmail.com',
            'displayDate' => now()->format('F d, Y'),
            'partnerLogos' => [
                'axon' => 'gallery/axon.png',
                'jetstar' => 'gallery/jetstar.png',
                'expedia' => 'gallery/expedia.png',
                'qantas' => 'gallery/qantas.png',
                'alitalia' => 'gallery/alitalia.png',
            ],
        ];

        $featuredContent = [
            [
                'image' => 'gallery/bilaesokibu.jpeg',
                'points' => 450,
            ],
            [
                'image' => 'gallery/Agaklaen.jpeg',
                'points' => 400,
            ],
            [
                'image' => 'gallery/jendelaseribusungai.jpeg',
                'points' => 320,
            ],
            [
                'image' => 'gallery/Pengantiniblis.jpeg',
                'points' => 380,
            ],
        ];

        // Platform statistics
        // Platform statistics without fallbacks
        $platformStats = [
            'platform_name' => 'PointPlay',
            'logo' => 'gallery/logopointplay.png',
            'metrics' => [
                'likes' => $interactions['like'] ?? 0,
                'engagement' => $engagement . '%', // Use calculated engagement
                'comments' => $interactions['comment'] ?? 0,
                'shares' => $interactions['share'] ?? 0,
                'reach' => $user->interactions()->distinct('task_id')->count(),
                'total_score' => number_format($user->total_points ?? 0),
            ],
        ];

        // Featured tasks for slider
        $featuredTasks = Task::where('featured', true)
            ->where('status', 'active')
            ->take(5)
            ->get();

        return view('user.dashboard', compact(
            'user',
            'interactions',
            'totalPoints',
            'totalMissions',
            'engagedUsers',
            'missionStats',
            'gamificationHighlights',
            'featuredTasks',
            'cardData',
            'platformStats',
            'featuredContent'
        ));
    }

    /**
     * Display a list of available tasks for the user.
     *
     * @return \Illuminate\View\View
     */
    public function tasks()
    {
        $user = Auth::user();

        // Get active tasks
        $tasks = Task::where('status', 'active')
            ->where(function ($query) {
                $query->where('deadline', '>', now())
                    ->orWhereNull('deadline');
            })
            ->orderBy('featured', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Get user's in-progress tasks
        $inProgressTasks = $user->userTasks()
            ->with('task')
            ->whereIn('status', ['pending', 'started'])
            ->get();

        // Get user's completed tasks
        $completedTasks = $user->userTasks()
            ->with('task')
            ->where('status', 'completed')
            ->get();

        return view('user.tasks.index', compact('tasks', 'inProgressTasks', 'completedTasks'));
    }

    public function showTask($userTaskId)
    {
        $userTask = UserTask::where('id', $userTaskId)
            ->where('user_id', Auth::id())
            ->with(['task', 'user'])
            ->firstOrFail();

        // Get related tasks (you can customize this query)
        $relatedTasks = Task::where('id', '!=', $userTask->task_id)
            ->where('status', 'active')
            ->take(4)
            ->get();

        // Check if user has interacted with this task
        $userInteractions = $userTask->task->interactions()
            ->where('user_id', Auth::id())
            ->pluck('type')
            ->toArray();

        return view('user.tasks.show', compact('userTask', 'relatedTasks', 'userInteractions'));
    }

    /**
     * Start a Task.
     *
     * @param  int  $taskId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function startTask($userTaskId)
    {
        $userTask = UserTask::where('id', $userTaskId)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($userTask->status === 'pending') {
            $userTask->update([
                'status' => 'started',
                'started_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Take a Task.
     *
     * @param  int  $taskId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function takeTask($taskId)
    {
        $user = Auth::user();
        $task = Task::findOrFail($taskId);

        if ($task->status !== 'active' || $user->userTasks()->where('task_id', $taskId)->exists()) {
            return redirect()->back()->with('error', 'Task not available to take.');
        }

        $userTask = UserTask::create([
            'user_id' => $user->id,
            'task_id' => $task->id,
            'status' => 'pending',
        ]);

        // Log task taken activity
        activity()
            ->causedBy($user)
            ->performedOn($task)
            ->withProperties(['user_task_id' => $userTask->id])
            ->log('Task Taken');

        return redirect()->route('user.tasks.show', $userTask->id);
    }

    /**
     * Complete a Task.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userTaskId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function completeTask(Request $request, $userTaskId)
    {
        $userTask = UserTask::where('id', $userTaskId)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        $task = $userTask->task;

        $this->authorize('complete', $userTask);

        // Ensure that 'watched_to_completion' is true
        if (!$userTask->watched_to_completion) {
            // Log incomplete task attempt
            activity()
                ->causedBy(Auth::user())
                ->performedOn($task)
                ->withProperties(['user_task_id' => $userTaskId])
                ->log('Incomplete Task Completion Attempt');

            return redirect()->back()->with('error', 'You must watch the video to completion to complete the task.');
        }

        $userTask->update([
            'status' => 'completed',
            'completion_date' => now(),
        ]);

        $user = Auth::user();
        $user->total_points += $task->points; // Points for watching the video
        $user->save();

        $this->updateBadgeStatuses($user);

        $user->notify(new TaskCompleted($task));

        // Log task completion
        activity()
            ->causedBy($user)
            ->performedOn($task)
            ->withProperties([
                'user_task_id' => $userTaskId,
                'points_earned' => $task->points,
                'total_points' => $user->total_points,
            ])
            ->log('Task Completed');

        // Check for badge achievements
        $badges = Badge::where('points_required', '<=', $user->total_points)->get();
        foreach ($badges as $badge) {
            if (!$user->badges()->where('badge_id', $badge->id)->exists()) {
                $user->badges()->attach($badge->id);
                $user->notify(new BadgeAchieved($badge));

                // Log badge achievement
                activity()
                    ->causedBy($user)
                    ->performedOn($badge)
                    ->withProperties(['badge_id' => $badge->id])
                    ->log('Badge Achieved');
            }
        }

        return redirect()->route('user.tasks.index')
            ->with('success', 'Task completed successfully! You earned ' . $task->points . ' points.');
    }

    /**
     * Mark the task as watched to completion.
     *
     * @param  int  $userTaskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markVideoWatched(Request $request, UserTask $userTask)
    {
        // Ensure the user owns this task
        if ($userTask->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        // Check if already watched
        if (!$userTask->watched_to_completion) {
            $userTask->update([
                'watched_to_completion' => true,
                'completion_date' => now(), // Changed from completed_at to completion_date
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json(['success' => false, 'message' => 'Video already watched']);
    }

    /**
     * Redeem a Voucher.
     *
     * @param  int  $voucherId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redeemVoucher($voucherId)
    {
        $user = Auth::user();
        $voucher = Voucher::findOrFail($voucherId);

        if ($user->total_points < $voucher->points_required || $voucher->status !== 'active') {
            // Log failed redemption attempt
            activity()
                ->causedBy($user)
                ->withProperties([
                    'voucher_id' => $voucher->id,
                    'points_required' => $voucher->points_required,
                    'current_points' => $user->total_points,
                    'status' => $voucher->status,
                ])
                ->log('Failed Voucher Redemption Attempt');

            return redirect()->back()->with('error', 'Voucher cannot be redeemed.');
        }

        $user->total_points -= $voucher->points_required;
        $user->save();

        $userVoucher = UserVoucher::create([
            'user_id' => $user->id,
            'voucher_id' => $voucher->id,
            'redeemed_at' => now(),
        ]);

        // Log successful redemption
        activity()
            ->causedBy($user)
            ->performedOn($voucher)
            ->withProperties([
                'voucher_id' => $voucher->id,
                'points_redeemed' => $voucher->points_required,
                'remaining_points' => $user->total_points,
            ])
            ->log('Voucher Redeemed');

        return redirect()->back()->with('success', 'Voucher redeemed successfully.');
    }

    /**
     * Display the leaderboard.
     *
     * @return \Illuminate\View\View
     */

    /**
     * Log Interaction (Like, Comment, Share).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Task  $task
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logInteraction(Request $request, Task $task)
    {
        try {
            $validated = $request->validate([
                'type' => 'required|string|in:like,comment,share',
                'comment' => 'required_if:type,comment|string|max:500|nullable',
            ]);

            DB::beginTransaction();

            $user = Auth::user();

            // Check for existing interaction
            $existingInteraction = $user->interactions()
                ->where('task_id', $task->id)
                ->where('type', $validated['type'])
                ->first();

            if ($existingInteraction) {
                return response()->json([
                    'success' => false,
                    'message' => "You have already {$validated['type']}d this task",
                ], 409);
            }

            // Create interaction
            $interaction = Interaction::create([
                'user_id' => $user->id,
                'task_id' => $task->id,
                'type' => $validated['type'],
                'content' => $validated['type'] === 'comment' ? $validated['comment'] : null,
            ]);

            // Award points
            $pointsMap = [
                'like' => 10,
                'comment' => 20,
                'share' => 50,
            ];

            $points = $pointsMap[$validated['type']] ?? 0;
            $user->increment('total_points', $points);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst($validated['type']) . ' recorded successfully',
                'points' => $points,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Interaction Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while recording your interaction',
            ], 500);
        }
    }
    // Helper method for badge processing
    private function processNewBadges(User $user): array
    {
        $newBadges = [];
        $badges = Badge::where('points_required', '<=', $user->total_points)
            ->whereNotIn('id', $user->badges()->pluck('badge_id'))
            ->get();

        foreach ($badges as $badge) {
            $user->badges()->attach($badge->id);
            $user->notify(new BadgeAchieved($badge));
            $newBadges[] = $badge;

            activity()
                ->causedBy($user)
                ->performedOn($badge)
                ->withProperties(['badge_id' => $badge->id])
                ->log('Badge Achieved');
        }

        return $newBadges;
    }

    /**
     * Display task statistics.
     *
     * @return \Illuminate\View\View
     */
    public function taskStatistics()
    {
        $statistics = Task::withCount(['userTasks' => function ($query) {
            $query->where('status', 'completed');
        }])->get();

        return view('user.task_statistics', compact('statistics'));
    }

    /**
     * Display the user's profile.
     *
     * @return \Illuminate\View\View
     */
    public function profile()
    {
        $user = Auth::user();
        $taskHistory = $user->userTasks()
            ->with('task')
            ->where('status', 'completed')
            ->get();
        $vouchers = $user->userVouchers()
            ->with('voucher')
            ->latest()
            ->take(3) // Get last 3 vouchers
            ->get();
        $interactions = $user->interactions()
            ->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        $taskStats = [
            'like' => $user->interactions()->where('type', 'like')->count(),
            'comment' => $user->interactions()->where('type', 'comment')->count(),
            'share' => $user->interactions()->where('type', 'share')->count(),
        ];
        $taskCompletion = round(
            ($user->userTasks()->where('status', 'completed')->count() /
                Task::count()) * 100
        );

        return view('user.profile.show', compact(
            'user',
            'taskHistory',
            'vouchers',
            'interactions',
            'taskStats',
            'taskCompletion'
        ));
    }

    public function editProfile()
    {
        $user = Auth::user();
        return view('user.profile.edit', compact('user'));
    }

    /**
     * Update the user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'age' => 'nullable|integer|min:0',
            'gender' => 'required|in:Male,Female',
            'location' => 'nullable|string|max:255',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        $data = $request->only(['name', 'email', 'age', 'gender', 'location']);

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            // Delete old profile image jika ada
            if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                Storage::disk('public')->delete($user->profile_image);
            }

            // Simpan gambar baru
            $path = $request->file('profile_image')->store('profile_images', 'public');
            $data['profile_image'] = $path;
        }

        $user->update($data);

        // Log profile update activity
        activity()
            ->causedBy($user)
            ->performedOn($user)
            ->withProperties(['updated_fields' => $data])
            ->log('Profile Updated');

        return response()->json(['success' => true, 'message' => 'Profile updated successfully']);
    }

    /**
     * Handle daily check-in
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function checkIn()
    {
        $user = Auth::user();
        $now = now(); // This will use the configured timezone

        // Get user's last check-in
        $lastCheckIn = DailyCheckIn::where('user_id', $user->id)
            ->latest()
            ->first();

        // Check if user already checked in today using the correct timezone
        if ($lastCheckIn && $lastCheckIn->last_check_in->timezone(config('app.timezone'))->isToday()) {
            return response()->json([
                'success' => false,
                'message' => 'Already checked in today',
            ], 400);
        }

        // Calculate day count
        $dayCount = 1; // Default to day 1

        // Only maintain streak if checked in yesterday
        if ($lastCheckIn && $lastCheckIn->last_check_in->timezone(config('app.timezone'))->isYesterday()) {
            $dayCount = min($lastCheckIn->day_count + 1, 7);
        }

        // Calculate points (50 points × day count)
        $points = 50 * $dayCount;

        // Create new check-in record
        DailyCheckIn::create([
            'user_id' => $user->id,
            'day_count' => $dayCount,
            'last_check_in' => $now,
            'points_earned' => $points,
        ]);

        // Add points to user
        $user->total_points += $points;
        $user->save();

        // Log the check-in activity
        activity()
            ->causedBy($user)
            ->withProperties([
                'day_count' => $dayCount,
                'points_earned' => $points,
            ])
            ->log('Daily Check-in Complete');

        return response()->json([
            'success' => true,
            'points' => $points,
            'day_count' => $dayCount,
        ]);
    }

/**
 * Get check-in status
 *
 * @return \Illuminate\Http\JsonResponse
 */

    public function checkInStatus()
    {
        $user = Auth::user();

        // Get last check-in using correct timezone
        $lastCheckIn = DailyCheckIn::where('user_id', $user->id)
            ->latest()
            ->first();

        $canCheckIn = !$lastCheckIn ||
        !$lastCheckIn->last_check_in->timezone(config('app.timezone'))->isToday();

        $currentDay = 0;
        $streak = 0;

        if ($lastCheckIn) {
            if ($lastCheckIn->last_check_in->timezone(config('app.timezone'))->isToday()) {
                $currentDay = $lastCheckIn->day_count;
                $streak = $currentDay;
            } else if ($lastCheckIn->last_check_in->timezone(config('app.timezone'))->isYesterday()) {
                $currentDay = $lastCheckIn->day_count;
                $streak = $currentDay;
            }
        }

        // Calculate next reward
        $nextDay = min($streak + 1, 7);
        $nextReward = $nextDay * 50;

        return response()->json([
            'can_check_in' => $canCheckIn,
            'current_day' => $currentDay,
            'current_streak' => $streak,
            'next_reward' => $nextReward,
        ]);
    }

    /**
     * Check if a task is available for the user.
     *
     * @param  int  $taskId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkTaskAvailability($taskId)
    {
        $user = Auth::user();
        $task = Task::findOrFail($taskId);

        $available = true;
        $message = '';

        // Check if task is active
        if ($task->status !== 'active') {
            $available = false;
            $message = 'This task is no longer active.';
        }

        // Check if task has expired
        if ($task->deadline && Carbon::parse($task->deadline)->isPast()) {
            $available = false;
            $message = 'This task has expired.';
        }

        // Check if user already has this task
        if ($user->userTasks()->where('task_id', $taskId)->exists()) {
            $available = false;
            $message = 'You have already taken this task.';
        }

        return response()->json([
            'available' => $available,
            'message' => $message ?: 'Task is available',
        ]);
    }

    protected function updateBadgeStatuses($user)
    {
        // Initialize newBadge variable
        $newBadge = null;
        // Get all badges where user has enough points but hasn't collected yet
        $eligibleBadges = Badge::where('points_required', '<=', $user->total_points)
            ->whereDoesntHave('userBadges', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', 'collected');
            })
            ->get();

        foreach ($eligibleBadges as $badge) {
            // Get or create user badge record
            $userBadge = $user->userBadges()->firstOrCreate(
                ['badge_id' => $badge->id],
                ['status' => 'locked']
            );

            // Update to available if not already collected
            if ($userBadge->status !== 'collected') {
                $userBadge->update(['status' => 'available']);
                $newBadge = $badge;
            }
        }
        if ($newBadge) {
            session()->flash('badge_achieved', $newBadge);
        }
    }
}

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>TASK SESSION</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="{{ url('css/task.css') }}">
</head>

<body>

    @include('layouts.header')

    <main class="py-5">
        <<div class="container">
    <div class="video-container position-relative">
        @if ($userTask->task->video_type === 'youtube')
            <iframe id="youtube-player" width="100%" height="600"
                src="{{ $userTask->task->video_url }}?enablejsapi=1&origin={{ url('/') }}&autoplay=1&controls=0"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen>
            </iframe>
        @elseif($userTask->task->video_type === 'file')
            <video id="task-video" width="100%" height="600" autoplay muted>
                <source src="{{ $userTask->task->video_url }}" type="video/mp4">
                Your browser does not support the video tag.
            </video>
        @endif
    </div>

    <!-- Progress bar -->
    <div class="progress-section mt-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="progress-text text-muted">
                <i class="bi bi-info-circle"></i>
                Video Progress: Watch the video to unlock rewards
            </span>
            <span class="progress-percentage text-muted" id="progress-percentage">0%</span>
        </div>
        <div class="progress" style="height: 0.5rem;">
            <div class="progress-bar" role="progressbar" style="width: 0%" id="video-progress" aria-valuenow="0"
                aria-valuemin="0" aria-valuemax="100">
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex justify-content-between align-items-center">
        <form action="{{ route('user.tasks.complete', $userTask->id) }}" method="POST" class="d-inline">
            @csrf
            <button type="submit" id="complete-task-btn" class="btn btn-success d-none">
                Complete Task
            </button>
        </form>
    </div>
</div>


<div class="container my-4">
    <h3 class="mt-4">Experience the Fun of Progress</h3>
    <p>{{ $userTask->task->description }}</p>

    <!-- Grid layout for buttons -->
    <div class="row gx-3 gy-3">
        <!-- Like Button -->
        <div class="col-12 col-sm-6 col-md-4">
            <button
                class="btn btn-outline-primary interact-btn w-100 {{ in_array('like', $userInteractions) ? 'active' : '' }}"
                data-type="like" data-task="{{ $userTask->task_id }}" data-points="10">
                <img src="{{ url('gallery/like.png') }}" alt="iconjempol" style="width: 30px">
                <span class="d-block mt-1">+10 pts</span>
            </button>
        </div>

        <!-- Share Button -->
        <div class="col-12 col-sm-6 col-md-4">
            <button
                class="btn btn-outline-primary interact-btn w-100 {{ in_array('share', $userInteractions) ? 'active' : '' }}"
                data-type="share" data-task="{{ $userTask->task_id }}" data-points="50" data-bs-toggle="modal"
                data-bs-target="#shareModal">
                <img src="{{ url('gallery/sharetask.png') }}" alt="iconshare" style="width: 30px">
                <span class="d-block mt-1">+50 pts</span>
            </button>
        </div>

        <!-- Comment Button -->
        <div class="col-12 col-sm-6 col-md-4">
            <button
                class="btn btn-outline-primary interact-btn w-100 {{ in_array('comment', $userInteractions) ? 'active' : '' }}"
                data-type="comment" data-task="{{ $userTask->task_id }}" data-points="20" data-bs-toggle="modal"
                data-bs-target="#commentModal">
                <img src="{{ url('gallery/commentask.png') }}" alt="iconkomen" style="width: 30px">
                <span class="d-block mt-1">+20 pts</span>
            </button>
        </div>
    </div>
</div>

<br>
            <p class=" text-center">Discover a platform where every action counts! Watch videos, like, comment, and share to earn points
                effortlessly. Turn your daily interactions into exciting rewards and climb the leaderboard with
                every step forward. Whether you're enjoying your favorite content or unlocking exclusive perks,
                PointPlay makes every moment rewarding. Start now and experience progress like never before!</p>
        </div>

        <!-- Add Share Modal -->
        <div class="modal fade" id="shareModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Share This Task</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Share Link:</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="shareLink"
                                    value="{{ request()->url() }}" readonly>
                                <button class="btn btn-primary" id="copyShareLink">
                                    <i class="bi bi-clipboard"></i> Copy
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Comment Modal -->
        <div class="modal fade" id="commentModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Add Comment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="commentText" class="form-label">Your Comment:</label>
                            <textarea class="form-control" id="commentText" rows="3" required></textarea>
                        </div>
                        <button type="button" class="btn btn-primary" id="submitComment">
                            <i class="bi bi-send"></i> Submit Comment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container text-center">

            <div class="d-flex justify-content-center gap-3">
                <a href="#"><i class="bi bi-youtube"></i></a>
                <a href="#"><i class="bi bi-facebook"></i></a>
                <a href="#"><i class="bi bi-twitter"></i></a>
                <a href="#"><i class="bi bi-instagram"></i></a>
            </div>
        </div>
    </footer>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.js"></script>
    <script src="https://www.youtube.com/iframe_api"></script>

    <script>
        let player;
        let progressBar = document.getElementById('video-progress');
        let progressInterval;

        // YouTube player initialization
        function onYouTubeIframeAPIReady() {
            const videoUrl = "{{ $userTask->task->video_url }}";
            const videoId = getYouTubeVideoId(videoUrl);

            if (document.getElementById('youtube-player')) {
                player = new YT.Player('youtube-player', {
                    videoId: videoId,
                    events: {
                        'onReady': onPlayerReady,
                        'onStateChange': onPlayerStateChange
                    },
                    playerVars: {
                        'enablejsapi': 1,
                        'origin': window.location.origin,
                        'autoplay': 0,
                        'rel': 0
                    }
                });
            }
        }

        // HTML5 video handling
        const video = document.getElementById('task-video');
        if (video) {
            // Initialize progress bar immediately for HTML5 video
            video.addEventListener('loadedmetadata', () => {
                if (!progressBar) {
                    progressBar = document.getElementById('video-progress');
                }
            });

            video.addEventListener('play', () => {
                // Update task status to 'started' when video starts playing
                fetch("{{ route('user.tasks.start', $userTask->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    }
                });
            });

            video.addEventListener('timeupdate', () => {
                if (progressBar) {
                    const progress = (video.currentTime / video.duration) * 100;
                    progressBar.style.width = `${progress}%`;

                    // Update percentage text
                    document.getElementById('progress-percentage').textContent =
                        `${Math.round(progress)}%`;

                    if (progress >= 95) {
                        showCompleteButton();
                    }
                }
            });

            video.addEventListener('ended', showCompleteButton);
        }

        // YouTube helper functions
        function getYouTubeVideoId(url) {
            const regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
            const match = url.match(regExp);
            return (match && match[2].length === 11) ? match[2] : null;
        }

        function onPlayerReady(event) {
            progressBar = document.getElementById('video-progress');
            // Update task status to 'started' when video is ready
            fetch("{{ route('user.tasks.start', $userTask->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            });
            updateProgressBar();
        }

        function onPlayerStateChange(event) {
            if (event.data === YT.PlayerState.PLAYING) {
                updateProgressBar();
            } else if (event.data === YT.PlayerState.ENDED) {
                clearInterval(progressInterval);
                progressBar.style.width = '100%';
                showCompleteButton();
            }
        }

        // Progress tracking
        function updateProgressBar() {
            if (!progressInterval && player && player.getCurrentTime) {
                progressInterval = setInterval(() => {
                    if (player.getPlayerState() === YT.PlayerState.PLAYING) {
                        const duration = player.getDuration();
                        const currentTime = player.getCurrentTime();
                        const progress = (currentTime / duration) * 100;

                        // Update progress bar
                        progressBar.style.width = `${progress}%`;

                        // Update percentage text
                        document.getElementById('progress-percentage').textContent =
                            `${Math.round(progress)}%`;

                        if (progress >= 95) {
                            clearInterval(progressInterval);
                            progressBar.style.width = '100%';
                            document.getElementById('progress-percentage').textContent = '100%';
                            showCompleteButton();
                        }
                    }
                }, 1000);
            }
        }




        // Task completion handling
        function showCompleteButton() {
            fetch("{{ route('user.tasks.markWatched', $userTask->id) }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    credentials: 'same-origin'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show completion animation
                        Swal.fire({
                            icon: 'success',
                            title: 'Task Completed!',
                            html: `
                    <div class="d-flex flex-column align-items-center">
                        <div class="mb-3">
                            <i class="fas fa-trophy text-warning fa-3x mb-3"></i>
                        </div>
                        <h4 class="text-success mb-2">Congratulations!</h4>
                        <p class="mb-3">You've earned {{ number_format($userTask->task->points) }} points</p>
                        <div class="d-flex gap-2 justify-content-center">
                            <span class="badge bg-primary rounded-pill px-3">
                                <i class="fas fa-star me-1"></i>Task Complete
                            </span>
                            <span class="badge bg-warning rounded-pill px-3">
                                <i class="fas fa-coins me-1"></i>+{{ number_format($userTask->task->points) }} pts
                            </span>
                        </div>
                    </div>
                `,
                            showConfirmButton: true,
                            confirmButtonText: 'Claim Rewards!',
                            customClass: {
                                confirmButton: 'btn btn-success rounded-pill px-4'
                            },
                            allowOutsideClick: false
                        }).then((result) => {
                            if (result.isConfirmed) {
                                document.getElementById('complete-task-btn').click();
                            }
                        });
                    }
                });
        }

        $(document).ready(function() {
            // General interaction handler for like/share/comment buttons
            $('.interact-btn').on('click', function(e) {
                e.preventDefault(); // Prevent default button behavior
                const button = $(this);
                const type = button.data('type');

                console.log('Button clicked:', type); // Add debug logging

                // If it's a comment, let the comment modal handle it
                if (type === 'comment') {
                    return;
                }

                handleInteraction(button);
            });

            // Share functionality
            $('#copyShareLink').click(function() {
                const shareBtn = $('.interact-btn[data-type="share"]');

                if (shareBtn.hasClass('active')) {
                    showNotification('info', 'Already Shared', 'You have already shared this task!');
                    return;
                }

                const shareLink = document.getElementById('shareLink');
                shareLink.select();
                document.execCommand('copy');

                // Update copy button UI
                this.innerHTML = '<i class="bi bi-check"></i> Copied!';
                setTimeout(() => {
                    this.innerHTML = '<i class="bi bi-clipboard"></i> Copy';
                }, 2000);

                // Trigger share interaction
                handleInteraction(shareBtn);
            });

            // Comment functionality
            $('#submitComment').click(function() {
                const commentBtn = $('.interact-btn[data-type="comment"]');
                const commentText = $('#commentText').val().trim();

                // Validate empty comment
                if (!commentText) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Empty Comment',
                        text: 'Please write something before submitting.',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    return;
                }

                // Check if already commented
                if (commentBtn.hasClass('active')) {
                    Swal.fire({
                        icon: 'info',
                        title: 'Already Commented',
                        text: 'You have already commented on this task!',
                        toast: true,
                        position: 'top-end',
                        showConfirmButton: false,
                        timer: 3000
                    });
                    return;
                }

                // Submit comment
                $.ajax({
                    url: "{{ route('user.tasks.interaction', ['task' => ':taskId']) }}".replace(
                        ':taskId', commentBtn.data('task')),
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        type: 'comment',
                        comment: commentText
                    },
                    success: function(response) {
                        if (response.success) {
                            // Mark as commented
                            commentBtn.addClass('active');
                            commentBtn.addClass('btn-primary').removeClass(
                                'btn-outline-primary');

                            // Update points
                            updatePoints(commentBtn.data('points'));

                            // Show success message
                            Swal.fire({
                                icon: 'success',
                                title: 'Comment Posted!',
                                text: `+${commentBtn.data('points')} points for commenting!`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });

                            // Reset form
                            $('#commentText').val('');
                            $('#commentModal').modal('hide');

                            // Disable comment button
                            commentBtn.prop('disabled', true);
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Oops...',
                            text: 'Failed to post comment!',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            });

            // Helper Functions
            function handleInteraction(button) {
                const type = button.data('type');
                const taskId = button.data('task');
                const points = button.data('points');

                if (button.hasClass('active')) {
                    showNotification('info', 'Already Interacted', `You have already ${type}d this task!`);
                    return;
                }

                // Disable button while processing
                button.prop('disabled', true);


                $.ajax({
                    url: `/user/tasks/${taskId}/interaction`,
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        type: type,
                        comment: null
                    },
                    success: function(response) {
                        if (response.success) {
                            // Add active class and styling
                            button.addClass('active');
                            button.addClass('btn-primary').removeClass('btn-outline-primary');

                            // Update points
                            updatePoints(points);

                            // Show success notification
                            showNotification('success', 'Points Earned!',
                                `+${points} points for ${type}ing this task!`);

                            // Keep button disabled
                            button.prop('disabled', true);
                        } else {
                            button.prop('disabled', false);
                            showNotification('error', 'Error', response.message ||
                                'Something went wrong!');
                        }
                    },
                    error: function(xhr) {
                        button.prop('disabled', false);

                        let errorMessage = xhr.responseJSON?.message || 'Something went wrong!';
                        showNotification('error', 'Error', errorMessage);

                        if (xhr.status === 419) {
                            window.location.reload();
                        }
                    }
                });
            }

            function showNotification(icon, title, text) {
                Swal.fire({
                    icon: icon,
                    title: title,
                    text: text,
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
            }


            function updatePoints(points) {
                // Update points in header
                const pointsElement = $('.badge.bg-primary');
                if (pointsElement.length) {
                    const currentPoints = parseInt(pointsElement.text().replace(/[^0-9]/g, ''));
                    const newPoints = currentPoints + points;
                    pointsElement.text(`${newPoints.toLocaleString()} Pts`);
                }
            }
        });
    </script>
</body>

</html>

@if(isset($announcements) && $announcements->count() > 0)
<div class="col-lg-12 mb-4">
    <div class="card overflow-hidden border-0 shadow-sm" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px;">
        <div class="card-header border-0 bg-transparent py-3">
            <h3 class="card-title mb-0 text-white fw-bold d-flex align-items-center">
                <span class="avatar avatar-md br-7 bg-white-transparent text-white me-3">
                    <i class="fe fe-bell"></i>
                </span>
                Latest Announcement
            </h3>
            <div class="card-options">
                <a href="javascript:void(0)" class="card-options-collapse text-white" data-bs-toggle="card-collapse"><i class="fe fe-chevron-up text-white"></i></a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="announcement-slider p-4" style="background: rgba(255, 255, 255, 0.05); backdrop-filter: blur(10px);">
                @foreach($announcements as $announcement)
                <div class="announcement-item mb-4 last-mb-0 p-3 br-10 transition-all hover-glass" 
                     style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.1);">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="text-white fw-semibold mb-0">{{ $announcement->title }}</h5>
                        <span class="badge bg-white-transparent text-white-50 small">
                            {{ $announcement->published_at->diffForHumans() }}
                        </span>
                    </div>
                    <div class="text-white-80 text-break mb-2 opacity-80" style="font-size: 0.95rem; line-height: 1.6;">
                        {!! nl2br(e($announcement->content)) !!}
                    </div>
                    <div class="d-flex align-items-center mt-3">
                        <div class="avatar avatar-sm brround bg-white-transparent text-white me-2">
                            {{ strtoupper(substr($announcement->createdBy?->name ?? 'A', 0, 1)) }}
                        </div>
                        <small class="text-white-50">
                            Posted by <span class="text-white fw-medium">{{ $announcement->createdBy?->name ?? 'Admin' }}</span>
                        </small>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    .last-mb-0:last-child { margin-bottom: 0 !important; }
    .text-white-80 { color: rgba(255, 255, 255, 0.85); }
    .hover-glass:hover {
        background: rgba(255, 255, 255, 0.15) !important;
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1);
    }
    .transition-all { transition: all 0.3s ease; }
    .bg-white-transparent { background: rgba(255, 255, 255, 0.15); }
</style>
@endif

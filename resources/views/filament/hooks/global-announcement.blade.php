@php
    $announcement = \App\Models\GlobalAnnouncement::getActive();
@endphp

@if($announcement)
    <div style="background-color: <?php echo match($announcement->color) {
        'info' => '#3b82f6',
        'success' => '#10b981',
        'warning' => '#f59e0b',
        'danger' => '#ef4444',
        default => '#3b82f6',
    }; ?>; color: white; padding: 12px 20px; text-align: center; font-weight: 500; font-size: 14px; z-index: 50; position: relative;">
        <strong>{{ $announcement->title }}:</strong> {{ $announcement->message }}
    </div>
@endif

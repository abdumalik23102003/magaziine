@php
    $record = $getRecord();
    $url = \App\Models\Brand::mediaUrl($record?->story_video_path);
@endphp

<div>
    @if ($url)
        <p style="margin-bottom: .5rem; font-size: .875rem; font-weight: 500;">Current video</p>
        <video wire:key="story-preview-{{ md5($url) }}" src="{{ $url }}" controls muted playsinline preload="metadata" style="width: 100%; max-height: 360px; border-radius: .75rem; background: #000;"></video>
    @else
        <p style="font-size: .875rem; opacity: .7;">No video uploaded yet.</p>
    @endif
</div>

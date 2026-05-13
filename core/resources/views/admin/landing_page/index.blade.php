@extends('admin.layouts.app')

@push('style-lib')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/dracula.min.css">
@endpush

@push('style')
<style>
    .CodeMirror {
        height: 520px;
        font-size: 13px;
        font-family: 'JetBrains Mono', 'Fira Code', 'Courier New', monospace;
        border-radius: 0 0 6px 6px;
    }
    .editor-header {
        background: #282a36;
        color: #cdd6f4;
        padding: 8px 14px;
        font-size: 12px;
        border-radius: 6px 6px 0 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .editor-dot { width:12px;height:12px;border-radius:50%; }
</style>
@endpush

@section('panel')
<div class="row justify-content-center">
    <div class="col-xl-10">

        <form action="{{ route('admin.landing.save') }}" method="POST">
            @csrf

            {{-- ── Toggle Card ── --}}
            <div class="card mb-4">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="mb-0"><i class="las la-toggle-on me-2"></i>@lang('Frontend Override')</h6>
                    <span class="badge {{ $setting->frontend_disabled ? 'bg--success' : 'bg--danger' }}">
                        {{ $setting->frontend_disabled ? __('Custom Page Active') : __('Default Frontend Active') }}
                    </span>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        @lang('When enabled, the homepage will serve your custom HTML below instead of the default application frontend.')
                    </p>
                    <div class="d-flex align-items-center gap-3">
                        <label class="form-check form-switch mb-0 d-flex align-items-center gap-2" style="cursor:pointer;">
                            <input class="form-check-input" type="checkbox" name="frontend_disabled"
                                   value="1" role="switch" style="width:3rem;height:1.5rem;"
                                   id="toggleSwitch" {{ $setting->frontend_disabled ? 'checked' : '' }}>
                            <input type="hidden" name="frontend_disabled" value="0" id="hiddenToggle">
                            <span class="form-check-label fw-semibold" id="toggleLabel">
                                {{ $setting->frontend_disabled ? __('Disable Default Frontend (Custom page showing)') : __('Enable Default Frontend (Custom page hidden)') }}
                            </span>
                        </label>
                    </div>
                </div>
            </div>

            {{-- ── Editor Card ── --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="las la-code me-2"></i>@lang('Custom Page HTML')</h6>
                </div>
                <div class="card-body p-0">
                    <div class="editor-header">
                        <span class="editor-dot" style="background:#ff5f57;"></span>
                        <span class="editor-dot" style="background:#ffbd2e;"></span>
                        <span class="editor-dot" style="background:#28c940;"></span>
                        <span class="ms-2 opacity-75">index.html</span>
                        <span class="ms-auto opacity-50">
                            Tailwind CDN is auto-injected &nbsp;|&nbsp; Ctrl+S to save
                        </span>
                    </div>
                    <textarea id="html-editor" name="html_content">{{ old('html_content', $page->html_content ?? '') }}</textarea>
                </div>
            </div>

            {{-- ── Hints ── --}}
            <div class="card mb-4 border-0" style="background:#f8f9fc;">
                <div class="card-body py-3">
                    <p class="mb-1 small text-muted fw-semibold">@lang('Tips')</p>
                    <ul class="mb-0 small text-muted ps-3">
                        <li>@lang('Tailwind CSS CDN is automatically included — use any Tailwind class freely.')</li>
                        <li>@lang('Write a complete HTML body (no need for &lt;html&gt; or &lt;head&gt; tags unless you want them).')</li>
                        <li>@lang('Inline &lt;style&gt; and &lt;script&gt; tags are fully supported.')</li>
                        <li>@lang('Toggle must be ON for visitors to see the custom page.')</li>
                    </ul>
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn--primary px-5">
                    <i class="las la-save me-1"></i>@lang('Save')
                </button>
                @if($setting->frontend_disabled && $page)
                <a href="{{ route('admin.landing.preview') }}" target="_blank" class="btn btn-outline-info">
                    <i class="las la-eye me-1"></i>@lang('Preview')
                </a>
                @endif
                <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="las la-external-link-alt me-1"></i>@lang('View Site')
                </a>
            </div>

        </form>
    </div>
</div>
@endsection

@push('script-lib')
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/htmlmixed/htmlmixed.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/xml/xml.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/css/css.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js"></script>
@endpush

@push('script')
<script>
"use strict";

// ── CodeMirror editor ──────────────────────────────────────────────
var editor = CodeMirror.fromTextArea(document.getElementById('html-editor'), {
    mode        : 'htmlmixed',
    theme       : 'dracula',
    lineNumbers : true,
    lineWrapping: true,
    indentUnit  : 2,
    tabSize     : 2,
    autoCloseTags: true,
});

// Sync value back to textarea on form submit
document.querySelector('form').addEventListener('submit', function() {
    editor.save();
});

// Ctrl+S shortcut
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        editor.save();
        document.querySelector('form').submit();
    }
});

// ── Toggle label update ────────────────────────────────────────────
var toggleSwitch = document.getElementById('toggleSwitch');
var hiddenToggle = document.getElementById('hiddenToggle');
var toggleLabel  = document.getElementById('toggleLabel');

toggleSwitch.addEventListener('change', function() {
    if (this.checked) {
        this.name = 'frontend_disabled';
        hiddenToggle.name = '_frontend_disabled_unused';
        toggleLabel.textContent = '{{ __("Disable Default Frontend (Custom page showing)") }}';
    } else {
        this.name = '_frontend_disabled_unused';
        hiddenToggle.name = 'frontend_disabled';
        toggleLabel.textContent = '{{ __("Enable Default Frontend (Custom page hidden)") }}';
    }
});

// Init correct names on load
if (!toggleSwitch.checked) {
    toggleSwitch.name = '_frontend_disabled_unused';
    hiddenToggle.name = 'frontend_disabled';
}
</script>
@endpush

@extends('layouts.app')
@section('title', 'Submit Article')

@section('content')
<div class="row justify-content-center">
<div class="col-lg-8">
<div class="card border-0 shadow-sm" style="border-radius:16px!important;">
    <div class="card-header bg-white fw-semibold border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
        <span>Submit an Article</span>
        <a href="{{ route('articles.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>Back
        </a>
    </div>
    <div class="card-body p-4">
        <div class="alert border-0 small mb-4"
             style="background:#eff6ff;color:#1e40af;border-radius:10px!important;">
            <i class="bi bi-info-circle me-1"></i>
            Your article will be reviewed by an administrator before it is published.
        </div>

        <form method="POST" action="{{ route('articles.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                <select name="category_id"
                        class="form-select @error('category_id') is-invalid @enderror" required>
                    <option value="">Select a category…</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}"
                            {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                        {{ $cat->name }}
                    </option>
                    @endforeach
                </select>
                @error('category_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                <input type="text" name="title"
                       class="form-control @error('title') is-invalid @enderror"
                       value="{{ old('title') }}" required>
                @error('title')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Content <span class="text-danger">*</span></label>
                <textarea name="body"
                          class="form-control @error('body') is-invalid @enderror"
                          rows="12" required>{{ old('body') }}</textarea>
                @error('body')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- ── TAGS ──────────────────────────────────────────────── --}}
            <div class="mb-4">
                <label class="form-label fw-semibold">
                    Tags <span class="text-muted fw-normal small">(optional)</span>
                </label>

                {{-- Hidden input that actually gets submitted with the form --}}
                <input type="hidden" name="tags" id="tagsHidden" value="{{ old('tags') }}">

                {{-- Tag badge display area --}}
                <div id="tagBadges"
                     class="d-flex flex-wrap gap-1 mb-2 p-2 border rounded bg-white"
                     style="min-height:42px;cursor:text;border-radius:8px!important;"
                     onclick="document.getElementById('tagTypingInput').focus()">
                    {{-- badges injected by JS --}}
                    <span id="tagPlaceholder" class="text-muted small" style="line-height:2;">
                        Click here then type a tag…
                    </span>
                </div>

                {{-- Typing box — NOT submitted, purely for UX --}}
                <input type="text" id="tagTypingInput"
                       class="form-control form-control-sm mt-1"
                       placeholder="Type a tag, then press Enter or comma to add"
                       autocomplete="off">

                <div class="form-text">Press <kbd>Enter</kbd> or <kbd>,</kbd> after each tag. Click × to remove.</div>

                {{-- Suggestions from existing tags --}}
                @if($allTags->isNotEmpty())
                <div class="mt-2">
                    <span class="text-muted small me-1">Suggestions:</span>
                    @foreach($allTags->take(20) as $tag)
                    <button type="button"
                            class="btn btn-sm bg-light border text-muted mb-1"
                            style="font-size:.72rem;"
                            onclick="addTag('{{ addslashes($tag->name) }}')">
                        #{{ $tag->name }}
                    </button>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Submit for Review
                </button>
                <a href="{{ route('articles.index') }}" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
</div>
</div>
@endsection

@push('scripts')
<script>
// ─── Source of truth: an array of tag strings ───────────────────────────────
let tags = [];

// Pre-populate from old() value if a validation error bounced the form back
(function () {
    const old = document.getElementById('tagsHidden').value.trim();
    if (old) {
        old.split(',').map(t => t.trim()).filter(Boolean).forEach(t => tags.push(t));
    }
})();

const typingInput  = document.getElementById('tagTypingInput');
const badgesEl     = document.getElementById('tagBadges');
const hiddenInput  = document.getElementById('tagsHidden');
const placeholder  = document.getElementById('tagPlaceholder');

// ─── Render badges from the tags[] array ────────────────────────────────────
function renderBadges() {
    // Remove all badge spans (keep the placeholder span)
    badgesEl.querySelectorAll('.tag-badge').forEach(el => el.remove());

    // Show/hide placeholder
    placeholder.style.display = tags.length === 0 ? 'inline' : 'none';

    // Build one badge per tag
    tags.forEach(function (tag) {
        const span = document.createElement('span');
        span.className = 'tag-badge badge bg-primary d-inline-flex align-items-center gap-1';
        span.style.fontSize = '.8rem';
        span.style.lineHeight = '1.6';

        const label = document.createTextNode('#' + tag + ' ');
        span.appendChild(label);

        const btn = document.createElement('button');
        btn.type = 'button';
        btn.setAttribute('aria-label', 'Remove ' + tag);
        btn.style.cssText = 'background:none;border:none;color:#fff;padding:0 0 0 2px;line-height:1;cursor:pointer;font-size:.85em;';
        btn.textContent = '✕';
        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            removeTag(tag);
        });

        span.appendChild(btn);
        // Insert before the placeholder so badges appear first
        badgesEl.insertBefore(span, placeholder);
    });

    // Keep the hidden input in sync for form submission
    hiddenInput.value = tags.join(', ');
}

// ─── Add a tag ───────────────────────────────────────────────────────────────
function addTag(name) {
    const clean = name.trim().replace(/,/g, '').trim();
    if (!clean) return;

    // Case-insensitive duplicate check
    const exists = tags.some(t => t.toLowerCase() === clean.toLowerCase());
    if (!exists) {
        tags.push(clean);
        renderBadges();
    }

    // Always clear the typing box after an add attempt
    typingInput.value = '';
}

// ─── Remove a tag ────────────────────────────────────────────────────────────
function removeTag(name) {
    tags = tags.filter(t => t.toLowerCase() !== name.toLowerCase());
    renderBadges();
}

// ─── Keyboard handling in the typing box ─────────────────────────────────────
typingInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        const val = typingInput.value.replace(/,/g, '').trim();
        if (val) addTag(val);
        else typingInput.value = '';
        return;
    }

    // Backspace on empty input removes the last tag
    if (e.key === 'Backspace' && typingInput.value === '' && tags.length > 0) {
        tags.pop();
        renderBadges();
    }
});

// If user types a comma mid-word, treat it as a separator
typingInput.addEventListener('input', function () {
    if (typingInput.value.includes(',')) {
        const parts = typingInput.value.split(',');
        // All parts except the last are completed tags
        parts.slice(0, -1).forEach(function (p) {
            const clean = p.trim();
            if (clean) addTag(clean);
        });
        // Leave whatever comes after the last comma in the box
        typingInput.value = parts[parts.length - 1].trim();
    }
});

// Add remaining text as a tag when user leaves the field
typingInput.addEventListener('blur', function () {
    const val = typingInput.value.replace(/,/g, '').trim();
    if (val) addTag(val);
});

// Initial render (handles old() repopulation)
renderBadges();
</script>
@endpush

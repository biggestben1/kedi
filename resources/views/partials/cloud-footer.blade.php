@if(isset($cloudFooter) && $cloudFooter && filled(trim((string) ($cloudFooter->body ?? ''))))
<div class="cloud-footer border-top pt-3 pb-2 mt-2 text-center small text-muted">
    <div class="container">
        {!! $cloudFooter->body !!}
    </div>
</div>
@endif

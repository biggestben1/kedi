{{-- Microsoft Word–like typography: Calibri 11pt, 1.15 line spacing, A4 page --}}
<style>
/* Page “paper” (grey canvas + white sheet) */
.word-page {
    background: #e7e6e6;
    padding: 1.25rem 0.75rem;
    border-radius: 4px;
}
.word-document-inner {
    max-width: 8.5in;
    margin: 0 auto;
    background: #fff;
    box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.08), 0 2px 8px rgba(0, 0, 0, 0.12);
    min-height: 10in;
    padding: 1in;
    color: #000000;
    font-family: Calibri, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    font-size: 11pt;
    line-height: 1.15;
    text-align: left;
}
.word-document-inner .word-title {
    font-family: Calibri, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    font-size: 26pt;
    font-weight: 400;
    line-height: 1.2;
    color: #000000;
    margin: 0 0 0.75em;
    letter-spacing: -0.02em;
}
/* Body text (read view) */
.blog-post-body.word-style {
    font-family: Calibri, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    font-size: 11pt;
    line-height: 1.15;
    color: #000000;
    word-wrap: break-word;
}
.blog-post-body.word-style p {
    margin: 0 0 0.5em;
}
.blog-post-body.word-style br + br {
    display: block;
    content: "";
    margin-top: 0.5em;
}
.blog-post-body.word-style h2,
.blog-post-body.word-style h3 {
    font-family: Calibri, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
    font-weight: 700;
    color: #000000;
    margin: 1em 0 0.35em;
}
.blog-post-body.word-style h2 { font-size: 16pt; }
.blog-post-body.word-style h3 { font-size: 14pt; }
.blog-post-body.word-style ul,
.blog-post-body.word-style ol {
    margin: 0 0 0.5em 1.25em;
    padding-left: 0.25em;
}
.blog-post-body.word-style li { margin-bottom: 0.25em; }
/* Editor: same look as a Word page */
textarea.word-content-editor {
    font-family: Calibri, "Segoe UI", "Helvetica Neue", Arial, sans-serif !important;
    font-size: 11pt !important;
    line-height: 1.15 !important;
    color: #000000 !important;
    background: #ffffff !important;
    border: 1px solid #bfbfbf !important;
    border-radius: 1px;
    padding: 1in !important;
    min-height: 10in;
    resize: vertical;
    box-shadow: inset 0 0 0 1px rgba(0, 0, 0, 0.04);
    width: 100%;
    max-width: 8.5in;
    margin-left: auto;
    margin-right: auto;
    display: block;
}
.word-editor-wrap {
    background: #e7e6e6;
    padding: 1rem 0.75rem;
    border-radius: 4px;
}
@media (max-width: 767.98px) {
    .word-document-inner {
        padding: 0.75in 0.6in;
        min-height: auto;
    }
    .word-document-inner .word-title { font-size: 22pt; }
    textarea.word-content-editor {
        padding: 0.75in 0.6in !important;
        min-height: 50vh;
    }
}
</style>

{{-- Matches public/sash/html/add-product.html → Product Description (.richText .richText-editor in style.css) --}}
<style>
/* Public post: read-only box = same white content area as the WYSIWYG editor (no toolbar) */
.blog-rich-readonly.richText {
    position: relative;
    width: 100%;
    margin-bottom: 0;
    border-radius: 7px;
    overflow: hidden;
}
.blog-rich-readonly .richText-editor {
    padding: 20px;
    background-color: #ffffff;
    font-family: Calibri, Verdana, Helvetica, sans-serif;
    height: auto !important;
    min-height: 120px !important;
    overflow-y: visible !important;
    overflow-x: auto;
    outline: none;
    border-left: #ffffff solid 2px;
}
.blog-rich-readonly .richText-editor ul,
.blog-rich-readonly .richText-editor ol {
    margin: 10px 25px;
}
.blog-rich-readonly .richText-editor table {
    margin: 10px 0;
    border-spacing: 0;
    width: 100%;
}
.blog-rich-readonly .richText-editor table td,
.blog-rich-readonly .richText-editor table th {
    padding: 10px;
    border: #efefef solid 1px;
}
.blog-rich-readonly .richText-editor img {
    max-width: 100%;
    height: auto;
}
/* Public post title — not an <h1> so pasted <style>h1{…}</style> in body cannot hide it */
.blog-post-title {
    display: block !important;
    font-size: 1.75rem;
    font-weight: 600;
    line-height: 1.3;
    color: #212529;
    word-wrap: break-word;
}
.dark-mode .blog-post-title {
    color: #f3f6f9;
}
</style>

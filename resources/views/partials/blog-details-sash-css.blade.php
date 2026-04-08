{{-- Aligns public blog post with Sash blog-details.html --}}
<style>
/* Fixed/sticky .app-header overlaps main content — push “Blog details” below the bar */
body.public-blog-page .main-content.app-content {
    padding-top: 5.75rem;
}
@media (max-width: 991px) {
    body.public-blog-page .main-content.app-content {
        padding-top: 5.25rem;
    }
}
.blog-details-cover {
    min-height: 220px;
    background: linear-gradient(135deg, var(--primary-bg-color, #6c5ffc) 0%, #8b7cff 45%, #a8c0ff 100%);
}
.blog-details-cover--placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    color: rgba(255,255,255,0.9);
    font-size: 3rem;
}
.blog-details-meta .fe {
    line-height: 1;
}
.recent-post-thumb {
    min-width: 72px;
    width: 72px;
    height: 72px;
    flex-shrink: 0;
    border-radius: 5px;
    background: linear-gradient(145deg, #e8e8f5 0%, #f4f4fb 100%);
    background-size: cover;
    background-position: center;
}
</style>

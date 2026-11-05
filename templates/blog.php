<?php
$currentLang = $SITE['lang'] ?? 'th';
$posts       = $BLOG_DATA['posts'] ?? [];
$categories  = $BLOG_DATA['categories'] ?? [];
$currentPost = $BLOG_DATA['current_post'] ?? null;
$prevPost    = $BLOG_DATA['prev_post'] ?? null;
$nextPost    = $BLOG_DATA['next_post'] ?? null;
$comments    = $BLOG_DATA['comments'] ?? [];
$totalComms  = (int)($BLOG_DATA['total_comments'] ?? 0);
$currentUser = $BLOG_DATA['current_user'] ?? null;
$isLoggedIn  = !empty($currentUser['user_id']);
$blogListUrl = URL_PATH . '/' . $currentLang . '/blog';
?>

<div class="yp-container content-page-container">
    <?php if ($currentPost): ?>
        <?php
        $blogId    = (int)$currentPost['blog_id'];
        $shareUrl  = ROOT_URL . '/' . $currentLang . '/blog/' . $blogId;
        $likesCnt  = (int)($currentPost['likes_count'] ?? 0);
        $userLiked = !empty($currentPost['user_liked']);
        $rawDetail = trim((string)($currentPost['blog_detail'] ?? ''));
        if ($rawDetail === '') {
            $rawDetail = '<p>' . nl2br(htmlspecialchars((string)($currentPost['blog_excerpt'] ?? ''))) . '</p>';
        } elseif ($rawDetail === strip_tags($rawDetail)) {
            $rawDetail = '<p>' . nl2br(htmlspecialchars($rawDetail)) . '</p>';
        }
        ?>
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb" class="content-breadcrumb">
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
            <span>/</span>
            <a href="<?= htmlspecialchars($blogListUrl) ?>"><?= $currentLang === 'th' ? 'บทความสมุนไพรนวดสปา' : 'Articles & News' ?></a>
            <span>/</span>
            <span><?= htmlspecialchars(mb_strimwidth((string)$currentPost['blog_name'], 0, 50, '...', 'UTF-8')) ?></span>
        </nav>

        <!-- Article Detail Card -->
        <article class="blog-detail-card">
            <div class="blog-detail-header">
                <span class="content-tag-gold"><?= htmlspecialchars((string)$currentPost['blogcat_name']) ?></span>
                <h1 class="blog-detail-title"><?= htmlspecialchars((string)$currentPost['blog_name']) ?></h1>
                <div class="blog-detail-meta">
                    <span>📅 <?= date('d M Y', strtotime($currentPost['blog_date'] ?? 'now')) ?></span>
                    <span>•</span>
                    <span>👁️ <?= number_format((int)($currentPost['blog_view'] ?? 0)) ?> views</span>
                    <span>•</span>
                    <span>❤️ <span id="metaLikeCount"><?= number_format($likesCnt) ?></span> ถูกใจ</span>
                    <span>•</span>
                    <span>💬 <span id="metaCommentCount"><?= number_format($totalComms) ?></span> ความคิดเห็น</span>
                </div>
            </div>

            <div class="blog-detail-cover-wrap">
                <img src="<?= htmlspecialchars((string)$currentPost['image_url']) ?>" alt="<?= htmlspecialchars((string)$currentPost['blog_name']) ?>" class="blog-detail-cover-img">
            </div>

            <div class="blog-detail-content">
                <?= $rawDetail ?>
            </div>

            <!-- Like & Share Bar -->
            <div class="blog-social-bar">
                <div class="blog-social-left">
                    <button type="button" id="btnBlogLike" class="blog-action-btn <?= $userLiked ? 'is-liked' : '' ?>" onclick="toggleBlogLike(<?= $blogId ?>, this)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="<?= $userLiked ? 'currentColor' : 'none' ?>" stroke="currentColor" stroke-width="2"><path d="M14 9V5a3 3 0 0 0-3-3l-4 9v11h11.28a2 2 0 0 0 2-1.7l1.38-9a2 2 0 0 0-2-2.3zM7 22H4a2 2 0 0 1-2-2v-7a2 2 0 0 1 2-2h3"></path></svg>
                        <span>ถูกใจ</span>
                        <span class="blog-like-badge" id="blogLikeCountBadge"><?= number_format($likesCnt) ?></span>
                    </button>

                    <button type="button" class="blog-action-btn" onclick="focusMainCommentBox()">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                        <span>แสดงความคิดเห็น (<span id="barCommentCount"><?= number_format($totalComms) ?></span>)</span>
                    </button>
                </div>

                <div class="blog-social-right">
                    <button type="button" class="blog-action-btn" onclick="shareArticle('<?= htmlspecialchars(addslashes((string)$currentPost['blog_name'])) ?>', '<?= htmlspecialchars($shareUrl) ?>')">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="18" cy="5" r="3"></circle><circle cx="6" cy="12" r="3"></circle><circle cx="18" cy="19" r="3"></circle><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"></line><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"></line></svg>
                        <span>แชร์บทความ</span>
                    </button>
                </div>
            </div>

            <!-- Left (Previous) & Right (Next) Navigation Buttons -->
            <div class="blog-prev-next-nav">
                <?php if ($prevPost): ?>
                    <a href="<?= htmlspecialchars((string)$prevPost['detail_url']) ?>" class="blog-nav-btn blog-nav-prev">
                        <span class="blog-nav-arrow">‹</span>
                        <div class="blog-nav-text">
                            <span class="blog-nav-label">บทความก่อนหน้า</span>
                            <span class="blog-nav-title"><?= htmlspecialchars((string)$prevPost['blog_name']) ?></span>
                        </div>
                    </a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($blogListUrl) ?>" class="blog-nav-btn blog-nav-prev">
                        <span class="blog-nav-arrow">‹</span>
                        <div class="blog-nav-text">
                            <span class="blog-nav-label">กลับไปหน้ารวม</span>
                            <span class="blog-nav-title">ดูบทความทั้งหมด</span>
                        </div>
                    </a>
                <?php endif; ?>

                <?php if ($nextPost): ?>
                    <a href="<?= htmlspecialchars((string)$nextPost['detail_url']) ?>" class="blog-nav-btn blog-nav-next">
                        <div class="blog-nav-text text-end">
                            <span class="blog-nav-label">บทความถัดไป</span>
                            <span class="blog-nav-title"><?= htmlspecialchars((string)$nextPost['blog_name']) ?></span>
                        </div>
                        <span class="blog-nav-arrow">›</span>
                    </a>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($blogListUrl) ?>" class="blog-nav-btn blog-nav-next">
                        <div class="blog-nav-text text-end">
                            <span class="blog-nav-label">กลับไปหน้ารวม</span>
                            <span class="blog-nav-title">ดูบทความทั้งหมด</span>
                        </div>
                        <span class="blog-nav-arrow">›</span>
                    </a>
                <?php endif; ?>
            </div>

            <!-- Facebook-Style Comments Section -->
            <section class="fb-comments-section" id="commentsSection">
                <h3 class="fb-comments-heading">
                    💬 ความคิดเห็น (<span id="headingCommentCount"><?= number_format($totalComms) ?></span>)
                </h3>

                <?php if ($isLoggedIn): ?>
                    <!-- Main Comment Composer -->
                    <form class="fb-comment-composer" onsubmit="submitBlogComment(event, <?= $blogId ?>, 0)">
                        <div class="fb-avatar">
                            <?php if (!empty($currentUser['avatar_url'])): ?>
                                <img src="<?= htmlspecialchars((string)$currentUser['avatar_url']) ?>" alt="Avatar" class="fb-avatar-img">
                            <?php else: ?>
                                <span class="fb-avatar-initial"><?= htmlspecialchars((string)$currentUser['initial']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="fb-composer-box">
                            <div class="fb-composer-user-tag">แสดงความเห็นในชื่อ <strong><?= htmlspecialchars((string)$currentUser['display_name']) ?></strong></div>
                            <div class="fb-composer-input-row">
                                <textarea id="mainCommentInput" class="fb-composer-textarea" rows="2" placeholder="เขียนความคิดเห็นของคุณ..." required></textarea>
                                <button type="submit" id="btnSubmitMainComment" class="fb-composer-send-btn">
                                    <span class="spinner-snake d-none" id="mainCommentSpinner"></span>
                                    <span id="mainCommentBtnText">โพสต์</span>
                                </button>
                            </div>
                        </div>
                    </form>
                <?php else: ?>
                    <!-- Login Prompt for Guests -->
                    <div class="fb-login-prompt">
                        <div class="fb-login-prompt-text">
                            🔒 กรุณาเข้าสู่ระบบสมาชิกก่อนร่วมแสดงความคิดเห็นและตอบกลับ
                        </div>
                        <div class="fb-login-prompt-actions">
                            <a href="<?= URL_PATH ?>/api/line_auth.php?action=login&amp;redirect=<?= urlencode(URL_PATH . '/' . $currentLang . '/blog/' . $blogId) ?>" class="fb-btn-line-login">
                                เข้าสู่ระบบด้วย LINE
                            </a>
                            <a href="<?= URL_PATH ?>/<?= $currentLang ?>/profile" class="fb-btn-member-login">
                                เข้าสู่ระบบสมาชิก
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Comments Thread List -->
                <div class="fb-comments-list" id="fbCommentsList">
                    <?php if (empty($comments)): ?>
                        <div class="fb-comments-empty" id="fbCommentsEmpty">
                            ยังไม่มีความคิดเห็น มาร่วมแสดงความคิดเห็นเป็นคนแรกกันเลย!
                        </div>
                    <?php else: ?>
                        <?php foreach ($comments as $comm): ?>
                            <?php $cid = (int)$comm['comment_id']; ?>
                            <div class="fb-comment-thread" id="comment-thread-<?= $cid ?>">
                                <!-- Parent Comment -->
                                <div class="fb-comment-item">
                                    <div class="fb-avatar">
                                        <?php if (!empty($comm['avatar_url'])): ?>
                                            <img src="<?= htmlspecialchars((string)$comm['avatar_url']) ?>" alt="Avatar" class="fb-avatar-img">
                                        <?php else: ?>
                                            <span class="fb-avatar-initial"><?= htmlspecialchars((string)$comm['initial']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fb-comment-main">
                                        <div class="fb-comment-bubble">
                                            <div class="fb-comment-author"><?= htmlspecialchars((string)$comm['display_name']) ?></div>
                                            <div class="fb-comment-text"><?= nl2br(htmlspecialchars((string)$comm['comment_text'])) ?></div>
                                        </div>
                                        <div class="fb-comment-actions">
                                            <button type="button" class="fb-comment-act-link <?= !empty($comm['user_liked']) ? 'is-liked' : '' ?>" onclick="likeComment(<?= $cid ?>, this)">
                                                ถูกใจ <span class="fb-comm-like-cnt"><?= (int)$comm['likes_count'] > 0 ? '(' . (int)$comm['likes_count'] . ')' : '' ?></span>
                                            </button>
                                            <span>·</span>
                                            <button type="button" class="fb-comment-act-link" onclick="openReplyBox(<?= $cid ?>, '<?= htmlspecialchars(addslashes((string)$comm['display_name'])) ?>')">
                                                ตอบกลับ
                                            </button>
                                            <span>·</span>
                                            <span class="fb-comment-time"><?= htmlspecialchars((string)$comm['created_text']) ?></span>
                                        </div>

                                        <!-- Replies List -->
                                        <div class="fb-replies-list" id="replies-for-<?= $cid ?>">
                                            <?php if (!empty($comm['replies'])): ?>
                                                <?php foreach ($comm['replies'] as $rep): ?>
                                                    <?php $rid = (int)$rep['comment_id']; ?>
                                                    <div class="fb-comment-item fb-reply-item" id="comment-item-<?= $rid ?>">
                                                        <div class="fb-avatar fb-avatar-sm">
                                                            <?php if (!empty($rep['avatar_url'])): ?>
                                                                <img src="<?= htmlspecialchars((string)$rep['avatar_url']) ?>" alt="Avatar" class="fb-avatar-img">
                                                            <?php else: ?>
                                                                <span class="fb-avatar-initial"><?= htmlspecialchars((string)$rep['initial']) ?></span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="fb-comment-main">
                                                            <div class="fb-comment-bubble fb-reply-bubble">
                                                                <div class="fb-comment-author"><?= htmlspecialchars((string)$rep['display_name']) ?></div>
                                                                <div class="fb-comment-text"><?= nl2br(htmlspecialchars((string)$rep['comment_text'])) ?></div>
                                                            </div>
                                                            <div class="fb-comment-actions">
                                                                <button type="button" class="fb-comment-act-link <?= !empty($rep['user_liked']) ? 'is-liked' : '' ?>" onclick="likeComment(<?= $rid ?>, this)">
                                                                    ถูกใจ <span class="fb-comm-like-cnt"><?= (int)$rep['likes_count'] > 0 ? '(' . (int)$rep['likes_count'] . ')' : '' ?></span>
                                                                </button>
                                                                <span>·</span>
                                                                <button type="button" class="fb-comment-act-link" onclick="openReplyBox(<?= $cid ?>, '<?= htmlspecialchars(addslashes((string)$rep['display_name'])) ?>')">
                                                                    ตอบกลับ
                                                                </button>
                                                                <span>·</span>
                                                                <span class="fb-comment-time"><?= htmlspecialchars((string)$rep['created_text']) ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Inline Reply Form (Hidden until Reply clicked) -->
                                        <div class="fb-reply-composer-wrap d-none" id="reply-box-<?= $cid ?>">
                                            <?php if ($isLoggedIn): ?>
                                                <form class="fb-reply-form" onsubmit="submitBlogComment(event, <?= $blogId ?>, <?= $cid ?>)">
                                                    <input type="text" id="reply-input-<?= $cid ?>" class="fb-reply-input" placeholder="ตอบกลับ..." required>
                                                    <button type="submit" id="reply-btn-<?= $cid ?>" class="fb-reply-send-btn">ส่ง</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </article>
    <?php else: ?>
        <!-- ============================================================
             BLOG LISTING VIEW (/th/blog)
             ============================================================ -->
        <nav aria-label="breadcrumb" class="content-breadcrumb">
            <a href="<?= URL_PATH ?>/<?= $currentLang ?>"><?= $currentLang === 'th' ? 'หน้าแรก' : 'Home' ?></a>
            <span>/</span>
            <span><?= $currentLang === 'th' ? 'บทความสมุนไพรนวดสปา (Articles & News)' : 'Articles & News' ?></span>
        </nav>

        <div class="content-page-hero">
            <span class="content-tag-gold">WELLNESS KNOWLEDGE &amp; SPA INSIGHTS</span>
            <h1 class="content-page-title">
                <?= $currentLang === 'th' ? 'บทความสมุนไพรนวดสปา &amp; สาระน่ารู้' : 'Herbal Spa Articles & Insights' ?>
            </h1>
            <p class="content-page-desc">
                <?= $currentLang === 'th' 
                    ? 'เรื่องราวความรู้เกี่ยวกับสมุนไพรไทย ศาสตร์การบำบัดกลิ่น (Aromatherapy) เคล็ดลับการบริหารร้านสปา และมาตรฐานบาล์มส่งออกสากล'
                    : 'Ancient Thai herbal wisdom, aromatherapy science, spa business insights, and international export standards.' ?>
            </p>
        </div>

        <?php if (empty($posts)): ?>
            <div class="content-page-hero text-center py-5">
                <p class="text-muted"><?= $currentLang === 'th' ? 'ยังไม่มีบทความในขณะนี้' : 'No articles available at the moment.' ?></p>
            </div>
        <?php else: ?>
            <div class="article-grid">
                <?php foreach ($posts as $post): ?>
                    <?php $detailLink = $post['detail_url'] ?? (URL_PATH . '/' . $currentLang . '/blog/' . (int)$post['blog_id']); ?>
                    <article class="article-card">
                        <a href="<?= htmlspecialchars($detailLink) ?>" class="article-thumb-wrap article-thumb-link">
                            <img src="<?= htmlspecialchars((string)$post['image_url']) ?>" alt="<?= htmlspecialchars((string)$post['blog_name']) ?>" class="article-thumb-img" loading="lazy">
                            <span class="article-cat-badge">
                                <?= htmlspecialchars((string)$post['blogcat_name']) ?>
                            </span>
                            <div class="article-thumb-overlay">
                                <span class="article-overlay-btn">ดูรายละเอียด ›</span>
                            </div>
                        </a>

                        <div class="article-body">
                            <div class="article-meta-row">
                                <span><?= date('d M Y', strtotime($post['blog_date'] ?? 'now')) ?></span>
                                <span>•</span>
                                <span><?= number_format((int)($post['blog_view'] ?? 0)) ?> views</span>
                                <?php if ((int)($post['likes_count'] ?? 0) > 0): ?>
                                    <span>•</span>
                                    <span>❤️ <?= number_format((int)$post['likes_count']) ?></span>
                                <?php endif; ?>
                                <?php if ((int)($post['comments_count'] ?? 0) > 0): ?>
                                    <span>•</span>
                                    <span>💬 <?= number_format((int)$post['comments_count']) ?></span>
                                <?php endif; ?>
                            </div>
                            <h2 class="article-title">
                                <a href="<?= htmlspecialchars($detailLink) ?>" class="article-title-link">
                                    <?= htmlspecialchars((string)$post['blog_name']) ?>
                                </a>
                            </h2>
                            <p class="article-excerpt-text"><?= htmlspecialchars($post['blog_excerpt'] ?: strip_tags((string)($post['blog_detail'] ?? ''))) ?></p>
                            <div class="article-card-footer">
                                <a href="<?= htmlspecialchars($detailLink) ?>" class="article-readmore-btn">
                                    ดูรายละเอียด <span class="ms-1">→</span>
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
const BLOG_IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;
const BLOG_LOGIN_URL    = '<?= URL_PATH ?>/<?= $currentLang ?>/profile';

async function toggleBlogLike(blogId, btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'toggle_blog_like');
        fd.append('blog_id', blogId);
        const res = await fetch('<?= URL_PATH ?>/api/blog_interaction.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status) {
            btn.classList.toggle('is-liked', !!json.liked);
            const svg = btn.querySelector('svg');
            if (svg) svg.setAttribute('fill', json.liked ? 'currentColor' : 'none');
            const cntStr = Number(json.likes_count || 0).toLocaleString();
            const badge = document.getElementById('blogLikeCountBadge');
            const meta = document.getElementById('metaLikeCount');
            if (badge) badge.textContent = cntStr;
            if (meta) meta.textContent = cntStr;
        }
    } catch (e) {}
    finally {
        btn.disabled = false;
    }
}

function focusMainCommentBox() {
    const inp = document.getElementById('mainCommentInput');
    if (inp) {
        inp.focus();
        inp.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
        document.getElementById('commentsSection')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
}

function shareArticle(title, url) {
    if (navigator.share) {
        navigator.share({ title: title, url: url }).catch(() => {});
        return;
    }
    const fbUrl = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
    const lineUrl = 'https://social-plugins.line.me/lineit/share?url=' + encodeURIComponent(url);
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: 'แชร์บทความนี้',
            html: `
                <div class="d-flex flex-column gap-2 mt-2">
                    <a href="${fbUrl}" target="_blank" rel="noopener" class="btn btn-primary rounded-pill fw-bold">แชร์ไปยัง Facebook</a>
                    <a href="${lineUrl}" target="_blank" rel="noopener" class="btn btn-success rounded-pill fw-bold">แชร์ไปยัง LINE</a>
                    <button type="button" class="btn btn-outline-light rounded-pill fw-bold" onclick="navigator.clipboard.writeText('${url}'); Swal.fire({toast:true,position:'top-end',icon:'success',title:'คัดลอกลิงก์บทความแล้ว',timer:1500,showConfirmButton:false});">คัดลอกลิงก์บทความ</button>
                </div>
            `,
            showConfirmButton: false,
            showCloseButton: true
        });
    } else {
        navigator.clipboard.writeText(url);
    }
}

function openReplyBox(parentId, targetName) {
    if (!BLOG_IS_LOGGED_IN) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'info',
                title: 'กรุณาเข้าสู่ระบบ',
                text: 'เข้าสู่ระบบสมาชิกก่อนร่วมแสดงความคิดเห็นและตอบกลับ',
                showCancelButton: true,
                confirmButtonText: 'เข้าสู่ระบบ',
                cancelButtonText: 'ยกเลิก'
            }).then(r => {
                if (r.isConfirmed) window.location.href = BLOG_LOGIN_URL;
            });
        } else {
            window.location.href = BLOG_LOGIN_URL;
        }
        return;
    }
    const wrap = document.getElementById('reply-box-' + parentId);
    const inp  = document.getElementById('reply-input-' + parentId);
    if (wrap) {
        wrap.classList.remove('d-none');
        if (inp) {
            if (targetName && !inp.value) {
                inp.placeholder = 'ตอบกลับคุณ ' + targetName + '...';
            }
            inp.focus();
        }
    }
}

async function likeComment(commentId, btn) {
    if (btn.disabled) return;
    btn.disabled = true;
    try {
        const fd = new FormData();
        fd.append('action', 'like_comment');
        fd.append('comment_id', commentId);
        const res = await fetch('<?= URL_PATH ?>/api/blog_interaction.php', { method: 'POST', body: fd });
        const json = await res.json();
        if (json.status) {
            btn.classList.toggle('is-liked', !!json.liked);
            const cntSpan = btn.querySelector('.fb-comm-like-cnt');
            if (cntSpan) {
                cntSpan.textContent = json.likes_count > 0 ? '(' + json.likes_count + ')' : '';
            }
        }
    } catch (e) {}
    finally {
        btn.disabled = false;
    }
}

function escapeHtmlStr(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML.replace(/\n/g, '<br>');
}

async function submitBlogComment(e, blogId, parentId) {
    e.preventDefault();
    const isReply = parentId > 0;
    const inputEl = isReply ? document.getElementById('reply-input-' + parentId) : document.getElementById('mainCommentInput');
    const btnEl   = isReply ? document.getElementById('reply-btn-' + parentId) : document.getElementById('btnSubmitMainComment');
    if (!inputEl || !btnEl || btnEl.disabled) return;

    const text = inputEl.value.trim();
    if (!text) return;

    btnEl.disabled = true;
    if (!isReply) {
        document.getElementById('mainCommentSpinner')?.classList.remove('d-none');
    }

    try {
        const fd = new FormData();
        fd.append('action', 'add_comment');
        fd.append('blog_id', blogId);
        fd.append('parent_id', parentId);
        fd.append('comment_text', text);

        const res = await fetch('<?= URL_PATH ?>/api/blog_interaction.php', { method: 'POST', body: fd });
        const json = await res.json();

        if (json.status && json.comment) {
            const c = json.comment;
            inputEl.value = '';
            document.getElementById('fbCommentsEmpty')?.remove();

            const avatarHtml = c.avatar_url
                ? `<img src="${escapeHtmlStr(c.avatar_url)}" alt="Avatar" class="fb-avatar-img">`
                : `<span class="fb-avatar-initial">${escapeHtmlStr(c.initial)}</span>`;

            if (isReply) {
                const repliesContainer = document.getElementById('replies-for-' + parentId);
                if (repliesContainer) {
                    const replyHtml = `
                        <div class="fb-comment-item fb-reply-item" id="comment-item-${c.comment_id}">
                            <div class="fb-avatar fb-avatar-sm">${avatarHtml}</div>
                            <div class="fb-comment-main">
                                <div class="fb-comment-bubble fb-reply-bubble">
                                    <div class="fb-comment-author">${escapeHtmlStr(c.display_name)}</div>
                                    <div class="fb-comment-text">${escapeHtmlStr(c.comment_text)}</div>
                                </div>
                                <div class="fb-comment-actions">
                                    <button type="button" class="fb-comment-act-link" onclick="likeComment(${c.comment_id}, this)">
                                        ถูกใจ <span class="fb-comm-like-cnt"></span>
                                    </button>
                                    <span>·</span>
                                    <button type="button" class="fb-comment-act-link" onclick="openReplyBox(${parentId}, '${escapeHtmlStr(c.display_name)}')">
                                        ตอบกลับ
                                    </button>
                                    <span>·</span>
                                    <span class="fb-comment-time">${escapeHtmlStr(c.created_text)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                    repliesContainer.insertAdjacentHTML('beforeend', replyHtml);
                }
            } else {
                const listEl = document.getElementById('fbCommentsList');
                if (listEl) {
                    const threadHtml = `
                        <div class="fb-comment-thread" id="comment-thread-${c.comment_id}">
                            <div class="fb-comment-item">
                                <div class="fb-avatar">${avatarHtml}</div>
                                <div class="fb-comment-main">
                                    <div class="fb-comment-bubble">
                                        <div class="fb-comment-author">${escapeHtmlStr(c.display_name)}</div>
                                        <div class="fb-comment-text">${escapeHtmlStr(c.comment_text)}</div>
                                    </div>
                                    <div class="fb-comment-actions">
                                        <button type="button" class="fb-comment-act-link" onclick="likeComment(${c.comment_id}, this)">
                                            ถูกใจ <span class="fb-comm-like-cnt"></span>
                                        </button>
                                        <span>·</span>
                                        <button type="button" class="fb-comment-act-link" onclick="openReplyBox(${c.comment_id}, '${escapeHtmlStr(c.display_name)}')">
                                            ตอบกลับ
                                        </button>
                                        <span>·</span>
                                        <span class="fb-comment-time">${escapeHtmlStr(c.created_text)}</span>
                                    </div>
                                    <div class="fb-replies-list" id="replies-for-${c.comment_id}"></div>
                                    <div class="fb-reply-composer-wrap d-none" id="reply-box-${c.comment_id}">
                                        <form class="fb-reply-form" onsubmit="submitBlogComment(event, ${blogId}, ${c.comment_id})">
                                            <input type="text" id="reply-input-${c.comment_id}" class="fb-reply-input" placeholder="ตอบกลับ..." required>
                                            <button type="submit" id="reply-btn-${c.comment_id}" class="fb-reply-send-btn">ส่ง</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    listEl.insertAdjacentHTML('beforeend', threadHtml);
                }
            }

            // Increment counters
            ['metaCommentCount', 'barCommentCount', 'headingCommentCount'].forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    const cur = parseInt(el.textContent.replace(/,/g, ''), 10) || 0;
                    el.textContent = (cur + 1).toLocaleString();
                }
            });
        } else if (json.require_login) {
            window.location.href = BLOG_LOGIN_URL;
        }
    } catch (err) {}
    finally {
        btnEl.disabled = false;
        if (!isReply) {
            document.getElementById('mainCommentSpinner')?.classList.add('d-none');
        }
    }
}
</script>

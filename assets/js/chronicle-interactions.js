(() => {
    'use strict';

    const escapeHtml = (value) => String(value == null ? '' : value).replace(/[&<>'"]/g, (character) => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        "'": '&#039;',
        '"': '&quot;'
    }[character]));

    const formatComment = (comment) => {
        const selectedClass = comment.viewer_has_liked ? ' is-selected' : '';
        const pressed = comment.viewer_has_liked ? 'true' : 'false';
        const author = escapeHtml(comment.author_name);
        const content = escapeHtml(comment.content).replace(/\n/g, '<br>');
        const createdAt = escapeHtml(comment.created_at);
        const createdLabel = escapeHtml(comment.created_label);
        const likes = Number(comment.likes_count == null ? 0 : comment.likes_count);

        return `<article class="chronicle-comment" data-chronicle-comment-id="${Number(comment.id)}">
            <div class="chronicle-comment__meta"><strong>${author}</strong><time datetime="${createdAt}">${createdLabel}</time></div>
            <p class="chronicle-comment__content">${content}</p>
            <button type="button" class="chronicle-comment__like${selectedClass}" data-chronicle-comment-like aria-pressed="${pressed}" aria-label="Polubienie ${likes}">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7.5 10.5H5.75A1.75 1.75 0 0 0 4 12.25v1.5c0 .97.78 1.75 1.75 1.75H7.5m0-5v5m0-5V8.75c0-1.1.9-2 2-2h.7c.44 0 .84.26 1.02.66l.6 1.34c.4.88 1.05 1.6 1.86 2.07l1.2.7c.7.4 1.12 1.14 1.12 1.95v.28c0 1.24-1.01 2.25-2.25 2.25H7.5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg><span>Polubienie</span><strong data-comment-likes>${likes}</strong>
            </button>
        </article>`;
    };

    const request = async (endpoint, payload) => {
        const response = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await response.json().catch(() => ({}));
        if (!response.ok || !data.ok) {
            throw new Error(data.error || 'Nie udało się wykonać tej akcji.');
        }
        return data;
    };

    const setStatus = (root, message, isError = false) => {
        const status = root.querySelector('[data-chronicle-status]');
        if (!status) {
            return;
        }
        status.textContent = message;
        status.classList.toggle('is-error', isError);
    };

    const updateVoteControls = (root, payload) => {
        const selectedVote = Number(payload.viewer_vote);
        root.querySelectorAll('[data-chronicle-vote]').forEach((button) => {
            const isSelected = Number(button.dataset.chronicleVote) === selectedVote;
            button.classList.toggle('is-selected', isSelected);
            button.setAttribute('aria-pressed', String(isSelected));
        });

        const positiveCount = Number(payload.positive_votes == null ? 0 : payload.positive_votes);
        const negativeCount = Number(payload.negative_votes == null ? 0 : payload.negative_votes);
        const positive = root.querySelector('[data-positive-votes]');
        const negative = root.querySelector('[data-negative-votes]');
        if (positive) {
            positive.textContent = String(positiveCount);
        }
        if (negative) {
            negative.textContent = String(negativeCount);
        }
        const positiveButton = root.querySelector('[data-chronicle-vote="1"]');
        const negativeButton = root.querySelector('[data-chronicle-vote="-1"]');
        if (positiveButton) {
            positiveButton.setAttribute('aria-label', `Na plus ${positiveCount}`);
        }
        if (negativeButton) {
            negativeButton.setAttribute('aria-label', `Na minus ${negativeCount}`);
        }
    };

    const updateCommentLike = (commentElement, comment) => {
        const button = commentElement.querySelector('[data-chronicle-comment-like]');
        if (!button) {
            return;
        }
        const likes = Number(comment.likes_count == null ? 0 : comment.likes_count);
        const selected = Boolean(comment.viewer_has_liked);
        const count = button.querySelector('[data-comment-likes]');
        if (count) {
            count.textContent = String(likes);
        }
        button.classList.toggle('is-selected', selected);
        button.setAttribute('aria-pressed', String(selected));
        button.setAttribute('aria-label', `Polubienie ${likes}`);
    };

    const bindInteractions = (root) => {
        const endpoint = root.dataset.endpoint;
        const postId = Number(root.dataset.postId);
        if (!endpoint || !postId) {
            return;
        }

        root.querySelectorAll('[data-chronicle-vote]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (button.disabled) {
                    return;
                }
                root.querySelectorAll('[data-chronicle-vote]').forEach((control) => {
                    control.disabled = true;
                });
                setStatus(root, 'Zapisywanie oceny…');
                try {
                    const payload = await request(endpoint, {action: 'vote', post_id: postId, vote: Number(button.dataset.chronicleVote)});
                    updateVoteControls(root, payload);
                    setStatus(root, payload.recorded ? 'Ocena została zapisana.' : 'Twoja ocena tego wpisu jest już zapisana.');
                } catch (error) {
                    setStatus(root, error.message || 'Nie udało się zapisać oceny.', true);
                } finally {
                    root.querySelectorAll('[data-chronicle-vote]').forEach((control) => {
                        control.disabled = false;
                    });
                }
            });
        });

        const form = root.querySelector('[data-chronicle-comment-form]');
        const comments = root.querySelector('[data-chronicle-comments]');
        if (form && comments) {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                const submit = form.querySelector('button[type="submit"]');
                if (submit && submit.disabled) {
                    return;
                }
                const formData = new FormData(form);
                if (submit) {
                    submit.disabled = true;
                }
                setStatus(root, 'Publikowanie komentarza…');
                try {
                    const payload = await request(endpoint, {
                        action: 'comment',
                        post_id: postId,
                        author_name: String(formData.get('author_name') || ''),
                        content: String(formData.get('content') || '')
                    });
                    comments.insertAdjacentHTML('beforeend', formatComment(payload.comment));
                    form.reset();
                    setStatus(root, 'Komentarz został opublikowany.');
                } catch (error) {
                    setStatus(root, error.message || 'Nie udało się opublikować komentarza.', true);
                } finally {
                    if (submit) {
                        submit.disabled = false;
                    }
                }
            });
        }

        root.addEventListener('click', async (event) => {
            const button = event.target.closest('[data-chronicle-comment-like]');
            if (!button || button.disabled) {
                return;
            }
            const commentElement = button.closest('[data-chronicle-comment-id]');
            const commentId = Number(commentElement ? commentElement.dataset.chronicleCommentId : 0);
            if (!commentElement || !commentId) {
                return;
            }
            button.disabled = true;
            try {
                const payload = await request(endpoint, {action: 'comment_like', comment_id: commentId});
                updateCommentLike(commentElement, payload.comment);
                setStatus(root, payload.recorded ? 'Polubienie komentarza zostało zapisane.' : 'To polubienie komentarza jest już zapisane.');
            } catch (error) {
                setStatus(root, error.message || 'Nie udało się zapisać polubienia.', true);
            } finally {
                button.disabled = false;
            }
        });
    };

    document.querySelectorAll('[data-chronicle-interactions]').forEach(bindInteractions);
})();

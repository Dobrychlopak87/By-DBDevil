(function () {
    'use strict';

    function setMessage(element, text, isError) {
        if (!element) {
            return;
        }
        element.textContent = text;
        element.classList.toggle('is-error', Boolean(isError));
    }

    function updateResults(card, payload) {
        var resultRows = card.querySelectorAll('[data-poll-result]');
        Array.prototype.forEach.call(resultRows, function (row) {
            var optionId = Number(row.getAttribute('data-option-id'));
            var option = payload.options.find(function (item) {
                return Number(item.id) === optionId;
            });
            if (!option) {
                return;
            }
            var percentage = row.querySelector('[data-poll-percentage]');
            var bar = row.querySelector('[data-poll-bar]');
            if (percentage) {
                percentage.textContent = String(option.percentage) + '%';
            }
            if (bar) {
                bar.style.width = String(option.percentage) + '%';
            }
            row.classList.toggle('is-selected', Number(payload.selected_option_id) === optionId);
        });

        var total = card.querySelector('[data-poll-total]');
        if (total) {
            total.textContent = 'Łącznie: ' + String(payload.total_votes) + ' głosów';
        }
    }

    function bindPoll(card) {
        var form = card.querySelector('[data-poll-form]');
        var results = card.querySelector('[data-poll-results]');
        var message = card.querySelector('[data-poll-message]');
        if (!form || !results || !message) {
            return;
        }

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var selected = form.querySelector('input[name="poll_option"]:checked');
            if (!selected) {
                setMessage(message, 'Wybierz jedną odpowiedź przed oddaniem głosu.', true);
                return;
            }

            var submit = form.querySelector('button[type="submit"]');
            if (submit) {
                submit.disabled = true;
            }
            setMessage(message, 'Zapisywanie głosu…', false);

            fetch('poll-vote.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    poll_id: Number(card.getAttribute('data-poll-id')),
                    option_id: Number(selected.value)
                })
            })
                .then(function (response) {
                    return response.json().then(function (payload) {
                        if (!response.ok || !payload.ok) {
                            throw new Error(payload.error || 'Nie udało się zapisać głosu.');
                        }
                        return payload;
                    });
                })
                .then(function (payload) {
                    updateResults(card, payload);
                    form.hidden = true;
                    results.hidden = false;
                    setMessage(message, payload.recorded ? 'Twój głos został zapisany.' : 'Twój głos został już zapisany.', false);
                })
                .catch(function (error) {
                    setMessage(message, error.message || 'Nie udało się zapisać głosu. Spróbuj ponownie.', true);
                    if (submit) {
                        submit.disabled = false;
                    }
                });
        });
    }

    var cards = document.querySelectorAll('[data-poll-card]');
    Array.prototype.forEach.call(cards, bindPoll);
}());

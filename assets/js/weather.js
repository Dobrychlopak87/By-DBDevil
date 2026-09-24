(function () {
    'use strict';

    function setText(id, value) {
        var element = document.getElementById(id);
        if (element) {
            element.textContent = value;
        }
    }

    function reveal(id) {
        var element = document.getElementById(id);
        if (element) {
            element.hidden = false;
        }
    }

    function isValidWeather(data) {
        return data
            && data.ok === true
            && typeof data.city === 'string'
            && typeof data.icon === 'string'
            && typeof data.condition === 'string'
            && typeof data.temperature === 'number'
            && typeof data.wind === 'number';
    }

    function renderWeather(data) {
        if (!isValidWeather(data)) {
            return;
        }

        setText('header-weather-summary', data.icon + ' ' + data.temperature + '°');
        setText('menu-weather-city', data.city);
        setText('menu-weather-condition', data.icon + ' ' + data.condition);
        setText('menu-weather-temperature', data.temperature + '°C');
        setText('menu-weather-wind', 'Wiatr ' + data.wind + ' km/h');
        reveal('header-weather');
        reveal('hamburger-weather');
    }

    function loadWeather() {
        if (!window.fetch) {
            return;
        }

        window.fetch(window.location.origin + '/weather.php', { cache: 'no-store', credentials: 'same-origin' })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Pogoda niedostępna.');
                }
                return response.json();
            })
            .then(renderWeather)
            .catch(function () {});
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadWeather);
    } else {
        loadWeather();
    }
}());

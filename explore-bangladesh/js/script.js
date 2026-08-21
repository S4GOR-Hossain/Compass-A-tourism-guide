// Explore Bangladesh — shared front-end behaviour

document.addEventListener('DOMContentLoaded', () => {
  // Mobile nav toggle
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('mainNav');
  if (toggle && nav) {
    toggle.addEventListener('click', () => nav.classList.toggle('open'));
  }

  // Dark mode toggle (theme already pre-applied by inline script in <head>)
  const themeToggle = document.getElementById('themeToggle');
  const root = document.documentElement;

  function syncThemeToggleIcon() {
    if (!themeToggle) return;
    const isDark = root.getAttribute('data-theme') === 'dark';
    const icon = themeToggle.querySelector('.theme-toggle-icon');
    if (icon) icon.textContent = isDark ? '☀️' : '🌙';
    themeToggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
  }

  syncThemeToggleIcon();

  if (themeToggle) {
    themeToggle.addEventListener('click', () => {
      const isDark = root.getAttribute('data-theme') === 'dark';
      if (isDark) {
        root.removeAttribute('data-theme');
        try { localStorage.setItem('theme', 'light'); } catch (e) {}
      } else {
        root.setAttribute('data-theme', 'dark');
        try { localStorage.setItem('theme', 'dark'); } catch (e) {}
      }
      syncThemeToggleIcon();
    });
  }

  // Favourite (heart) buttons — AJAX toggle
  document.querySelectorAll('.fav-btn, .fav-btn-hero').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const destId = btn.dataset.destId;
      try {
        const res = await fetch('/api/toggle_favourite.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ destination_id: destId })
        });
        const data = await res.json();
        if (data.status === 'login_required') {
          window.location.href = '/login.php';
          return;
        }
        const icon = data.is_favourite ? '❤️' : '🤍';
        const iconEl = btn.querySelector('.fav-icon');
        if (iconEl) {
          iconEl.textContent = icon; // labeled hero button — keep the text, swap the icon only
        } else {
          btn.textContent = icon; // small card overlay icon
        }
      } catch (err) {
        console.error('Favourite toggle failed', err);
      }
    });
  });

  // Weather planner form (AJAX forecast lookup)
  const weatherForm = document.getElementById('weatherForm');
  if (weatherForm) {
    weatherForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const destinationId = document.getElementById('destinationSelect').value;
      const travelDate = document.getElementById('travelDate').value;
      const resultBox = document.getElementById('weatherResult');

      resultBox.innerHTML = '<p>Loading forecast…</p>';

      try {
        const res = await fetch(`/api/get_weather.php?destination_id=${encodeURIComponent(destinationId)}&travel_date=${encodeURIComponent(travelDate)}`);
        const data = await res.json();
        renderWeatherResult(data, resultBox);
      } catch (err) {
        resultBox.innerHTML = '<p>Could not load forecast right now. Please try again.</p>';
        console.error(err);
      }
    });
  }
});

function weatherIcon(condition) {
  const map = {
    Clear: '☀️', Clouds: '⛅', Rain: '🌧️', Thunderstorm: '⛈️',
    Drizzle: '🌦️', Mist: '🌫️', Fog: '🌫️', Snow: '❄️'
  };
  return map[condition] || '🌤️';
}

function renderWeatherResult(data, box) {
  if (!data.forecast || data.forecast.length === 0) {
    box.innerHTML = '<div class="info-note">No forecast data yet — add your OpenWeatherMap API key in <code>config/weather_api.php</code>, or check back once cached data is available.</div>';
    return;
  }

  let strip = '<div class="forecast-strip">';
  data.forecast.forEach(day => {
    const d = new Date(day.forecast_date);
    const label = d.toLocaleDateString('en-US', { weekday: 'short', day: 'numeric' });
    strip += `
      <div class="forecast-day">
        <div class="d">${label}</div>
        <div class="icon">${weatherIcon(day.condition_main)}</div>
        <div class="t">${day.temp_max}° / ${day.temp_min}°</div>
        <div style="font-size:.72rem;color:#8aa;margin-top:4px;">${day.rain_probability}% rain</div>
      </div>`;
  });
  strip += '</div>';

  let adviceHtml = '';
  if (data.advice && data.advice.found) {
    const cls = data.advice.suggest_alternate ? 'advice-box warn' : 'advice-box';
    adviceHtml = `
      <div class="${cls}">
        <h4>${data.advice.advice.label}</h4>
        <p style="margin-bottom:0;">
          Forecast for ${data.advice.day.forecast_date}: ${data.advice.day.condition_main},
          ${data.advice.day.temp_min}°–${data.advice.day.temp_max}°C, ${data.advice.day.rain_probability}% rain chance.
          ${data.advice.suggest_alternate
            ? ' We suggest shifting your trip a day or two, or choosing an indoor/heritage destination instead.'
            : ' Looks like a solid day to travel.'}
        </p>
      </div>`;
  } else {
    adviceHtml = `
      <div class="info-note" style="margin-top:16px;">
        We can only forecast the next 5 days (up to ${data.forecast[data.forecast.length - 1].forecast_date}).
        Your selected travel date is further out, so we can't give a go/wait recommendation yet —
        check back closer to your trip.
      </div>`;
  }

  box.innerHTML = strip + adviceHtml;
}
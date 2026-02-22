<!-- includes/weather_alert.php -->
<div id="weather-alert-container" style="display: none; margin-bottom: 24px;"></div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    function fetchWeather(lat, lon) {
        const url = `https://api.open-meteo.com/v1/forecast?latitude=${lat}&longitude=${lon}&current_weather=true`;
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                const weather = data.current_weather;
                const code = weather.weathercode;
                const temp = weather.temperature;
                
                let alertColor = "#e2e8f0";
                let alertIcon = "<i data-lucide='cloud' style='width:32px; height:32px;'></i>";
                let alertTitle = "Weather Update";
                let alertMessage = `Current temperature is ${temp}°C. Have a safe trip!`;
                
                // Calculate sunlight exposure based on time of day
                const hour = new Date().getHours();
                let sunTip = "";
                if (hour >= 6 && hour < 12) {
                    sunTip = "☀️ <strong>Sun Position: East (Morning).</strong> Sit on the <strong>West (Left if heading North, Right if heading South)</strong> side for less sun exposure and heat.";
                } else if (hour >= 12 && hour < 17) {
                    sunTip = "☀️ <strong>Sun Position: West (Afternoon).</strong> Sit on the <strong>East (Right if heading North, Left if heading South)</strong> side for less sun exposure and heat.";
                } else if (hour >= 17 && hour <= 19) {
                    sunTip = "☀️ <strong>Sun Position: Low West (Evening).</strong> Sit on the <strong>East (Right side)</strong> for less glare.";
                } else {
                    sunTip = "🌙 No direct sun right now, both sides are comfortable.";
                }

                // WMO Weather interpretation codes
                // 0: Clear sky
                if (code === 0) {
                    alertColor = "#fef08a"; // Yellow
                    alertIcon = "<i data-lucide='sun' style='width:32px; height:32px;'></i>";
                    alertTitle = "Sunny & Clear";
                    alertMessage = `Current temperature is ${temp}°C. It's very sunny today! Don't forget to wear sunscreen. <br><br>${sunTip}`;
                }
                // 1, 2, 3: Cloudy
                else if (code === 1 || code === 2 || code === 3) {
                    alertColor = "#f1f5f9"; // Light Gray
                    alertIcon = "<i data-lucide='cloud-sun' style='width:32px; height:32px;'></i>";
                    alertTitle = "Partly Cloudy";
                }
                // 51 - 65: Drizzle / Rain
                else if ((code >= 51 && code <= 67) || (code >= 80 && code <= 82)) {
                    alertColor = "#bfdbfe"; // Blue
                    alertIcon = "<i data-lucide='cloud-rain' style='width:32px; height:32px;'></i>";
                    alertTitle = "Rain Detected";
                    alertMessage = `Current temperature is ${temp}°C. It's raining! Please remember to carry an umbrella and step carefully when boarding.`;
                }
                // 71 - 77, 85, 86: Snow
                else if (code >= 71 && code <= 86) {
                    alertColor = "#e0f2fe"; // Light Blue
                    alertIcon = "<i data-lucide='snowflake' style='width:32px; height:32px;'></i>";
                    alertTitle = "Snow / Cold Warning";
                    alertMessage = `Current temperature is ${temp}°C. It's snowing or very cold. Please dress warmly and beware of slippery steps.`;
                }
                // 95 - 99: Thunderstorm
                else if (code >= 95) {
                    alertColor = "#fecaca"; // Red
                    alertIcon = "<i data-lucide='cloud-lightning' style='width:32px; height:32px;'></i>";
                    alertTitle = "Thunderstorm Warning";
                    alertMessage = `Current temperature is ${temp}°C. Severe weather detected. Please stay indoors when possible and follow driver instructions carefully!`;
                }

                // Inject specific heat warning if extremely hot regardless of sun
                if (temp > 30) {
                    alertColor = "#fed7aa"; // Orange
                    alertIcon = "<i data-lucide='flame' style='width:32px; height:32px;'></i>";
                    alertTitle = "Extreme Heat Advisory";
                    let heatTip = "";
                    if (code === 0) { // If it's sunny and hot, remind them of the sun position
                       heatTip = "<br><br>" + sunTip;
                    }
                    alertMessage = `Temperature is a scorching ${temp}°C! Hydrate well, use sunscreen, and try to sit away from direct sunlight.${heatTip}`;
                }
                
                const alertHtml = `
                    <div style="background: ${alertColor}; border-radius: 12px; padding: 20px; display: flex; align-items: flex-start; gap: 16px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);">
                        <div style="font-size: 32px; line-height: 1;">${alertIcon}</div>
                        <div>
                            <h3 style="margin: 0 0 8px 0; font-size: 18px; color: #1e293b;">${alertTitle}</h3>
                            <p style="margin: 0; color: #475569; font-size: 15px; line-height: 1.5;">${alertMessage}</p>
                        </div>
                    </div>
                `;
                
                const container = document.getElementById('weather-alert-container');
                container.innerHTML = alertHtml;
                container.style.display = 'block';

                if (typeof lucide !== 'undefined') {
                    lucide.createIcons();
                }
            })
            .catch(err => console.error("Weather fetch failed:", err));
    }

    // Attempt geolocation
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            (position) => {
                fetchWeather(position.coords.latitude, position.coords.longitude);
            },
            (error) => {
                // Fallback coordinates (e.g., New York)
                console.log("Geolocation denied or failed, using fallback.");
                fetchWeather(40.7128, -74.0060);
            }
        );
    } else {
        // Fallback
        fetchWeather(40.7128, -74.0060);
    }
});
</script>

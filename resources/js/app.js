import Chart from 'chart.js/auto';

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch((error) => {
            console.error('Service Worker registration failed:', error);
        });
    });
}

// Make Chart available globally for Blade templates
window.Chart = Chart;

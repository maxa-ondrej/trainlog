import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default class extends Controller {
    static targets = ['canvas', 'metric'];
    static values = {
        url: String,
    };

    async connect() {
        const response = await fetch(this.urlValue, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            return;
        }
        this.data = await response.json();
        this.render('maxWeight');
    }

    switch(event) {
        const metric = event.target.value;
        this.render(metric);
    }

    render(metric) {
        if (!this.data) {
            return;
        }
        const ctx = this.canvasTarget.getContext('2d');
        if (this.chart) {
            this.chart.destroy();
        }
        const labelText = {
            maxWeight: 'Max váha (kg)',
            volume: 'Objem (kg)',
            estimated1rm: 'Odhad 1RM (kg)',
        }[metric] || metric;

        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.data.labels,
                datasets: [{
                    label: labelText,
                    data: this.data[metric],
                    borderColor: 'rgb(13, 110, 253)',
                    backgroundColor: 'rgba(13, 110, 253, 0.15)',
                    tension: 0.2,
                    fill: true,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true },
                },
            },
        });
    }
}

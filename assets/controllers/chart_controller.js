import { Controller } from '@hotwired/stimulus';
import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

export default class extends Controller {
    static targets = ['canvas'];
    static values = {
        url: String,
    };

    connect() {
        // Stimulus may call connect() multiple times (Turbo cache restore,
        // element morphing, etc.). Guard so we fetch + draw exactly once
        // per controller lifetime.
        if (this.loadPromise) {
            return;
        }
        this.loadPromise = this.load();
    }

    disconnect() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = undefined;
        }
    }

    async load() {
        const response = await fetch(this.urlValue, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });
        if (!response.ok) {
            return;
        }
        // `this.data` is a reserved getter on the Stimulus Controller base in
        // Stimulus 3+. Name the cache something else.
        this.chartData = await response.json();
        this.render('maxWeight');
    }

    switch(event) {
        this.render(event.target.value);
    }

    render(metric) {
        if (!this.chartData) {
            return;
        }
        const labelText = {
            maxWeight: 'Max váha (kg)',
            volume: 'Objem (kg)',
            estimated1rm: 'Odhad 1RM (kg)',
        }[metric] || metric;

        if (this.chart) {
            this.chart.data.labels = this.chartData.labels;
            this.chart.data.datasets[0].label = labelText;
            this.chart.data.datasets[0].data = this.chartData[metric];
            this.chart.update();
            return;
        }

        const ctx = this.canvasTarget.getContext('2d');
        this.chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.chartData.labels,
                datasets: [{
                    label: labelText,
                    data: this.chartData[metric],
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

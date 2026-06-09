import { Chart, registerables } from 'chart.js';

Chart.register(...registerables);

const chartInstances = new Map();

const cleanupCharts = () => {
    for (const [id, chart] of chartInstances.entries()) {
        const canvas = document.querySelector(`[data-chart-id="${id}"]`);

        if (!canvas) {
            chart.destroy();
            chartInstances.delete(id);
        }
    }
};

export const initCharts = () => {
    cleanupCharts();

    document.querySelectorAll('[data-chart-widget]').forEach((canvas) => {
        const id = canvas.dataset.chartId;
        const configElement = canvas.closest('[data-chart-root]')?.querySelector('[data-chart-config]');

        if (!id || !configElement?.textContent) {
            return;
        }

        try {
            const config = JSON.parse(configElement.textContent);

            if (chartInstances.has(id)) {
                chartInstances.get(id)?.destroy();
            }

            const context = canvas.getContext('2d');

            if (!context) {
                return;
            }

            chartInstances.set(id, new Chart(context, config));
        } catch (error) {
            console.error('Failed to initialize chart widget', error);
        }
    });
};

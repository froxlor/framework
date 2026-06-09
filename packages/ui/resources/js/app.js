import '@tailwindcss/browser';

let lucideModulePromise;
let chartsModulePromise;

const loadLucideModule = () => {
    lucideModulePromise ??= import('lucide');

    return lucideModulePromise;
};

const loadChartsModule = () => {
    chartsModulePromise ??= import('./charts.js');

    return chartsModulePromise;
};

const renderIconsIfPresent = async () => {
    if (!document.querySelector('[data-lucide]')) {
        return;
    }

    const { createIcons, icons } = await loadLucideModule();

    createIcons({ icons });
};

const initChartsIfPresent = async () => {
    if (!document.querySelector('[data-chart-widget]')) {
        return;
    }

    const { initCharts } = await loadChartsModule();

    initCharts();
};

const bootUi = async () => {
    await Promise.all([
        renderIconsIfPresent(),
        initChartsIfPresent(),
    ]);
};

document.addEventListener('DOMContentLoaded', bootUi);
document.addEventListener('livewire:navigated', bootUi);

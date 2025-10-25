(function () {
    const root = document.querySelector('[data-condrug-openfda]');
    if (!root) return;

    const input = root.querySelector('#condrug-openfda-query');
    const btn = root.querySelector('[data-condrug-openfda-search]');
    const statusEl = root.querySelector('.condrug-openfda__status');
    const contentEl = root.querySelector('.condrug-openfda__content');
    const summaryEl = root.querySelector('.condrug-openfda__summary');
    const preEl = root.querySelector('.condrug-openfda__pre');

    const chartEls = {
        reactions: root.querySelector('#condrug-chart-reactions'),
        ages: root.querySelector('#condrug-chart-ages'),
        gender: root.querySelector('#condrug-chart-gender'),
        year: root.querySelector('#condrug-chart-year'),
    };

    let charts = {};

    const showStatus = (message, type = 'info') => {
        if (!statusEl) return;
        statusEl.textContent = message || '';
        statusEl.className = 'condrug-openfda__status condrug-notice condrug-notice--' + (type === 'error' ? 'error' : type);
    };

    const setLoading = (loading) => {
        if (!btn) return;
        btn.disabled = loading;
        if (loading) {
            btn.classList.add('is-loading');
            showStatus((condrugOpenFDA && condrugOpenFDA.messages && condrugOpenFDA.messages.loading) || 'Loading…', 'info');
        } else {
            btn.classList.remove('is-loading');
        }
    };

    const fetchData = async (query) => {
        const ajaxUrl = (condrugOpenFDA && condrugOpenFDA.ajaxUrl) || (window.ajaxurl || '/wp-admin/admin-ajax.php');
        const nonce = condrugOpenFDA && condrugOpenFDA.nonce;

        const body = new URLSearchParams({
            action: 'condrug_openfda_query',
            nonce,
            q: query,
        });

        const res = await fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body,
        });
        const json = await res.json();
        if (!res.ok || !json.success) {
            const msg = (json && json.data && json.data.message) || (condrugOpenFDA && condrugOpenFDA.messages && condrugOpenFDA.messages.genericError) || 'Error';
            throw new Error(msg);
        }
        return json.data;
    };

    const toPalette = (n) => {
        const base = [
            '#4C51BF','#667EEA','#63B3ED','#48BB78','#F6AD55','#ED8936','#E53E3E','#9F7AEA','#38B2AC','#A0AEC0','#2B6CB0','#B794F4'
        ];
        return base[n % base.length];
    };

    const destroyCharts = () => {
        Object.values(charts).forEach((c) => { try { c.destroy(); } catch(e) {} });
        charts = {};
    };

    const renderCharts = (data) => {
        destroyCharts();
        if (!contentEl) return;
        contentEl.hidden = false;

        if (preEl) {
            try { preEl.textContent = JSON.stringify(data, null, 2); } catch (e) { /* ignore */ }
        }

        // Summary
        if (summaryEl) {
            summaryEl.innerHTML = '' +
                '<div class="condrug-summary-card">' +
                '<div><strong>Arama:</strong> ' + escapeHtml(data.query) + '</div>' +
                '<div><strong>Toplam Bildirim:</strong> ' + numberFormat(data.totals.reports) + '</div>' +
                '<div><strong>Benzersiz Yan Etki:</strong> ' + numberFormat(data.totals.uniqueReactions) + '</div>' +
                '</div>';
        }

        // Reactions Bar
        if (chartEls.reactions) {
            const labels = data.reactions.map(r => r.term);
            const values = data.reactions.map(r => r.count);
            charts.reactions = new Chart(chartEls.reactions, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Yan Etki Sayısı',
                        data: values,
                        backgroundColor: labels.map((_, i) => toPalette(i)),
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 0 } }, y: { beginAtZero: true } },
                }
            });
        }

        // Age Histogram (binning)
        if (chartEls.ages) {
            const bins = [0,10,20,30,40,50,60,70,80,90,100,120];
            const counts = new Array(bins.length - 1).fill(0);
            data.ages.forEach(({age, count}) => {
                for (let i = 0; i < bins.length - 1; i++) {
                    if (age >= bins[i] && age < bins[i+1]) {
                        counts[i] += count;
                        break;
                    }
                }
            });
            const labels = bins.slice(0, -1).map((b, i) => `${b}-${bins[i+1]-1}`);
            charts.ages = new Chart(chartEls.ages, {
                type: 'bar',
                data: {
                    labels,
                    datasets: [{
                        label: 'Vaka',
                        data: counts,
                        backgroundColor: labels.map((_, i) => toPalette(i)),
                    }]
                },
                options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }

        // Gender Pie
        if (chartEls.gender) {
            const labels = data.sexes.map(s => s.term);
            const values = data.sexes.map(s => s.count);
            charts.gender = new Chart(chartEls.gender, {
                type: 'pie',
                data: {
                    labels,
                    datasets: [{ data: values, backgroundColor: labels.map((_, i) => toPalette(i)) }]
                },
                options: { responsive: true }
            });
        }

        // Year Line
        if (chartEls.year) {
            const labels = data.years.map(y => y.year);
            const values = data.years.map(y => y.count);
            charts.year = new Chart(chartEls.year, {
                type: 'line',
                data: {
                    labels,
                    datasets: [{
                        label: 'Bildirim',
                        data: values,
                        borderColor: toPalette(0),
                        backgroundColor: 'rgba(76,81,191,0.2)',
                        tension: 0.25,
                        fill: true,
                    }]
                },
                options: { responsive: true, scales: { y: { beginAtZero: true } } }
            });
        }
    };

    const escapeHtml = (str) => {
        return (str || '').replace(/[&<>'"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', "'": '&#39;', '"': '&quot;' }[c]));
    };

    const numberFormat = (n) => {
        try { return new Intl.NumberFormat('tr-TR').format(n); } catch (e) { return String(n); }
    };

    const runSearch = async () => {
        const q = (input && input.value || '').trim();
        if (!q) return;
        setLoading(true);
        try {
            const data = await fetchData(q);
            showStatus('');
            renderCharts(data);
        } catch (e) {
            showStatus(e.message || (condrugOpenFDA && condrugOpenFDA.messages && condrugOpenFDA.messages.genericError) || 'Error', 'error');
            if (contentEl) contentEl.hidden = true;
        } finally {
            setLoading(false);
        }
    };

    if (btn) btn.addEventListener('click', runSearch);
    if (input) {
        input.placeholder = (condrugOpenFDA && condrugOpenFDA.messages && condrugOpenFDA.messages.placeholder) || input.placeholder;
        input.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                runSearch();
            }
        });
    }
})();

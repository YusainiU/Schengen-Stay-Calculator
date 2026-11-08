const form = document.querySelector('#calculator-form');

if (form) {
    const staysList = document.querySelector('#stays-list');
    const stayTemplate = document.querySelector('#stay-template');
    const addStayButton = document.querySelector('#add-stay');
    const stayCount = document.querySelector('#stay-count');
    const emptyStays = document.querySelector('#empty-stays');
    const formError = document.querySelector('#form-error');
    const results = document.querySelector('#results');
    const calculationDate = document.querySelector('#calculation-date');
    const countryNames = {
        AT: 'Austria',
        BE: 'Belgium',
        BG: 'Bulgaria',
        CH: 'Switzerland',
        CZ: 'Czechia',
        DE: 'Germany',
        DK: 'Denmark',
        EE: 'Estonia',
        ES: 'Spain',
        FI: 'Finland',
        FR: 'France',
        GR: 'Greece',
        HR: 'Croatia',
        HU: 'Hungary',
        IS: 'Iceland',
        IT: 'Italy',
        LI: 'Liechtenstein',
        LT: 'Lithuania',
        LU: 'Luxembourg',
        LV: 'Latvia',
        MT: 'Malta',
        NL: 'Netherlands',
        NO: 'Norway',
        PL: 'Poland',
        PT: 'Portugal',
        RO: 'Romania',
        SE: 'Sweden',
        SI: 'Slovenia',
        SK: 'Slovakia',
    };

    let countries = [];
    let latestResult = null;
    let latestStays = [];

    const today = new Date();
    calculationDate.value = [
        today.getFullYear(),
        String(today.getMonth() + 1).padStart(2, '0'),
        String(today.getDate()).padStart(2, '0'),
    ].join('-');

    function showError(message) {
        formError.textContent = message;
        formError.hidden = false;
    }

    function clearError() {
        formError.textContent = '';
        formError.hidden = true;
    }

    function clearResults() {
        latestResult = null;
        latestStays = [];
        results.hidden = true;
    }

    function updateStayCount() {
        const rows = staysList.querySelectorAll('.stay-row');
        stayCount.textContent = rows.length;
        emptyStays.hidden = rows.length > 0;
        rows.forEach((row, index) => {
            row.querySelector('.stay-index').textContent = String(index + 1).padStart(2, '0');
        });
    }

    function addStay() {
        clearResults();
        const row = stayTemplate.content.firstElementChild.cloneNode(true);
        const countrySelect = row.querySelector('.stay-country');

        countries.forEach((code) => {
            const option = document.createElement('option');
            option.value = code;
            option.textContent = `${countryNames[code] ?? code} (${code})`;
            countrySelect.append(option);
        });

        row.querySelector('.remove-stay').addEventListener('click', () => {
            clearResults();
            row.remove();
            updateStayCount();
        });
        staysList.append(row);
        updateStayCount();
        countrySelect.focus();
    }

    function formatDate(date) {
        return new Intl.DateTimeFormat(undefined, {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
            timeZone: 'UTC',
        }).format(new Date(`${date}T00:00:00Z`));
    }

    function formatDateRanges(dates) {
        if (dates.length === 0) {
            return 'None';
        }

        const ranges = [];
        let start = dates[0];
        let end = dates[0];

        dates.slice(1).forEach((date) => {
            const nextDay = new Date(`${end}T00:00:00Z`);
            nextDay.setUTCDate(nextDay.getUTCDate() + 1);
            const nextDate = nextDay.toISOString().slice(0, 10);

            if (date === nextDate) {
                end = date;
                return;
            }

            ranges.push(start === end ? formatDate(start) : `${formatDate(start)} – ${formatDate(end)}`);
            start = date;
            end = date;
        });

        ranges.push(start === end ? formatDate(start) : `${formatDate(start)} – ${formatDate(end)}`);

        return ranges.join(', ');
    }

    function renderBreakdown(stays) {
        const breakdownRows = document.querySelector('#breakdown-rows');
        breakdownRows.replaceChildren();

        stays.forEach((stay) => {
            const row = document.createElement('tr');
            const stayCell = document.createElement('td');
            const country = document.createElement('strong');
            const dates = document.createElement('span');
            const countedCell = document.createElement('td');
            const countedDays = document.createElement('strong');
            const countedDates = document.createElement('details');
            const countedDatesSummary = document.createElement('summary');
            const countedDatesText = document.createElement('span');
            const beforeWindowCell = document.createElement('td');
            const afterDateCell = document.createElement('td');
            const excludedCell = document.createElement('td');

            country.textContent = `${countryNames[stay.country_code] ?? stay.country_code} (${stay.country_code})`;
            dates.textContent = `${formatDate(stay.entry_date)} – ${stay.exit_date ? formatDate(stay.exit_date) : 'Ongoing'}`;
            dates.className = 'breakdown-stay-dates';
            stayCell.append(country, dates);

            countedDays.textContent = stay.days_counted;
            countedDatesSummary.textContent = stay.counted_dates.length > 0 ? 'View dates' : 'No dates counted';
            countedDatesText.textContent = formatDateRanges(stay.counted_dates);
            countedDates.append(countedDatesSummary, countedDatesText);
            countedCell.append(countedDays, countedDates);

            beforeWindowCell.textContent = stay.days_before_window;
            afterDateCell.textContent = stay.days_after_date;

            const excludedReasons = [
                ['Not Schengen', stay.days_not_schengen],
                ['Permit exempt', stay.days_exempt],
                ['Overlapping', stay.days_overlapping],
            ].filter(([, days]) => days > 0);
            excludedCell.textContent = excludedReasons.length > 0
                ? excludedReasons.map(([reason, days]) => `${reason}: ${days}`).join(' · ')
                : '—';

            row.append(stayCell, countedCell, beforeWindowCell, afterDateCell, excludedCell);
            breakdownRows.append(row);
        });
    }

    function renderResult(data) {
        document.querySelector('#days-remaining').textContent = data.days_remaining;
        document.querySelector('#days-used').textContent = data.days_used;
        document.querySelector('#period-start').textContent = formatDate(data.period_start);
        document.querySelector('#period-end').textContent = `through ${formatDate(data.period_end)}`;
        document.querySelector('#remaining-caption').textContent = `of ${data.maximum_days} days available`;

        const status = document.querySelector('#compliance-status');
        status.textContent = data.compliant ? 'Within your allowance' : 'Allowance exceeded';
        status.classList.toggle('status-warning', !data.compliant);
        document.querySelector('#result-summary').textContent = data.compliant
            ? `You have ${data.days_remaining} ${data.days_remaining === 1 ? 'day' : 'days'} available in this rolling window.`
            : `You have used ${data.days_used} days, which is ${data.days_used - data.maximum_days} over the ${data.maximum_days}-day allowance.`;
        renderBreakdown(data.stay_breakdown ?? []);
        results.hidden = false;
        latestResult = data;
        results.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function csvCell(value) {
        return `"${String(value ?? '').replaceAll('"', '""')}"`;
    }

    async function loadCountries() {
        const response = await fetch(form.dataset.countriesUrl, {
            headers: { Accept: 'application/json' },
        });
        const payload = await response.json();

        if (!response.ok || payload.success !== true || !Array.isArray(payload.data?.countries)) {
            throw new Error('The country list could not be loaded. Please refresh the page and try again.');
        }

        countries = payload.data.countries;
        addStayButton.disabled = false;
    }

    addStayButton.disabled = true;
    loadCountries().catch((error) => {
        showError(error.message);
    });

    addStayButton.addEventListener('click', () => {
        clearError();
        addStay();
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearError();
        clearResults();

        const rows = [...staysList.querySelectorAll('.stay-row')];
        const stays = rows.map((row) => ({
            entry: row.querySelector('.stay-entry').value,
            exit: row.querySelector('.stay-exit').value || null,
            country_code: row.querySelector('.stay-country').value,
        }));

        if (stays.some((stay) => stay.exit && stay.exit < stay.entry)) {
            showError('Each exit date must be on or after its entry date.');
            return;
        }

        const submitButton = form.querySelector('[type="submit"]');
        submitButton.disabled = true;
        submitButton.classList.add('is-loading');

        try {
            const response = await fetch(form.dataset.calculateUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date: calculationDate.value,
                    stays,
                }),
            });
            const payload = await response.json();

            if (!response.ok) {
                const messages = Object.values(payload.errors ?? {}).flat();
                throw new Error(messages.join(' ') || 'The calculation could not be completed. Please check your entries and try again.');
            }

            if (payload.success !== true || !payload.data) {
                throw new Error('The calculator returned an unexpected response. Please try again.');
            }

            latestStays = stays;
            renderResult(payload.data);
        } catch (error) {
            showError(error.message || 'Something went wrong while calculating your days.');
        } finally {
            submitButton.disabled = false;
            submitButton.classList.remove('is-loading');
        }
    });

    form.addEventListener('input', clearResults);
    form.addEventListener('change', clearResults);

    document.querySelector('#reset-results').addEventListener('click', () => {
        clearResults();
        clearError();
        document.querySelector('#calculator-title').scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    document.querySelector('#download-csv').addEventListener('click', () => {
        if (!latestResult) {
            return;
        }

        const lines = [
            ['Schengen Stay Calculator result'],
            ['Calculated on', latestResult.date],
            ['Days used', latestResult.days_used],
            ['Days remaining', latestResult.days_remaining],
            ['Maximum days', latestResult.maximum_days],
            ['Reference period (days)', latestResult.reference_period],
            ['Compliant', latestResult.compliant ? 'Yes' : 'No'],
            ['Window start', latestResult.period_start],
            ['Window end', latestResult.period_end],
            [],
            ['Country code', 'Entry date', 'Exit date'],
            ...latestStays.map((stay) => [stay.country_code, stay.entry, stay.exit ?? 'Ongoing']),
            [],
            ['Per-stay calculation breakdown'],
            ['Country code', 'Entry date', 'Exit date', 'Counted days', 'Counted dates', 'Before window', 'After check date', 'Not Schengen', 'Permit exempt', 'Overlapping'],
            ...(latestResult.stay_breakdown ?? []).map((stay) => [
                stay.country_code,
                stay.entry_date,
                stay.exit_date ?? 'Ongoing',
                stay.days_counted,
                formatDateRanges(stay.counted_dates),
                stay.days_before_window,
                stay.days_after_date,
                stay.days_not_schengen,
                stay.days_exempt,
                stay.days_overlapping,
            ]),
        ];
        const csv = lines.map((line) => line.map(csvCell).join(',')).join('\r\n');
        const downloadUrl = URL.createObjectURL(new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' }));
        const link = document.createElement('a');

        link.href = downloadUrl;
        link.download = `schengen-stay-calculation-${latestResult.date}.csv`;
        document.body.append(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(downloadUrl);
    });
}

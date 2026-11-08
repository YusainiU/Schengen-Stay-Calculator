<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#f5f7f2">
        <meta name="description" content="Plan your Schengen trips with a free 90/180 day stay calculator.">

        <title>Schengen Stay Calculator</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="page-shell">
            <header class="site-header">
                <a class="brand" href="{{ route('home') }}" aria-label="Schengen Stay Calculator home">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M5 8.5h22M5 16h22M5 23.5h22" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"/>
                            <circle cx="10" cy="8.5" r="2" fill="#e9a94f"/>
                            <circle cx="21" cy="16" r="2" fill="#e9a94f"/>
                            <circle cx="13" cy="23.5" r="2" fill="#e9a94f"/>
                        </svg>
                    </span>
                    <span>Schengen<span class="brand-light">Stay</span></span>
                </a>
                <a class="header-link" href="#how-it-works">How it works <span aria-hidden="true">↗</span></a>
            </header>

            <main>
                <section class="hero" aria-labelledby="page-title">
                    <div class="hero-copy">
                        <p class="eyebrow"><span class="eyebrow-dot"></span> YOUR TRAVEL, IN BALANCE</p>
                        <h1 id="page-title">More time exploring.<br><span>Less time counting.</span></h1>
                        <p class="hero-description">
                            Keep your Schengen days in check. Add your trips and get a clear picture of where you stand under the 90/180-day rule.
                        </p>
                        <div class="rule-note">
                            <span class="rule-icon" aria-hidden="true">90</span>
                            <span><strong>90 days</strong> in any rolling <strong>180-day period</strong></span>
                        </div>
                    </div>

                    <div class="hero-illustration" aria-hidden="true">
                        <div class="sun"></div>
                        <div class="orbit orbit-one"></div>
                        <div class="orbit orbit-two"></div>
                        <div class="illustration-card">
                            <div class="mini-calendar">
                                <span class="calendar-top"></span>
                                <span class="calendar-days">M T W T F S S</span>
                                <span class="calendar-grid">1 2 3 4 5 6 7<br>8 9 10 11 12 13 14<br>15 16 17 18 19 20 21</span>
                                <span class="calendar-highlight">22</span>
                            </div>
                            <span class="plane-path">✦</span>
                            <span class="plane">✈</span>
                        </div>
                        <span class="stamp stamp-one">EUROPE<br>✦ ✦ ✦</span>
                        <span class="stamp stamp-two">BON VOYAGE</span>
                        <div class="hill hill-back"></div>
                        <div class="hill hill-front"></div>
                    </div>
                </section>

                <section class="calculator-card" aria-labelledby="calculator-title">
                    <div class="card-heading">
                        <div>
                            <p class="section-kicker">YOUR TRAVEL TIMELINE</p>
                            <h2 id="calculator-title">Let’s count your days</h2>
                            <p class="card-intro">Add the dates you’ve spent in the Schengen Area, then calculate your allowance.</p>
                        </div>
                        <span class="private-note"><span aria-hidden="true">◉</span> No account · No saved travel history</span>
                    </div>

                    <form id="calculator-form" data-calculate-url="{{ route('schengen.calculate') }}" data-countries-url="{{ route('schengen.countries') }}">
                        <div class="calculation-date">
                            <label for="calculation-date">Calculate your allowance on</label>
                            <input id="calculation-date" name="date" type="date" required>
                            <span class="field-hint">Usually today, or a date you’re planning for.</span>
                        </div>

                        <div class="stays-heading">
                            <div>
                                <h3>Previous stays <span id="stay-count" class="stay-count">0</span></h3>
                                <p>Include every trip to a Schengen country within the last 180 days.</p>
                            </div>
                            <button id="add-stay" class="button button-secondary" type="button">
                                <span aria-hidden="true">＋</span> Add a stay
                            </button>
                        </div>

                        <div id="stays-list" class="stays-list" aria-live="polite"></div>
                        <p id="empty-stays" class="empty-stays">No stays added yet. If this is your first trip, you can calculate with zero previous stays.</p>

                        <div id="form-error" class="form-error" role="alert" hidden></div>

                        <div class="form-actions">
                            <button class="button button-primary" type="submit">
                                Calculate my days <span aria-hidden="true">→</span>
                            </button>
                            <span class="form-footnote">Free to use · No account needed</span>
                        </div>
                    </form>

                    <section id="results" class="results" aria-labelledby="results-title" aria-live="polite" hidden>
                        <div class="results-heading">
                            <div>
                                <p class="section-kicker">YOUR SCHENGEN ALLOWANCE</p>
                                <h2 id="results-title">Your results</h2>
                            </div>
                            <span id="compliance-status" class="status-badge"></span>
                        </div>
                        <div class="result-stats">
                            <article class="result-stat result-stat-primary">
                                <span class="stat-label">Days remaining</span>
                                <strong id="days-remaining">—</strong>
                                <span id="remaining-caption" class="stat-caption">of 90 days available</span>
                            </article>
                            <article class="result-stat">
                                <span class="stat-label">Days used</span>
                                <strong id="days-used">—</strong>
                                <span class="stat-caption">in the 180-day window</span>
                            </article>
                            <article class="result-stat">
                                <span class="stat-label">Your 180-day window</span>
                                <strong id="period-start" class="period-value">—</strong>
                                <span id="period-end" class="stat-caption">through —</span>
                            </article>
                        </div>
                        <div class="result-actions">
                            <p id="result-summary"></p>
                            <div class="result-buttons">
                                <button id="download-csv" class="button button-secondary" type="button">
                                    <span aria-hidden="true">↓</span> Download CSV
                                </button>
                                <button id="reset-results" class="button button-text" type="button">Reset results</button>
                            </div>
                        </div>
                        <details id="calculation-breakdown" class="calculation-breakdown">
                            <summary>See how each stay was counted</summary>
                            <p class="breakdown-intro">
                                Only Schengen days inside the rolling window count. Entry and exit dates both count; days before the window, after your check date, or duplicated by overlapping stays do not add to the total.
                            </p>
                            <div class="breakdown-table-wrap">
                                <table class="breakdown-table">
                                    <thead>
                                        <tr>
                                            <th scope="col">Stay</th>
                                            <th scope="col">Counted days</th>
                                            <th scope="col">Before window</th>
                                            <th scope="col">After check date</th>
                                            <th scope="col">Other excluded days</th>
                                        </tr>
                                    </thead>
                                    <tbody id="breakdown-rows"></tbody>
                                </table>
                            </div>
                        </details>
                    </section>
                </section>

                <section id="how-it-works" class="how-it-works" aria-labelledby="how-title">
                    <div class="how-heading">
                        <p class="section-kicker">SIMPLE BY DESIGN</p>
                        <h2 id="how-title">A little clarity goes a long way.</h2>
                    </div>
                    <div class="steps">
                        <article class="step">
                            <span class="step-number">01</span>
                            <h3>Add your stays</h3>
                            <p>Enter the country and dates for each past or current Schengen trip.</p>
                        </article>
                        <article class="step">
                            <span class="step-number">02</span>
                            <h3>Check your allowance</h3>
                            <p>We count days inclusively and apply the rolling 180-day window.</p>
                        </article>
                        <article class="step">
                            <span class="step-number">03</span>
                            <h3>Plan with confidence</h3>
                            <p>See your remaining days and download a copy of your calculation.</p>
                        </article>
                    </div>
                </section>
            </main>

            <footer class="site-footer">
                <span>Made for the journeys ahead.</span>
                <span>Always verify your travel dates with official guidance.</span>
            </footer>
        </div>

        <template id="stay-template">
            <div class="stay-row">
                <span class="stay-index" aria-hidden="true"></span>
                <label class="stay-field">
                    <span>Country</span>
                    <select class="stay-country" required>
                        <option value="">Choose a country</option>
                    </select>
                </label>
                <label class="stay-field">
                    <span>Entry date</span>
                    <input class="stay-entry" type="date" required>
                </label>
                <label class="stay-field">
                    <span>Exit date <span class="optional">(or leave empty if still there)</span></span>
                    <input class="stay-exit" type="date">
                </label>
                <button class="remove-stay" type="button" aria-label="Remove this stay">×</button>
            </div>
        </template>
    </body>
</html>

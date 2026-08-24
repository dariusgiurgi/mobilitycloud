@once
    <style>
        .mc-cookie-consent {
            position: fixed;
            inset: auto 18px 18px auto;
            z-index: 80;
            width: min(520px, calc(100% - 36px));
            color: #07111f;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .mc-cookie-consent[hidden] {
            display: none !important;
        }

        .mc-cookie-consent__card {
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, .28);
            border-radius: 28px;
            background:
                radial-gradient(circle at 10% 0%, rgba(56, 213, 255, .15), transparent 17rem),
                radial-gradient(circle at 100% 0%, rgba(79, 70, 229, .14), transparent 16rem),
                rgba(255, 255, 255, .96);
            box-shadow: 0 28px 80px rgba(15, 23, 42, .22);
            backdrop-filter: blur(18px);
        }

        .mc-cookie-consent__body {
            padding: 22px;
        }

        .mc-cookie-consent__badge {
            display: inline-flex;
            align-items: center;
            width: max-content;
            max-width: 100%;
            padding: .42rem .66rem;
            border-radius: 999px;
            background: #eef2ff;
            color: #4f46e5;
            font-size: .68rem;
            font-weight: 950;
            letter-spacing: .08em;
            text-transform: uppercase;
        }

        .mc-cookie-consent h2 {
            margin: .8rem 0 0;
            font-size: clamp(1.35rem, 3.6vw, 1.85rem);
            line-height: 1.05;
            letter-spacing: -.045em;
        }

        .mc-cookie-consent p {
            margin: .7rem 0 0;
            color: #52627a;
            line-height: 1.55;
            font-size: .95rem;
        }

        .mc-cookie-consent a {
            color: #4f46e5;
            font-weight: 850;
            text-decoration: none;
        }

        .mc-cookie-consent a:hover {
            text-decoration: underline;
        }

        .mc-cookie-consent__actions {
            display: flex;
            flex-wrap: wrap;
            gap: .55rem;
            margin-top: 1rem;
        }

        .mc-cookie-consent__button {
            appearance: none;
            border: 1px solid rgba(148, 163, 184, .32);
            border-radius: 999px;
            background: #ffffff;
            color: #0f172a;
            cursor: pointer;
            font: inherit;
            font-size: .88rem;
            font-weight: 900;
            padding: .76rem .98rem;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease;
        }

        .mc-cookie-consent__button:hover {
            border-color: rgba(79, 70, 229, .32);
            box-shadow: 0 10px 24px rgba(15, 23, 42, .09);
            transform: translateY(-1px);
        }

        .mc-cookie-consent__button--primary {
            border-color: transparent;
            background: linear-gradient(135deg, #4f46e5, #2563eb, #38d5ff);
            color: white;
            box-shadow: 0 14px 28px rgba(79, 70, 229, .22);
        }

        .mc-cookie-consent__choices {
            display: grid;
            gap: .62rem;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid rgba(148, 163, 184, .24);
        }

        .mc-cookie-consent__choices[hidden] {
            display: none !important;
        }

        .mc-cookie-consent__option {
            display: grid;
            grid-template-columns: 1fr auto;
            gap: .9rem;
            align-items: center;
            padding: .78rem .9rem;
            border: 1px solid rgba(148, 163, 184, .24);
            border-radius: 18px;
            background: rgba(255, 255, 255, .78);
        }

        .mc-cookie-consent__option strong {
            display: block;
            font-size: .92rem;
        }

        .mc-cookie-consent__option span {
            display: block;
            margin-top: .18rem;
            color: #64748b;
            font-size: .78rem;
            line-height: 1.35;
        }

        .mc-cookie-consent__option input {
            width: 1.2rem;
            height: 1.2rem;
            accent-color: #4f46e5;
        }

        @media (max-width: 620px) {
            .mc-cookie-consent {
                inset: auto 12px 12px 12px;
                width: auto;
            }

            .mc-cookie-consent__body {
                padding: 18px;
            }

            .mc-cookie-consent__actions {
                display: grid;
            }
        }
    </style>

    <div id="mc-cookie-consent" class="mc-cookie-consent" hidden>
        <section class="mc-cookie-consent__card" role="dialog" aria-modal="false" aria-labelledby="mc-cookie-consent-title">
            <div class="mc-cookie-consent__body">
                <span class="mc-cookie-consent__badge">Cookie choices</span>
                <h2 id="mc-cookie-consent-title">Choose how MobilityCloud uses cookies</h2>
                <p>
                    Essential cookies keep the site and platform secure. Optional cookies can help us remember preferences,
                    understand traffic and improve future campaigns. You can change your choice anytime.
                    <a href="{{ route('legal.cookies') }}">Read the Cookie Policy</a>.
                </p>

                <div class="mc-cookie-consent__actions" data-mc-cookie-summary>
                    <button class="mc-cookie-consent__button mc-cookie-consent__button--primary" type="button" data-mc-cookie-accept>
                        Accept all
                    </button>
                    <button class="mc-cookie-consent__button" type="button" data-mc-cookie-reject>
                        Reject optional
                    </button>
                    <button class="mc-cookie-consent__button" type="button" data-mc-cookie-customize>
                        Customize
                    </button>
                </div>

                <form class="mc-cookie-consent__choices" data-mc-cookie-choices hidden>
                    <label class="mc-cookie-consent__option">
                        <span>
                            <strong>Essential</strong>
                            <span>Required for login, security, session protection and basic site operation.</span>
                        </span>
                        <input type="checkbox" checked disabled>
                    </label>
                    <label class="mc-cookie-consent__option">
                        <span>
                            <strong>Preferences</strong>
                            <span>Stores interface choices such as dismissed notices, layout choices or language preferences.</span>
                        </span>
                        <input type="checkbox" name="preferences" data-mc-cookie-category="preferences">
                    </label>
                    <label class="mc-cookie-consent__option">
                        <span>
                            <strong>Analytics</strong>
                            <span>Allows privacy-conscious traffic measurement and conversion insights after consent.</span>
                        </span>
                        <input type="checkbox" name="analytics" data-mc-cookie-category="analytics">
                    </label>
                    <label class="mc-cookie-consent__option">
                        <span>
                            <strong>Marketing</strong>
                            <span>Reserved for future campaigns, embedded media or remarketing tools, only after consent.</span>
                        </span>
                        <input type="checkbox" name="marketing" data-mc-cookie-category="marketing">
                    </label>

                    <div class="mc-cookie-consent__actions">
                        <button class="mc-cookie-consent__button mc-cookie-consent__button--primary" type="submit">
                            Save choices
                        </button>
                        <button class="mc-cookie-consent__button" type="button" data-mc-cookie-reject>
                            Reject optional
                        </button>
                    </div>
                </form>
            </div>
        </section>
    </div>

    <script>
        (() => {
            const cookieName = 'mc_cookie_consent';
            const maxAge = 60 * 60 * 24 * 180;
            const defaults = {
                essential: true,
                preferences: false,
                analytics: false,
                marketing: false,
                version: 1,
                updatedAt: null,
            };

            const banner = document.getElementById('mc-cookie-consent');
            const summary = banner?.querySelector('[data-mc-cookie-summary]');
            const choices = banner?.querySelector('[data-mc-cookie-choices]');
            const categoryInputs = banner ? [...banner.querySelectorAll('[data-mc-cookie-category]')] : [];

            const readConsent = () => {
                const raw = document.cookie
                    .split('; ')
                    .find((row) => row.startsWith(`${cookieName}=`))
                    ?.split('=')
                    .slice(1)
                    .join('=');

                if (!raw) {
                    return null;
                }

                try {
                    return { ...defaults, ...JSON.parse(decodeURIComponent(raw)) };
                } catch (error) {
                    return null;
                }
            };

            const writeConsent = (choicesToStore) => {
                const consent = {
                    ...defaults,
                    ...choicesToStore,
                    essential: true,
                    version: defaults.version,
                    updatedAt: new Date().toISOString(),
                };

                const secure = window.location.protocol === 'https:' ? '; Secure' : '';
                document.cookie = `${cookieName}=${encodeURIComponent(JSON.stringify(consent))}; Max-Age=${maxAge}; Path=/; SameSite=Lax${secure}`;

                publishConsent(consent);
                hideBanner();

                return consent;
            };

            const publishConsent = (consent) => {
                window.MobilityCloudConsent = {
                    get: () => readConsent() || { ...defaults },
                    has: (category) => Boolean((readConsent() || defaults)[category]),
                    set: (choicesToStore) => writeConsent(choicesToStore),
                };

                window.dispatchEvent(new CustomEvent('mobilitycloud:cookie-consent', {
                    detail: consent || { ...defaults },
                }));
            };

            const syncInputs = (consent) => {
                categoryInputs.forEach((input) => {
                    input.checked = Boolean(consent?.[input.dataset.mcCookieCategory]);
                });
            };

            const showBanner = (customize = false) => {
                if (!banner) {
                    return;
                }

                syncInputs(readConsent() || defaults);
                banner.hidden = false;

                if (summary && choices) {
                    summary.hidden = customize;
                    choices.hidden = !customize;
                }
            };

            const hideBanner = () => {
                if (banner) {
                    banner.hidden = true;
                }
            };

            banner?.querySelector('[data-mc-cookie-accept]')?.addEventListener('click', () => {
                writeConsent({ preferences: true, analytics: true, marketing: true });
            });

            banner?.querySelectorAll('[data-mc-cookie-reject]').forEach((button) => {
                button.addEventListener('click', () => {
                    writeConsent({ preferences: false, analytics: false, marketing: false });
                });
            });

            banner?.querySelector('[data-mc-cookie-customize]')?.addEventListener('click', () => {
                showBanner(true);
            });

            choices?.addEventListener('submit', (event) => {
                event.preventDefault();

                writeConsent(Object.fromEntries(categoryInputs.map((input) => [
                    input.dataset.mcCookieCategory,
                    input.checked,
                ])));
            });

            document.addEventListener('click', (event) => {
                const trigger = event.target.closest('[data-cookie-settings]');

                if (!trigger) {
                    return;
                }

                event.preventDefault();
                showBanner(true);
            });

            const existingConsent = readConsent();
            publishConsent(existingConsent || defaults);

            if (!existingConsent) {
                showBanner(false);
            }
        })();
    </script>

    @include('public.partials.google-analytics')
@endonce

@php
    $googleAnalyticsMeasurementId = config('mobilitycloud.analytics.google_measurement_id');
@endphp

@if ($googleAnalyticsMeasurementId)
    @once
        <script>
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () {
                window.dataLayer.push(arguments);
            };

            window.gtag('consent', 'default', {
                analytics_storage: 'denied',
                ad_storage: 'denied',
                ad_user_data: 'denied',
                ad_personalization: 'denied',
                functionality_storage: 'granted',
                security_storage: 'granted',
                wait_for_update: 500,
            });
        </script>
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ rawurlencode($googleAnalyticsMeasurementId) }}"></script>
        <script>
            (() => {
                const measurementId = @json($googleAnalyticsMeasurementId);

                if (!measurementId) {
                    return;
                }

                const deleteCookie = (name, domain = null) => {
                    const domainPart = domain ? `; Domain=${domain}` : '';

                    document.cookie = `${name}=; Max-Age=0; Path=/${domainPart}; SameSite=Lax`;
                };

                const removeGoogleAnalyticsCookies = () => {
                    const host = window.location.hostname;
                    const rootDomain = host.split('.').slice(-2).join('.');
                    const cookies = document.cookie
                        .split('; ')
                        .map((cookie) => cookie.split('=')[0])
                        .filter((name) => name === '_ga' || name === '_gid' || name === '_gat' || name.startsWith('_ga_'));

                    cookies.forEach((name) => {
                        deleteCookie(name);
                        deleteCookie(name, host);
                        deleteCookie(name, `.${host}`);
                        deleteCookie(name, rootDomain);
                        deleteCookie(name, `.${rootDomain}`);
                    });
                };

                const currentConsent = window.MobilityCloudConsent?.get?.();
                let analyticsWasAllowed = Boolean(currentConsent?.analytics);

                const setConsentState = (analyticsAllowed) => {
                    analyticsAllowed = Boolean(analyticsAllowed);

                    window[`ga-disable-${measurementId}`] = !analyticsAllowed;
                    window.gtag('consent', 'update', {
                        analytics_storage: analyticsAllowed ? 'granted' : 'denied',
                        ad_storage: 'denied',
                        ad_user_data: 'denied',
                        ad_personalization: 'denied',
                    });

                    if (!analyticsAllowed) {
                        removeGoogleAnalyticsCookies();
                    }
                };

                const applyAnalyticsConsent = (consent) => {
                    const analyticsAllowed = Boolean(consent?.analytics);

                    setConsentState(analyticsAllowed);

                    if (analyticsAllowed && !analyticsWasAllowed) {
                        window.gtag('event', 'page_view', {
                            page_location: window.location.href,
                            page_title: document.title,
                            send_to: measurementId,
                        });
                    }

                    analyticsWasAllowed = analyticsAllowed;
                };

                setConsentState(analyticsWasAllowed);

                window.gtag('js', new Date());
                window.gtag('config', measurementId, {
                    anonymize_ip: true,
                    allow_google_signals: false,
                    allow_ad_personalization_signals: false,
                });

                window.addEventListener('mobilitycloud:cookie-consent', (event) => {
                    applyAnalyticsConsent(event.detail);
                });
            })();
        </script>
    @endonce
@endif

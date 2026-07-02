@once
    <style>
        @media (min-width: 1024px) {
            .fi-main-sidebar {
                --mc-sidebar-item-height: 2.75rem;
                --mc-sidebar-sub-item-height: 2.45rem;
                --mc-sidebar-icon-size: 1.25rem;
                --mc-sidebar-group-height: 1.7rem;
                --mc-sidebar-density-label: .72rem;
                --mc-sidebar-density-item: .82rem;
                --mc-sidebar-density-label-line-height: 1.05rem;
                --mc-sidebar-density-item-line-height: 1.05rem;
                --mc-sidebar-section-gap: .55rem;
                --mc-sidebar-item-gap: .16rem;
                --mc-sidebar-item-padding-y: .36rem;
                --mc-sidebar-nav-height: calc(100vh - 4.5rem);

                height: 100vh !important;
                max-height: 100vh !important;
                overflow: hidden !important;
            }

            .fi-main-sidebar .fi-sidebar-header {
                min-height: 3.45rem !important;
                padding: .6rem .75rem !important;
                flex-shrink: 0 !important;
            }

            .fi-main-sidebar .fi-sidebar-header-logo-ctn {
                gap: .45rem !important;
            }

            .fi-main-sidebar .fi-sidebar-nav {
                gap: var(--mc-sidebar-section-gap) !important;
                height: var(--mc-sidebar-nav-height) !important;
                max-height: var(--mc-sidebar-nav-height) !important;
                min-height: 0 !important;
                overflow: hidden !important;
                padding: .55rem .55rem .65rem !important;
                scrollbar-gutter: auto;
                scrollbar-width: none;
            }

            .fi-main-sidebar .fi-sidebar-nav-groups {
                gap: var(--mc-sidebar-section-gap) !important;
                min-height: 0 !important;
                overflow: hidden !important;
            }

            .fi-main-sidebar .fi-sidebar-group {
                gap: var(--mc-sidebar-item-gap) !important;
                min-height: 0 !important;
            }

            .fi-main-sidebar .fi-sidebar-group-btn {
                min-height: var(--mc-sidebar-group-height) !important;
                padding: .14rem .52rem !important;
            }

            .fi-main-sidebar .fi-sidebar-group-label {
                font-size: var(--mc-sidebar-density-label) !important;
                font-weight: 700 !important;
                letter-spacing: .045em !important;
                line-height: var(--mc-sidebar-density-label-line-height) !important;
            }

            .fi-main-sidebar .fi-sidebar-group-collapse-btn {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }

            .fi-main-sidebar .fi-sidebar-group-items,
            .fi-main-sidebar .fi-sidebar-sub-group-items {
                gap: var(--mc-sidebar-item-gap) !important;
            }

            .fi-main-sidebar .fi-sidebar-item-btn {
                min-height: var(--mc-sidebar-item-height) !important;
                gap: .48rem !important;
                border-radius: .55rem !important;
                padding: var(--mc-sidebar-item-padding-y) .55rem !important;
                align-items: center !important;
            }

            .fi-main-sidebar .fi-sidebar-sub-group-items .fi-sidebar-item-btn {
                min-height: var(--mc-sidebar-sub-item-height) !important;
                padding-block: calc(var(--mc-sidebar-item-padding-y) * .82) !important;
            }

            .fi-main-sidebar .fi-sidebar-item-icon {
                width: var(--mc-sidebar-icon-size) !important;
                height: var(--mc-sidebar-icon-size) !important;
            }

            .fi-main-sidebar .fi-sidebar-item-label {
                font-size: var(--mc-sidebar-density-item) !important;
                line-height: var(--mc-sidebar-density-item-line-height) !important;
            }

            .fi-main-sidebar .fi-sidebar-item-badge-ctn {
                transform: scale(.88);
                transform-origin: center right;
            }

            .fi-main-sidebar .fi-sidebar-footer {
                padding: .45rem .55rem .55rem !important;
                flex-shrink: 0 !important;
            }

            .fi-main-sidebar .fi-sidebar-database-notifications-btn {
                min-height: 2rem !important;
                padding: .32rem .5rem !important;
            }

            .fi-main-sidebar[data-mc-sidebar-density="comfortable"] .fi-sidebar-item-btn {
                border-radius: .7rem !important;
                padding-inline: .62rem !important;
            }

            .fi-main-sidebar[data-mc-sidebar-density="comfortable"] .fi-sidebar-nav,
            .fi-main-sidebar[data-mc-sidebar-density="comfortable"] .fi-sidebar-nav-groups {
                gap: .65rem !important;
            }

            .fi-main-sidebar[data-mc-sidebar-density="comfortable"] .fi-sidebar-group,
            .fi-main-sidebar[data-mc-sidebar-density="comfortable"] .fi-sidebar-group-items,
            .fi-main-sidebar[data-mc-sidebar-density="comfortable"] .fi-sidebar-sub-group-items {
                gap: .22rem !important;
            }

            .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar {
                display: none;
                height: 0;
                width: 0;
            }

            .fi-main-sidebar .fi-sidebar-nav::-webkit-scrollbar-thumb {
                background: transparent;
            }

            @media (max-height: 820px) {
                .fi-main-sidebar {
                    --mc-sidebar-density-label: .69rem;
                    --mc-sidebar-density-item: .78rem;
                    --mc-sidebar-density-label-line-height: .95rem;
                    --mc-sidebar-density-item-line-height: .98rem;
                    --mc-sidebar-section-gap: .42rem;
                    --mc-sidebar-item-gap: .12rem;
                    --mc-sidebar-group-height: 1.5rem;
                }

                .fi-main-sidebar .fi-sidebar-header {
                    min-height: 3.15rem !important;
                    padding-block: .48rem !important;
                }

                .fi-main-sidebar .fi-sidebar-group-btn {
                    padding-block: .08rem !important;
                }
            }
        }
    </style>

    <script>
        (() => {
            const clamp = (value, min, max) => Math.min(Math.max(value, min), max);

            const fitSidebar = () => {
                if (! window.matchMedia('(min-width: 1024px)').matches) {
                    return;
                }

                document.querySelectorAll('.fi-main-sidebar').forEach((sidebar) => {
                    const nav = sidebar.querySelector('.fi-sidebar-nav');

                    if (! nav) {
                        return;
                    }

                    const collapsed = sidebar.offsetWidth < 120
                        || sidebar.classList.contains('fi-sidebar-collapsed')
                        || document.body.classList.contains('fi-sidebar-collapsed');
                    const visibleItems = [...nav.querySelectorAll('.fi-sidebar-item-btn')]
                        .filter((item) => item.offsetParent !== null);

                    if (! visibleItems.length) {
                        return;
                    }

                    const headerHeight = sidebar.querySelector('.fi-sidebar-header')?.getBoundingClientRect().height ?? 56;
                    const footerHeight = sidebar.querySelector('.fi-sidebar-footer')?.getBoundingClientRect().height ?? 0;
                    const groupHeaders = [...nav.querySelectorAll('.fi-sidebar-group-btn')]
                        .filter((item) => item.offsetParent !== null).length;
                    const groups = [...nav.querySelectorAll('.fi-sidebar-group')]
                        .filter((item) => item.offsetParent !== null).length;
                    const viewport = window.visualViewport?.height ?? window.innerHeight;
                    const chromeReserve = headerHeight + footerHeight + 10;
                    const navHeight = Math.max(Math.floor(viewport - chromeReserve), 220);
                    const groupHeight = collapsed ? 12 : clamp(Math.floor(navHeight * 0.032), 14, 25);
                    const sectionGap = clamp(navHeight / 1700, 0.08, collapsed ? 0.24 : 0.55);
                    const itemGap = clamp(navHeight / 3600, 0.03, collapsed ? 0.12 : 0.2);
                    const reserved = (groupHeaders * groupHeight)
                        + (groups * sectionGap * 16)
                        + (visibleItems.length * itemGap * 16)
                        + 14;
                    const available = Math.max(navHeight - reserved, visibleItems.length * 22);
                    const rawItemHeight = Math.floor(available / visibleItems.length);
                    const itemHeight = clamp(rawItemHeight, collapsed ? 23 : 24, collapsed ? 48 : 54);
                    const subItemHeight = clamp(itemHeight - 4, collapsed ? 22 : 23, 46);
                    const iconSize = clamp(Math.round(itemHeight * (collapsed ? 0.56 : 0.42)), 14, collapsed ? 22 : 23);
                    const itemFontSize = clamp(itemHeight * 0.33, 10, 13);
                    const labelFontSize = clamp(groupHeight * 0.43, 9, 11.5);
                    const itemLineHeight = clamp(itemFontSize * 1.22, 11, 16);
                    const labelLineHeight = clamp(labelFontSize * 1.18, 10, 14);
                    const paddingY = clamp(itemHeight * 0.12, 2.5, 5.8);

                    sidebar.dataset.mcSidebarDensity = itemHeight >= 48
                        ? 'comfortable'
                        : (itemHeight >= 39 ? 'balanced' : 'compact');

                    sidebar.style.setProperty('--mc-sidebar-nav-height', `${navHeight}px`);
                    sidebar.style.setProperty('--mc-sidebar-item-height', `${itemHeight}px`);
                    sidebar.style.setProperty('--mc-sidebar-sub-item-height', `${subItemHeight}px`);
                    sidebar.style.setProperty('--mc-sidebar-icon-size', `${iconSize}px`);
                    sidebar.style.setProperty('--mc-sidebar-group-height', `${groupHeight}px`);
                    sidebar.style.setProperty('--mc-sidebar-section-gap', `${sectionGap}rem`);
                    sidebar.style.setProperty('--mc-sidebar-item-gap', `${itemGap}rem`);
                    sidebar.style.setProperty('--mc-sidebar-density-item', `${itemFontSize}px`);
                    sidebar.style.setProperty('--mc-sidebar-density-label', `${labelFontSize}px`);
                    sidebar.style.setProperty('--mc-sidebar-density-item-line-height', `${itemLineHeight}px`);
                    sidebar.style.setProperty('--mc-sidebar-density-label-line-height', `${labelLineHeight}px`);
                    sidebar.style.setProperty('--mc-sidebar-item-padding-y', `${paddingY}px`);
                });
            };

            let frame = null;
            const scheduleFit = () => {
                window.cancelAnimationFrame(frame);
                frame = window.requestAnimationFrame(fitSidebar);
            };

            window.addEventListener('resize', scheduleFit, { passive: true });
            window.addEventListener('livewire:navigated', scheduleFit);
            document.addEventListener('DOMContentLoaded', scheduleFit);

            new MutationObserver(scheduleFit).observe(document.documentElement, {
                childList: true,
                subtree: true,
            });
        })();
    </script>
@endonce

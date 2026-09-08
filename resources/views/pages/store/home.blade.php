@php
    /**
     * Hero slides. Built here rather than inline in x-data so translated copy
     * with quotes or apostrophes survives attribute escaping (@js encodes it).
     *
     * action: 'booking' opens the workshop booking modal instead of following
     * the href — the workshop has no page of its own yet.
     *
     * img: picsum seeds, matching the Home design canvas verbatim. These are
     * remote placeholders — swap in local art before this carries real traffic.
     */
    $slides = [
        [
            'tab' => __('Cameras & drones'),
            'num' => '01',
            'kicker' => __('01 — Shop the gear'),
            'title' => __('Cameras and drones, ready to fly.'),
            'body' => __('Twenty-five bodies, lenses, drones and gimbals in stock in Colombo. Two-year local warranty, sale prices updated daily.'),
            'facts' => [
                ['v' => '25', 'k' => __('in stock')],
                ['v' => __('2 yr'), 'k' => __('warranty')],
                ['v' => __('Free'), 'k' => __('island delivery')],
            ],
            'cta' => __('Browse all products'),
            'href' => route('store.products'),
            'action' => null,
            'alt' => __('On sale now'),
            'altHref' => route('store.products'),
            'altAction' => null,
            'img' => 'https://picsum.photos/seed/shuttersky-gear/2000/1200',
            'filter' => 'grayscale(0.35) contrast(1.06) brightness(0.66)',
        ],
        [
            'tab' => __('The workshop'),
            'num' => '02',
            'kicker' => __('02 — The workshop'),
            'title' => __('Service, repair and hands-on classes.'),
            'body' => __('Sensor cleaning, gimbal calibration and drone repairs by our own technicians, plus weekend workshops for new pilots.'),
            'facts' => [
                ['v' => __('3–5 days'), 'k' => __('turnaround')],
                ['v' => __('Rs 6,500'), 'k' => __('sensor clean')],
                ['v' => __('Sat 10am'), 'k' => __('workshops')],
            ],
            'cta' => __('Visit the workshop'),
            'href' => '#',
            'action' => 'booking',
            'alt' => __('Book a service'),
            'altHref' => 'https://wa.me/94770000000?text='.rawurlencode('Hi Shutter & Sky, I would like to book a workshop service.'),
            'altAction' => null,
            'img' => 'https://picsum.photos/seed/shuttersky-workshop/2000/1200',
            'filter' => 'grayscale(0.5) contrast(1.05) brightness(0.6)',
        ],
    ];
@endphp

<x-layouts::store :title="__('Home')" overlay-nav>
    <div x-data="{ bookingModalOpen: false }">
        <section
            x-data="{
                slides: @js($slides),
                duration: 7000,
                autoplay: true,
                i: 0,
                tick: 0,
                armed: false,
                timer: null,

                init() {
                    // Arm on the next frame so the first progress bar sweeps
                    // from zero instead of rendering already full.
                    this.arm();
                    this.start();
                },

                destroy() {
                    clearInterval(this.timer);
                },

                start() {
                    clearInterval(this.timer);

                    if (! this.autoplay || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        return;
                    }

                    this.timer = setInterval(() => this.show(this.i + 1), this.duration);
                },

                arm() {
                    this.armed = false;
                    this.$nextTick(() => { this.armed = true });
                },

                show(n) {
                    this.i = (n + this.slides.length) % this.slides.length;
                    this.tick++;
                    this.arm();
                },

                go(n) {
                    this.show(n);
                    this.start();
                },

                get slide() {
                    return this.slides[this.i];
                },

                get slideKey() {
                    return this.i + '-' + this.tick;
                },

                layerStyle(n) {
                    const s = this.slides[n];
                    const on = n === this.i;

                    return `background-image:url('${s.img}');filter:${s.filter};opacity:${on ? 1 : 0};transform:scale(${on ? 1.08 : 1.02})`;
                },

                barStyle(n) {
                    if (n === this.i) {
                        // Nothing is advancing (autoplay off, or reduced motion),
                        // so the rule marks position rather than elapsed time.
                        if (! this.timer) {
                            return 'width:100%;opacity:1;transition:none';
                        }

                        return this.armed
                            ? `width:100%;opacity:1;transition:width ${this.duration}ms linear`
                            : 'width:0%;opacity:1;transition:none';
                    }

                    const passed = n < this.i;

                    return `width:${passed ? '100%' : '0%'};opacity:${passed ? 0.35 : 0};transition:width 400ms linear`;
                },
            }"
            class="relative flex min-h-svh flex-col justify-end overflow-hidden bg-store-night"
        >
            {{-- Photography stack. Only the active frame is opaque; it keeps a slow
                 Ken Burns push for the length of the slide. --}}
            <template x-for="(s, n) in slides" :key="n">
                <div
                    class="hero-layer absolute inset-0 bg-store-ink bg-cover bg-center"
                    :style="layerStyle(n)"
                    aria-hidden="true"
                ></div>
            </template>

            {{-- Legibility ramp: heavy at the foot where the copy sits, with a
                 touch back at the top so the navbar has something to sit on. --}}
            <div class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_top,rgba(6,8,10,0.96)_0%,rgba(6,8,10,0.62)_38%,rgba(6,8,10,0.28)_70%,rgba(6,8,10,0.55)_100%)]"></div>
            <div class="hero-grain"></div>

            <div class="relative z-10 mx-auto flex w-full max-w-[1440px] flex-col gap-6 px-6 pt-[120px] pb-[34px] sm:px-[clamp(24px,4vw,56px)] sm:pt-[140px] sm:pb-[clamp(34px,3.5vw,54px)]">
                {{-- Re-keyed on every slide change so the staggered rise replays. --}}
                <template x-for="key in [slideKey]" :key="key">
                    <div class="flex flex-col gap-[22px]">
                        <span class="hero-rise inline-flex items-center gap-[9px] self-start text-[11px] font-semibold uppercase tracking-[0.18em] text-store-blush">
                            <span class="h-px w-[34px] bg-store-ember"></span>
                            <span x-text="slide.kicker"></span>
                        </span>

                        <h1
                            class="hero-rise m-0 max-w-[14ch] text-[clamp(40px,5.4vw,88px)] font-bold leading-[0.95] tracking-[-0.04em] text-store-chalk"
                            x-text="slide.title"
                        ></h1>

                        <p
                            class="hero-rise hero-rise-1 m-0 max-w-[46ch] text-[16.5px] leading-[1.55] text-pretty text-store-chalk/78"
                            x-text="slide.body"
                        ></p>

                        <div class="hero-rise hero-rise-2 flex flex-wrap gap-2.5">
                            <template x-for="f in slide.facts" :key="f.k">
                                <span class="glass-dark inline-flex items-baseline gap-2 rounded-[15px] px-3.5 py-[9px]">
                                    <span class="text-[15px] font-bold tracking-[-0.01em] text-store-chalk" x-text="f.v"></span>
                                    <span class="text-[10.5px] uppercase tracking-[0.12em] text-store-chalk/60" x-text="f.k"></span>
                                </span>
                            </template>
                        </div>

                        <div class="hero-rise hero-rise-3 flex flex-wrap items-center gap-3 pt-0.5">
                            <a
                                :href="slide.href"
                                @click="if (slide.action === 'booking') { $event.preventDefault(); bookingModalOpen = true }"
                                class="inline-flex h-[54px] items-center gap-2.5 rounded-[27px] bg-white px-[25px] text-[15.5px] font-semibold text-store-night no-underline shadow-[0_16px_34px_-14px_rgba(0,0,0,0.7)] transition-[transform,background-color,color] duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03] hover:bg-store-ember hover:text-white"
                            >
                                <span x-text="slide.cta"></span>
                                <span class="text-[17px] leading-none">&rarr;</span>
                            </a>

                            <a
                                :href="slide.altHref"
                                @click="if (slide.altAction === 'booking') { $event.preventDefault(); bookingModalOpen = true }"
                                :target="slide.altHref.startsWith('http') ? '_blank' : null"
                                :rel="slide.altHref.startsWith('http') ? 'noopener noreferrer' : null"
                                class="glass-dark inline-flex h-[54px] items-center rounded-[27px] px-[23px] text-[15.5px] font-medium text-store-chalk no-underline transition-[transform,background-color] duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03] hover:bg-white/20"
                            >
                                <span x-text="slide.alt"></span>
                            </a>
                        </div>
                    </div>
                </template>

                {{-- Slide index. Each tab's rule doubles as the autoplay progress bar. --}}
                <div class="mt-4 flex flex-wrap items-center justify-between gap-5 border-t border-white/12 pt-5">
                    <div class="flex min-w-0 flex-1 items-stretch gap-2">
                        <template x-for="(s, n) in slides" :key="n">
                            <button
                                type="button"
                                @click="go(n)"
                                :aria-current="n === i"
                                class="min-w-0 flex-1 cursor-pointer border-0 bg-transparent pb-1 text-left transition-opacity duration-300"
                                :class="n === i ? 'opacity-100' : 'opacity-75'"
                            >
                                <span class="block h-0.5 overflow-hidden rounded-sm bg-white/20">
                                    <span class="block h-full origin-left bg-store-ember" :style="barStyle(n)"></span>
                                </span>
                                <span class="mt-2.5 block text-[10.5px] uppercase tracking-[0.14em] text-store-chalk/50" x-text="s.num"></span>
                                <span
                                    class="mt-1 block truncate text-[14px] font-semibold"
                                    :class="n === i ? 'text-white' : 'text-store-chalk/60'"
                                    x-text="s.tab"
                                ></span>
                            </button>
                        </template>
                    </div>

                    <div class="hidden shrink-0 gap-2 sm:flex">
                        <button
                            type="button"
                            @click="go(i - 1)"
                            aria-label="{{ __('Previous slide') }}"
                            class="glass-dark size-[46px] cursor-pointer rounded-full text-base text-store-chalk transition-[transform,background-color] duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.06] hover:bg-white/20"
                        >
                            <span>&larr;</span>
                        </button>
                        <button
                            type="button"
                            @click="go(i + 1)"
                            aria-label="{{ __('Next slide') }}"
                            class="glass-dark size-[46px] cursor-pointer rounded-full text-base text-store-chalk transition-[transform,background-color] duration-[400ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.06] hover:bg-white/20"
                        >
                            <span>&rarr;</span>
                        </button>
                    </div>
                </div>
            </div>
        </section>

        {{-- Workshop booking modal. Reached from the workshop slide's primary CTA. --}}
        <div
            x-show="bookingModalOpen"
            x-cloak
            @keydown.escape.window="bookingModalOpen = false"
            class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4 sm:p-6"
        >
            <div
                x-show="bookingModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="bookingModalOpen = false"
                class="fixed inset-0 bg-store-night/70 backdrop-blur-xl"
            ></div>

            <div
                x-show="bookingModalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 scale-95"
                class="relative z-10 w-full max-w-xl rounded-3xl border border-[rgba(20,24,29,0.07)] bg-white p-6 shadow-[0_40px_90px_-25px_rgba(0,0,0,0.6)] sm:p-8"
            >
                <button
                    type="button"
                    @click="bookingModalOpen = false"
                    aria-label="{{ __('Close') }}"
                    class="absolute right-5 top-5 flex size-8 cursor-pointer items-center justify-center rounded-full bg-store-chalk text-store-slate transition-colors hover:bg-store-wash hover:text-store-ink"
                >
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/>
                        <line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>

                <div class="flex items-center gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-[linear-gradient(155deg,#FF8A55,#E4572E)] text-white shadow-[0_6px_14px_-4px_rgba(228,87,46,0.6)]">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"/>
                            <line x1="8" y1="21" x2="16" y2="21"/>
                            <line x1="12" y1="17" x2="12" y2="21"/>
                        </svg>
                    </div>
                    <div>
                        <h2 class="font-store-display text-lg font-bold tracking-[-0.02em] text-store-ink sm:text-xl">
                            {{ __('Book a workshop slot') }}
                        </h2>
                        <p class="text-xs text-store-slate">
                            {{ __('Drop off at the Colombo hub or request an insured doorstep pickup.') }}
                        </p>
                    </div>
                </div>

                <form
                    x-data="{
                        name: '',
                        phone: '',
                        gear: '',
                        service: 'Sensor cleaning',
                        notes: '',
                        submitBooking() {
                            if (! this.name || ! this.phone) return;

                            const lines = [
                                'Hello Shutter & Sky, I want to book a workshop service:',
                                'Name: ' + this.name,
                                'Phone: ' + this.phone,
                                'Gear: ' + this.gear,
                                'Service: ' + this.service,
                                'Details: ' + this.notes,
                            ];

                            window.open('https://wa.me/94770000000?text=' + encodeURIComponent(lines.join('\n')), '_blank');
                        }
                    }"
                    @submit.prevent="submitBooking()"
                    class="mt-6 space-y-4"
                >
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-store-ink" for="booking-name">
                                {{ __('Your name') }} <span class="text-store-flame">*</span>
                            </label>
                            <input
                                id="booking-name"
                                type="text"
                                x-model="name"
                                required
                                placeholder="Kasun Perera"
                                class="mt-1.5 w-full rounded-xl border border-[rgba(20,24,29,0.08)] bg-store-chalk px-3.5 py-2 text-xs text-store-ink transition-colors focus:border-store-flame focus:bg-white focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-store-ink" for="booking-phone">
                                {{ __('Phone / WhatsApp') }} <span class="text-store-flame">*</span>
                            </label>
                            <input
                                id="booking-phone"
                                type="tel"
                                x-model="phone"
                                required
                                placeholder="077 123 4567"
                                class="mt-1.5 w-full rounded-xl border border-[rgba(20,24,29,0.08)] bg-store-chalk px-3.5 py-2 text-xs text-store-ink transition-colors focus:border-store-flame focus:bg-white focus:outline-none"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-bold text-store-ink" for="booking-gear">
                                {{ __('Camera / gear model') }}
                            </label>
                            <input
                                id="booking-gear"
                                type="text"
                                x-model="gear"
                                placeholder="{{ __('e.g. Sony A7 IV / 24-70mm GM') }}"
                                class="mt-1.5 w-full rounded-xl border border-[rgba(20,24,29,0.08)] bg-store-chalk px-3.5 py-2 text-xs text-store-ink transition-colors focus:border-store-flame focus:bg-white focus:outline-none"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-store-ink" for="booking-service">
                                {{ __('Service needed') }}
                            </label>
                            <select
                                id="booking-service"
                                x-model="service"
                                class="mt-1.5 w-full rounded-xl border border-[rgba(20,24,29,0.08)] bg-store-chalk px-3.5 py-2 text-xs text-store-ink transition-colors focus:border-store-flame focus:bg-white focus:outline-none"
                            >
                                <option value="Sensor cleaning">{{ __('Ultrasonic sensor clean') }}</option>
                                <option value="Lens realignment">{{ __('Optical lens calibration') }}</option>
                                <option value="Drone repair">{{ __('Drone diagnostic & repair') }}</option>
                                <option value="Shutter mechanism">{{ __('Shutter & mirrorbox service') }}</option>
                                <option value="Board level repair">{{ __('Motherboard / power circuit') }}</option>
                                <option value="General inspection">{{ __('General 42-point diagnostic') }}</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-store-ink" for="booking-notes">
                            {{ __('Issue description') }}
                        </label>
                        <textarea
                            id="booking-notes"
                            x-model="notes"
                            rows="3"
                            placeholder="{{ __('Describe the symptoms — error codes, sensor spots, autofocus drift, liquid contact.') }}"
                            class="mt-1.5 w-full rounded-xl border border-[rgba(20,24,29,0.08)] bg-store-chalk p-3 text-xs text-store-ink transition-colors focus:border-store-flame focus:bg-white focus:outline-none"
                        ></textarea>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-[rgba(20,24,29,0.07)] pt-4">
                        <span class="inline-flex items-center gap-2 text-[11px] text-store-slate">
                            <span class="size-1.5 rounded-full bg-store-go"></span>
                            <span>{{ __('Free preliminary bench assessment') }}</span>
                        </span>

                        <div class="flex items-center gap-2">
                            <button
                                type="button"
                                @click="bookingModalOpen = false"
                                class="cursor-pointer rounded-full px-4 py-2 text-xs font-semibold text-store-slate transition-colors hover:bg-store-chalk hover:text-store-ink"
                            >
                                {{ __('Cancel') }}
                            </button>
                            <button
                                type="submit"
                                class="inline-flex cursor-pointer items-center gap-2 rounded-full bg-store-ink px-5 py-2.5 text-xs font-bold text-white transition-[transform,background-color] duration-[260ms] ease-[cubic-bezier(0.32,0.72,0,1)] hover:scale-[1.03] hover:bg-store-flame"
                            >
                                <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                                <span>{{ __('Send via WhatsApp') }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts::store>

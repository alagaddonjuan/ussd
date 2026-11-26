@php
    $enableTilt = $tilt ?? true;
@endphp
<canvas id="q4-antigravity-canvas" aria-hidden="true"
    style="z-index: -1;"
    class="pointer-events-none fixed inset-0 h-full w-full"></canvas>

<script>
    (function () {
        if (window.__q4FxInitialized) return;
        window.__q4FxInitialized = true;

        const prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const finePointer = window.matchMedia('(pointer: fine)').matches;
        const enableTilt = @json((bool) $enableTilt);

        // =========================================================================
        // 1. Antigravity Magnetic Particle Ring & Click Ripple (HTML5 Canvas)
        // =========================================================================
        const canvas = document.getElementById('q4-antigravity-canvas');
        if (canvas && canvas.getContext) {
            const ctx = canvas.getContext('2d');
            let width = window.innerWidth;
            let height = window.innerHeight;
            let dpr = Math.min(window.devicePixelRatio || 1, 2);

            const pointer = { x: -9999, y: -9999, active: false };
            const ripples = [];
            const paletteLight = ['#0878F8', '#22C7F8', '#6D5EF7', '#0B3A75', '#10B981'];
            const paletteDark = ['#22C7F8', '#60A5FA', '#8B5CF6', '#34D399', '#0878F8'];

            const isDark = () => document.documentElement.classList.contains('dark');
            const getColors = () => (isDark() ? paletteDark : paletteLight);

            function resize() {
                width = window.innerWidth;
                height = window.innerHeight;
                dpr = Math.min(window.devicePixelRatio || 1, 2);
                canvas.width = Math.floor(width * dpr);
                canvas.height = Math.floor(height * dpr);
                canvas.style.width = width + 'px';
                canvas.style.height = height + 'px';
                ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            }

            resize();
            window.addEventListener('resize', resize, { passive: true });

            const particleCount = width < 768 ? 44 : 88;
            const particles = Array.from({ length: particleCount }, (_, i) => ({
                x: Math.random() * width,
                y: Math.random() * height,
                vx: (Math.random() - 0.5) * 0.42,
                vy: (Math.random() - 0.5) * 0.42,
                baseVx: (Math.random() - 0.5) * 0.34,
                baseVy: (Math.random() - 0.5) * 0.34,
                size: 1.8 + Math.random() * 2.4,
                isDash: i % 3 === 0,
                angle: Math.random() * Math.PI * 2,
                colorIndex: i % paletteLight.length,
                alpha: 0.28 + Math.random() * 0.38,
            }));

            if (finePointer) {
                window.addEventListener('pointermove', (e) => {
                    pointer.x = e.clientX;
                    pointer.y = e.clientY;
                    pointer.active = true;
                }, { passive: true });

                window.addEventListener('pointerleave', () => {
                    pointer.active = false;
                }, { passive: true });

                window.addEventListener('pointerdown', (e) => {
                    ripples.push({ x: e.clientX, y: e.clientY, radius: 10, alpha: 0.55 });
                    for (const p of particles) {
                        const dx = p.x - e.clientX;
                        const dy = p.y - e.clientY;
                        const dist = Math.hypot(dx, dy);
                        if (dist > 0 && dist < 240) {
                            const force = ((240 - dist) / 240) * 5.2;
                            p.vx += (dx / dist) * force;
                            p.vy += (dy / dist) * force;
                        }
                    }
                }, { passive: true });
            }

            let rafId = null;

            function step() {
                ctx.clearRect(0, 0, width, height);
                const colors = getColors();
                const haloRadius = 92;
                const influenceRadius = 210;

                // Subtle expanding click ripples
                for (let r = ripples.length - 1; r >= 0; r--) {
                    const rip = ripples[r];
                    rip.radius += 7.5;
                    rip.alpha *= 0.93;
                    if (rip.alpha <= 0.02) {
                        ripples.splice(r, 1);
                        continue;
                    }
                    ctx.beginPath();
                    ctx.arc(rip.x, rip.y, rip.radius, 0, Math.PI * 2);
                    ctx.strokeStyle = `rgba(34, 199, 248, ${rip.alpha.toFixed(3)})`;
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                }

                for (const p of particles) {
                    let nearCursorFactor = 0;

                    if (!prefersReduced && pointer.active) {
                        const dx = pointer.x - p.x;
                        const dy = pointer.y - p.y;
                        const dist = Math.hypot(dx, dy);

                        if (dist > 0 && dist < influenceRadius) {
                            nearCursorFactor = 1 - dist / influenceRadius;
                            const nx = dx / dist;
                            const ny = dy / dist;

                            // Magnetic ring equilibrium around haloRadius + gentle orbital drift
                            if (dist < 62) {
                                const repel = ((62 - dist) / 62) * 0.52;
                                p.vx -= nx * repel;
                                p.vy -= ny * repel;
                            } else {
                                const pull = ((dist - haloRadius) / influenceRadius) * 0.14;
                                p.vx += nx * pull;
                                p.vy += ny * pull;
                            }

                            // Tangential orbital swirl around the cursor
                            p.vx += -ny * 0.09 * nearCursorFactor;
                            p.vy += nx * 0.09 * nearCursorFactor;
                            p.angle = Math.atan2(ny, nx);
                        }
                    }

                    if (!prefersReduced) {
                        p.vx = p.vx * 0.95 + p.baseVx * 0.05;
                        p.vy = p.vy * 0.95 + p.baseVy * 0.05;
                        p.x += p.vx;
                        p.y += p.vy;

                        if (p.x < -20) p.x = width + 20;
                        if (p.x > width + 20) p.x = -20;
                        if (p.y < -20) p.y = height + 20;
                        if (p.y > height + 20) p.y = -20;
                    }

                    const drawAlpha = Math.min(0.92, p.alpha + nearCursorFactor * 0.45);
                    ctx.save();
                    ctx.translate(p.x, p.y);
                    ctx.globalAlpha = drawAlpha;
                    ctx.fillStyle = colors[p.colorIndex];
                    ctx.strokeStyle = colors[p.colorIndex];

                    if (p.isDash) {
                        ctx.rotate(p.angle);
                        const len = p.size * (2.6 + nearCursorFactor * 1.4);
                        ctx.lineWidth = Math.max(1.5, p.size * 0.65);
                        ctx.lineCap = 'round';
                        ctx.beginPath();
                        ctx.moveTo(-len * 0.5, 0);
                        ctx.lineTo(len * 0.5, 0);
                        ctx.stroke();
                    } else {
                        ctx.beginPath();
                        ctx.arc(0, 0, p.size * (0.9 + nearCursorFactor * 0.45), 0, Math.PI * 2);
                        ctx.fill();
                    }
                    ctx.restore();
                }

                if (!prefersReduced && !document.hidden) {
                    rafId = window.requestAnimationFrame(step);
                }
            }

            step();
            document.addEventListener('visibilitychange', () => {
                if (!document.hidden && !prefersReduced) {
                    if (rafId) window.cancelAnimationFrame(rafId);
                    rafId = window.requestAnimationFrame(step);
                }
            });
        }

        // =========================================================================
        // 2. Glass Border Spotlight & Smooth 3D Card Tilt
        // =========================================================================
        if (prefersReduced || !finePointer) return;

        const spotlightTargets = document.querySelectorAll(
            '.glass-card:not(header .glass-card):not(#landing-mobile-menu), .auth-frosted-panel'
        );

        spotlightTargets.forEach((card) => {
            card.classList.add('q4-spotlight-card');

            const isTiltable =
                enableTilt &&
                card.classList.contains('glass-card') &&
                !card.closest('#q4-faq-list') &&
                !card.closest('#partners') &&
                !card.closest('header');

            if (isTiltable) {
                card.classList.add('q4-tilt-card');
            }

            card.addEventListener('pointermove', (e) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                card.style.setProperty('--q4-mx', `${x.toFixed(1)}px`);
                card.style.setProperty('--q4-my', `${y.toFixed(1)}px`);
                card.style.setProperty('--q4-spot-opacity', '1');

                if (isTiltable && rect.width > 0 && rect.height > 0) {
                    const px = (x / rect.width - 0.5) * 2;  // -1 .. 1
                    const py = (y / rect.height - 0.5) * 2; // -1 .. 1
                    const rotY = (px * 4.2).toFixed(2);
                    const rotX = (-py * 4.2).toFixed(2);
                    card.style.transform = `perspective(1050px) translateY(-4px) rotateX(${rotX}deg) rotateY(${rotY}deg)`;
                }
            });

            card.addEventListener('pointerleave', () => {
                card.style.setProperty('--q4-spot-opacity', '0');
                if (isTiltable) {
                    card.style.transform = '';
                }
            });
        });
    })();
</script>

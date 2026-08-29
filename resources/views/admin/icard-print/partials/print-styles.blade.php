<style>
:root {
    --tray-w: {{ $phys['width_mm'] }}mm;
    --tray-h: {{ $phys['height_mm'] }}mm;
    --card-w: {{ $phys['card_w_mm'] }}mm;
    --card-h: {{ $phys['card_h_mm'] }}mm;
    --gap-x: {{ $phys['gap_x_mm'] }}mm;
    --gap-y: {{ $phys['gap_y_mm'] }}mm;
    --tray-target-margin-top: {{ $tray['target_top'] }}mm;
    --tray-target-margin-left: {{ $tray['target_left'] }}mm;
    --tray-print-cal-top: {{ $tray['cal_top'] }}mm;
    --tray-print-cal-left: {{ $tray['cal_left'] }}mm;
    --tray-margin-top: {{ $tray['margin_top'] }}mm;
    --tray-margin-left: {{ $tray['margin_left'] }}mm;
    --tray-page-shift-top: {{ $tray['page_shift_top'] }}mm;
    --tray-page-shift-left: {{ $tray['page_shift_left'] }}mm;
    --preview-scale: 1.25;
}

* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; background: #3a2a2a; font-family: 'Noto Sans Gujarati', 'Nirmala UI', Arial, sans-serif; }

.ui-wrapper {
    position: sticky; top: 0; z-index: 50;
    display: flex; flex-wrap: wrap; gap: .5rem; align-items: center;
    padding: .65rem 1rem;
    background: #6D1313; color: #fff;
}
.ui-wrapper .btn {
    border: 0; border-radius: 6px; padding: .35rem .7rem; cursor: pointer; font-size: .82rem; font-weight: 600;
}
.btn-print { background: #C9A227; color: #5A1010; }
.btn-side { background: rgba(255,255,255,.12); color: #fff; }
.btn-side.active { background: #fff; color: #6D1313; }
.ui-hint { font-size: .72rem; opacity: .85; margin-left: auto; }

.screen-scale {
    padding: 1.25rem;
    overflow: auto;
}
.screen-scale-inner {
    width: calc(var(--tray-w) * var(--preview-scale));
    height: calc(var(--tray-h) * var(--preview-scale));
    margin: 0 auto;
}
.print-page {
    width: var(--tray-w);
    height: var(--tray-h);
    background: #fff;
    position: relative;
    transform: scale(var(--preview-scale));
    transform-origin: top left;
}

.card-slot {
    position: absolute;
    width: var(--card-w);
    height: var(--card-h);
    overflow: hidden;
}
.card-slot[data-col="0"] { left: var(--tray-margin-left); }
.card-slot[data-col="1"] { left: calc(var(--tray-margin-left) + var(--card-w) + var(--gap-x)); }
.card-slot[data-row="0"] { top: var(--tray-margin-top); }
.card-slot[data-row="1"] { top: calc(var(--tray-margin-top) + var(--card-h) + var(--gap-y)); }
.card-slot[data-row="2"] { top: calc(var(--tray-margin-top) + 2 * (var(--card-h) + var(--gap-y))); }
.card-slot[data-row="3"] { top: calc(var(--tray-margin-top) + 3 * (var(--card-h) + var(--gap-y))); }
.card-slot[data-row="4"] { top: calc(var(--tray-margin-top) + 4 * (var(--card-h) + var(--gap-y))); }

.card-face { position: absolute; inset: 0; }
.card-bg {
    position: absolute; inset: 0; width: 100%; height: 100%;
    object-fit: cover;
}
.card-bg-fallback {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, #8B1A1A 0%, #C9A227 55%, #F8F3F3 55%);
}
.card-face.back .card-bg-fallback {
    background: linear-gradient(180deg, #F8F3F3 0%, #fff 40%);
}
.card-overlay { position: absolute; inset: 0; }

.f-photo {
    position: absolute; left: 4mm; top: 15.1mm;
    width: 24mm; height: 28mm;
    overflow: hidden; background: #eee;
}
.f-photo img { width: 100%; height: 100%; object-fit: cover; display: block; }
.f-qr {
    position: absolute; right: 3mm; top: 16.9mm;
    width: 25mm; height: 25mm;
    mix-blend-mode: multiply;
}
.f-qr img { width: 100%; height: 100%; display: block; }
.f-name-block {
    position: absolute; left: 4mm; right: 4mm; bottom: 2mm;
    text-align: center; line-height: 1.15;
}
.f-name { font-size: 3.2mm; font-weight: 800; }

.back-grid {
    position: absolute; left: 7mm; right: 7mm; top: 5mm; bottom: 6mm;
    display: flex; flex-direction: column; gap: 2.5mm;
    font-size: 2.6mm;
}
.b-label { font-size: 2mm; font-weight: 700; text-transform: uppercase; letter-spacing: .08mm; opacity: .75; }
.b-value { font-weight: 700; margin-top: .4mm; word-break: break-word; }
.b-span { grid-column: 1 / -1; }

.print-page.side-front .card-face.back { display: none; }
.print-page.side-back .card-face.front { display: none; }

.slot-edit {
    position: absolute; top: .6mm; right: .6mm; z-index: 8;
    width: 5mm; height: 5mm; border: 0; border-radius: 50%;
    background: rgba(255,255,255,.85); color: #6D1313; cursor: pointer; font-size: 2.6mm;
}

@media print {
    @page { size: 210mm 350mm; margin: 0; }
    html, body {
        width: 210mm; height: 350mm; margin: 0; padding: 0; overflow: hidden; background: #fff;
    }
    .ui-wrapper, .print-hint, .no-print, .slot-edit { display: none !important; }
    .screen-scale, .screen-scale-inner { padding: 0; margin: 0; width: 210mm; height: 350mm; overflow: hidden; }
    .print-page {
        transform: none !important;
        position: fixed;
        top: var(--tray-page-shift-top);
        left: var(--tray-page-shift-left);
        width: 210mm; height: 350mm;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
}
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print I-Cards — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;700;800&display=swap" rel="stylesheet">
    @include('admin.icard-print.partials.print-styles')
</head>
<body>
<div class="ui-wrapper no-print">
    <button type="button" class="btn btn-side active" data-side="front">Front</button>
    <button type="button" class="btn btn-side" data-side="back">Back</button>
    <label style="font-size:.8rem;">Zoom
        <input type="range" id="zoom" min="80" max="180" value="125" style="vertical-align:middle;width:110px;">
    </label>
    <button type="button" class="btn btn-print" id="btnDoPrint">Print</button>
    <span class="ui-hint">Paper: custom 210×350 mm · Margins: None · Scale: 100% · Background graphics ON</span>
</div>

<div class="screen-scale">
    <div class="screen-scale-inner">
        <div class="print-page side-front" id="printPage">
            @foreach($slots as $i => $card)
                @php
                    $row = intdiv($i, 2);
                    $col = $i % 2;
                    $c   = is_array($card) ? ($card['color_codes'] ?? []) : [];
                @endphp
                <div class="card-slot" data-row="{{ $row }}" data-col="{{ $col }}" data-idx="{{ $i }}">
                    @if($card)
                        <button type="button" class="slot-edit no-print" data-idx="{{ $i }}" title="Edit preview">✎</button>
                        <div class="card-face front">
                            @if(!empty($card['front_bg']))
                                <img class="card-bg" src="{{ $card['front_bg'] }}" alt="">
                            @else
                                <div class="card-bg-fallback"></div>
                            @endif
                            <div class="card-overlay">
                                <div class="f-photo">
                                    @if($card['photo_url'])
                                        <img src="{{ $card['photo_url'] }}" alt="">
                                    @endif
                                </div>
                                <div class="f-qr">
                                    @if($card['qr_code'])
                                        <img src="{{ $card['qr_code'] }}" alt="QR">
                                    @endif
                                </div>
                                <div class="f-name-block" style="color: {{ $c['front_name_value'] ?? '#2D1A0E' }}">
                                    <div class="f-name js-f-name">{{ $card['name'] }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="card-face back">
                            @if(!empty($card['back_bg']))
                                <img class="card-bg" src="{{ $card['back_bg'] }}" alt="">
                            @else
                                <div class="card-bg-fallback"></div>
                            @endif
                            <div class="card-overlay">
                                <div class="back-grid">
                                    <div>
                                        <div class="b-label" style="color: {{ $c['back_mobile_label'] ?? '#2D1A0E' }}">Phone</div>
                                        <div class="b-value js-b-mobile" style="color: {{ $c['back_mobile_value'] ?? '#2D1A0E' }}">{{ $card['mobile'] }}</div>
                                    </div>
                                    <div class="b-span">
                                        <div class="b-label" style="color: {{ $c['back_address_label'] ?? '#2D1A0E' }}">Address</div>
                                        <div class="b-value js-b-address" style="color: {{ $c['back_address_value'] ?? '#2D1A0E' }}">{{ $card['address'] }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>

<div id="editModal" class="no-print" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:80;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:12px;padding:1.1rem 1.25rem;width:min(420px,92vw);">
        <h4 style="margin:0 0 .8rem;font-size:1rem;">Edit card preview</h4>
        <p style="font-size:.75rem;color:#666;margin:0 0 .8rem;">Changes stay on this print page only. They are not saved to the member.</p>
        <label style="font-size:.8rem;">Name</label>
        <input id="eName" class="e-inp" style="width:100%;margin-bottom:.45rem;padding:.35rem;">
        <label style="font-size:.8rem;">Phone</label>
        <input id="eMobile" class="e-inp" style="width:100%;margin-bottom:.45rem;padding:.35rem;">
        <label style="font-size:.8rem;">Current address</label>
        <input id="eAddress" class="e-inp" style="width:100%;margin-bottom:.8rem;padding:.35rem;">
        <div style="display:flex;gap:.5rem;justify-content:flex-end;">
            <button type="button" id="eCancel" style="padding:.35rem .7rem;">Cancel</button>
            <button type="button" id="eApply" style="padding:.35rem .7rem;background:#6D1313;color:#fff;border:0;border-radius:6px;">Apply</button>
        </div>
    </div>
</div>

<script>
(function () {
    var page = document.getElementById('printPage');
    var scale = 1.25;
    var savedScale = 1.25;

    document.querySelectorAll('.btn-side').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-side').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            page.classList.toggle('side-front', btn.dataset.side === 'front');
            page.classList.toggle('side-back', btn.dataset.side === 'back');
        });
    });

    document.getElementById('zoom').addEventListener('input', function () {
        scale = (parseInt(this.value, 10) || 125) / 100;
        document.documentElement.style.setProperty('--preview-scale', scale);
    });

    document.getElementById('btnDoPrint').addEventListener('click', function () { window.print(); });

    window.addEventListener('beforeprint', function () {
        savedScale = scale;
        document.documentElement.style.setProperty('--preview-scale', 1);
    });
    window.addEventListener('afterprint', function () {
        document.documentElement.style.setProperty('--preview-scale', savedScale);
    });

    window.addEventListener('wheel', function (e) {
        if (!e.ctrlKey) return;
        e.preventDefault();
        var z = document.getElementById('zoom');
        z.value = String(Math.min(180, Math.max(80, parseInt(z.value, 10) + (e.deltaY < 0 ? 5 : -5))));
        z.dispatchEvent(new Event('input'));
    }, { passive: false });

    window.addEventListener('keydown', function (e) {
        if (!e.ctrlKey) return;
        if (e.key === '+' || e.key === '=' || e.key === '-' || e.key === '0') {
            e.preventDefault();
            var z = document.getElementById('zoom');
            if (e.key === '0') z.value = '125';
            else z.value = String(Math.min(180, Math.max(80, parseInt(z.value, 10) + (e.key === '-' ? -5 : 5))));
            z.dispatchEvent(new Event('input'));
        }
    });

    var editIdx = null;
    var modal = document.getElementById('editModal');

    document.querySelectorAll('.slot-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            editIdx = btn.dataset.idx;
            var slot = document.querySelector('.card-slot[data-idx="' + editIdx + '"]');
            document.getElementById('eName').value = text(slot, '.js-f-name');
            document.getElementById('eMobile').value = text(slot, '.js-b-mobile');
            document.getElementById('eAddress').value = text(slot, '.js-b-address');
            modal.style.display = 'flex';
        });
    });

    document.getElementById('eCancel').addEventListener('click', function () { modal.style.display = 'none'; });
    document.getElementById('eApply').addEventListener('click', function () {
        var slot = document.querySelector('.card-slot[data-idx="' + editIdx + '"]');
        setText(slot, '.js-f-name', document.getElementById('eName').value);
        setText(slot, '.js-b-mobile', document.getElementById('eMobile').value);
        setText(slot, '.js-b-address', document.getElementById('eAddress').value);
        modal.style.display = 'none';
    });

    function text(slot, sel) {
        var el = slot.querySelector(sel);
        return el ? el.textContent.trim() : '';
    }
    function setText(slot, sel, val) {
        var el = slot.querySelector(sel);
        if (el) el.textContent = val;
    }
})();
</script>
</body>
</html>

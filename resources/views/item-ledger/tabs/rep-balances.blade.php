@if($repBalances->isEmpty())
    <div style="text-align:center; padding:32px; color:var(--muted);">
        <p style="font-size:16px;">لا توجد أرصدة للمندوبين</p>
    </div>
@else
    <div style="overflow-x:auto;">
        <table style="width:100%; font-size:13px;">
            <thead>
                <tr>
                    <th>#</th>
                    <th>المندوب</th>
                    <th>المحمل</th>
                    <th>المباع</th>
                    <th>المرتجع</th>
                    <th>المفرغ</th>
                    <th>المتبقي</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach($repBalances as $idx => $rb)
                    <tr class="rep-row" onclick="openRepDrawer({{ $rb['rep_id'] }})">
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $rb['rep_name'] }}</strong></td>
                        <td style="color:var(--warn);">{{ number_format($rb['loaded'], 2) }}</td>
                        <td style="color:var(--danger);">{{ number_format($rb['sold'], 2) }}</td>
                        <td style="color:var(--accent);">{{ number_format($rb['returned'], 2) }}</td>
                        <td style="color:var(--muted);">{{ number_format($rb['unloaded'], 2) }}</td>
                        <td style="font-weight:700; font-size:16px; color:{{ $rb['balance'] > 0 ? 'var(--primary)' : 'var(--muted)' }};">
                            {{ number_format($rb['balance'], 2) }}
                        </td>
                        <td>
                            <button onclick="openRepDrawer({{ $rb['rep_id'] }})" class="btn" style="padding:4px 10px; font-size:12px;">
                                تفاصيل
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif

{{-- Drawer Modal --}}
<div id="repDrawer" style="display:none; position:fixed; top:0; left:0; right:0; bottom:0; background:rgba(0,0,0,0.6); z-index:1000; justify-content:flex-end;" onclick="if(event.target===this)closeRepDrawer()">
    <div style="width:420px; max-width:90vw; background:var(--bg); height:100%; overflow-y:auto; border-left:1px solid var(--line); padding:20px; direction:ltr;">
        <div style="direction:rtl;" id="repDrawerContent">
            <div style="text-align:center; padding:40px;">
                <span style="color:var(--muted);">جاري التحميل...</span>
            </div>
        </div>
    </div>
</div>

<script>
function openRepDrawer(repId) {
    const itemId = document.querySelector('select[name="item_id"]')?.value || '';
    const drawer = document.getElementById('repDrawer');
    const content = document.getElementById('repDrawerContent');
    drawer.style.display = 'flex';
    content.innerHTML = '<div style="text-align:center; padding:40px;"><span style="color:var(--muted);">جاري التحميل...</span></div>';

    const url = '{{ route("item-ledger.rep-drawer", ["repId" => "REP_ID"]) }}'.replace('REP_ID', repId) +
                (itemId ? '?item_id=' + itemId : '');

    fetch(url)
        .then(r => r.text())
        .then(html => { content.innerHTML = html; })
        .catch(() => { content.innerHTML = '<div style="text-align:center;padding:40px;color:var(--danger);">خطأ في التحميل</div>'; });

    document.body.style.overflow = 'hidden';
}

function closeRepDrawer() {
    document.getElementById('repDrawer').style.display = 'none';
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeRepDrawer();
});
</script>

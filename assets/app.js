const rp = n => 'Rp ' + Math.round(n || 0).toLocaleString('id-ID');
document.addEventListener('DOMContentLoaded', () => {
  const rows = document.getElementById('rows');
  if (!rows) return; // hanya halaman transaksi
  const $ = id => document.getElementById(id);
  const opts = '<option value="">-- pilih menu --</option>' + window.PRODUCTS.map(p =>
    `<option value="${p.id}" data-h="${p.harga}" data-s="${p.stok}" ${p.stok <= 0 ? 'disabled' : ''}>[${p.kat}] ${p.nama} - ${rp(p.harga)} (stok ${p.stok})</option>`).join('');

  function addRow(pid = '', qty = 1, note = '') {
    const tr = document.createElement('tr');
    tr.innerHTML = `<td><select name="product_id[]">${opts}</select></td><td><input type="number" name="qty[]" min="1" value="${qty}"></td>
      <td><input name="catatan[]" maxlength="150" placeholder="Less Sugar, Extra Ice..." value=""></td><td class="sub">Rp 0</td>
      <td><button type="button" class="btn sm danger">✕</button></td>`;
    tr.querySelector('select').value = pid; tr.querySelector('[name="catatan[]"]').value = note;
    tr.querySelector('button').onclick = () => { tr.remove(); if (!rows.children.length) addRow(); calc(); };
    rows.appendChild(tr);
  }

  function calc() {
    let sub = 0, over = false;
    rows.querySelectorAll('tr').forEach(tr => {
      const sel = tr.querySelector('select'), o = sel.selectedOptions[0], q = parseInt(tr.querySelector('[name="qty[]"]').value) || 0;
      const line = o && o.dataset.h ? o.dataset.h * q : 0;
      tr.querySelector('.sub').textContent = rp(line); sub += line;
      const bad = o && o.dataset.s && q > +o.dataset.s; tr.querySelector('[name="qty[]"]').style.borderColor = bad ? '#c0392b' : ''; if (bad) over = true;
    });
    const t = $('dTipe').value, v = parseFloat($('dNilai').value) || 0;
    let dis = t === 'persen' ? sub * Math.min(v, 100) / 100 : t === 'nominal' ? Math.min(v, sub) : 0;
    dis = Math.round(dis);
    const pjk = Math.round((sub - dis) * 0.10), grand = sub - dis + pjk;
    $('tSub').textContent = rp(sub); $('tDis').textContent = rp(dis); $('tPjk').textContent = rp(pjk); $('tGrand').textContent = rp(grand);
    const tunai = $('metode').value === 'Tunai'; $('bayarWrap').style.display = tunai ? '' : 'none';
    const info = $('kembali'); let ok = sub > 0 && !over;
    if (tunai) {
      const b = parseInt($('bayar').value) || 0;
      if (b < grand) { info.textContent = 'Uang kurang ' + rp(grand - b); info.className = 'kembali bad'; ok = false; }
      else { info.textContent = 'Kembalian: ' + rp(b - grand); info.className = 'kembali good'; }
    } else { info.textContent = ''; }
    if (over) { info.textContent = 'Qty melebihi stok tersedia.'; info.className = 'kembali bad'; }
    $('submitBtn').disabled = !ok;
  }

  const toggleTipe = () => { const d = $('tipe').value === 'Dine-In'; $('mejaWrap').style.display = d ? '' : 'none'; $('meja').required = d; };
  $('addRow').onclick = () => { addRow(); calc(); };
  $('tipe').onchange = toggleTipe;
  $('trxForm').addEventListener('input', calc); $('trxForm').addEventListener('change', calc);

  const old = window.OLD;
  if (old && old.product_id) {
    $('tipe').value = old.tipe_pesanan; $('meja').value = old.nomor_meja || ''; document.querySelector('[name=nama_pemesan]').value = old.nama_pemesan || '';
    $('dTipe').value = old.diskon_tipe; $('dNilai').value = old.diskon_nilai; $('metode').value = old.metode_bayar; $('bayar').value = old.uang_dibayar || '';
    old.product_id.forEach((p, i) => addRow(p, old.qty[i], old.catatan[i]));
  } else addRow();
  toggleTipe(); calc();
});
